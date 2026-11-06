<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Computes the "Monitoring" dashboard (Analysis menu).
 *
 * Definitions (single source of truth):
 *  - live / active      : status 0 (Todo) or 1 (Proposed)
 *  - overdue            : status 0, realisation_date null and plan_date < today
 *  - not yet overdue    : status 0 and not overdue
 *  - pending validation : status 1 (never overdue)
 *  - overdue rate       : overdue / live (0 when live = 0)
 *  - security-owned     : at least one assigned group of type 'security', or at least
 *                         one directly assigned user member of a 'security' group
 *
 * Runs a fixed number of queries (independent of the data volume) and
 * aggregates in PHP.
 */
class MonitoringService
{
    public const PRIORITY_SECURITY_OVERDUE = 'security_overdue';
    public const PRIORITY_NON_SECURITY_OVERDUE = 'non_security_overdue';
    public const PRIORITY_PENDING = 'pending';
    public const PRIORITY_NOT_YET_DUE = 'not_yet_due';

    /**
     * @param int|null $scopeUserId null = all data, otherwise only the measures
     *                              assigned to this user (directly or via a group)
     * @param CarbonInterface|null $today reference date (defaults to today)
     *
     * @return array<string, mixed>
     */
    public function compute(?int $scopeUserId, ?CarbonInterface $today = null): array
    {
        $today = ($today ?? Carbon::today())->format('Y-m-d');

        // --- Load everything in a bounded number of queries
        $measures = DB::table('measures')
            ->select('id', 'status', 'plan_date', 'realisation_date')
            ->get();

        $measureUsers = self::groupPairs(
            DB::table('measure_user')->select('measure_id', 'user_id')->get(),
            'measure_id',
            'user_id'
        );
        $measureGroups = self::groupPairs(
            DB::table('measure_user_group')->select('measure_id', 'user_group_id')->get(),
            'measure_id',
            'user_group_id'
        );
        $groupMembers = self::groupPairs(
            DB::table('user_user_group')->select('user_id', 'user_group_id')->get(),
            'user_group_id',
            'user_id'
        );

        $users = DB::table('users')
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        $groups = DB::table('user_groups')
            ->select('id', 'name', 'type')
            ->orderBy('name')
            ->get();

        $userNames = [];
        foreach ($users as $user) {
            $userNames[(int) $user->id] = (string) $user->name;
        }
        $groupNames = [];
        $securityGroups = [];
        foreach ($groups as $group) {
            $groupNames[(int) $group->id] = (string) $group->name;
            if ($group->type === UserGroup::TYPE_SECURITY) {
                $securityGroups[(int) $group->id] = true;
            }
        }

        // Users member of at least one security group
        $securityUsers = [];
        foreach ($securityGroups as $groupId => $_) {
            foreach ($groupMembers[$groupId] ?? [] as $userId) {
                $securityUsers[$userId] = true;
            }
        }

        // --- Visible scope
        $visible = null;
        if ($scopeUserId !== null) {
            $visible = [];
            foreach ($measureUsers as $measureId => $userIds) {
                if (in_array($scopeUserId, $userIds, true)) {
                    $visible[$measureId] = true;
                }
            }
            foreach ($measureGroups as $measureId => $groupIds) {
                foreach ($groupIds as $groupId) {
                    if (in_array($scopeUserId, $groupMembers[$groupId] ?? [], true)) {
                        $visible[$measureId] = true;
                        break;
                    }
                }
            }
        }

        // --- Aggregation
        $total = 0;
        $ownershipAll = ['security' => 0, 'non_security' => 0];
        $ownership = [
            'security' => self::emptyStats(),
            'non_security' => self::emptyStats(),
            'total' => self::emptyStats(),
        ];
        $priorities = [
            self::PRIORITY_SECURITY_OVERDUE => 0,
            self::PRIORITY_NON_SECURITY_OVERDUE => 0,
            self::PRIORITY_PENDING => 0,
            self::PRIORITY_NOT_YET_DUE => 0,
        ];
        $combinations = [];
        $individuals = [];
        $groupStats = [];

        foreach ($measures as $measure) {
            $measureId = (int) $measure->id;
            if ($visible !== null && ! isset($visible[$measureId])) {
                continue;
            }
            $total++;

            $directUsers = $measureUsers[$measureId] ?? [];
            $assignedGroups = $measureGroups[$measureId] ?? [];

            $isSecurity = false;
            foreach ($assignedGroups as $groupId) {
                if (isset($securityGroups[$groupId])) {
                    $isSecurity = true;
                    break;
                }
            }
            if (! $isSecurity) {
                foreach ($directUsers as $userId) {
                    if (isset($securityUsers[$userId])) {
                        $isSecurity = true;
                        break;
                    }
                }
            }
            $owner = $isSecurity ? 'security' : 'non_security';
            $ownershipAll[$owner]++;

            $state = self::classify($measure, $today);
            if ($state === null) {
                continue;
            }
            [$kind, $daysOverdue] = $state;

            // Ownership
            self::add($ownership[$owner], $kind, $daysOverdue);
            self::add($ownership['total'], $kind, $daysOverdue);

            // Priorities
            if ($kind === 'overdue') {
                $priorities[$isSecurity ? self::PRIORITY_SECURITY_OVERDUE : self::PRIORITY_NON_SECURITY_OVERDUE]++;
            } elseif ($kind === 'pending') {
                $priorities[self::PRIORITY_PENDING]++;
            } else {
                $priorities[self::PRIORITY_NOT_YET_DUE]++;
            }

            // A. Exact assignment combinations (users then groups, by name)
            $names = [];
            foreach ($directUsers as $userId) {
                if (isset($userNames[$userId])) {
                    $names['u'][$userId] = $userNames[$userId];
                }
            }
            foreach ($assignedGroups as $groupId) {
                if (isset($groupNames[$groupId])) {
                    $names['g'][$groupId] = $groupNames[$groupId];
                }
            }
            $userPart = $names['u'] ?? [];
            $groupPart = $names['g'] ?? [];
            asort($userPart, SORT_NATURAL | SORT_FLAG_CASE);
            asort($groupPart, SORT_NATURAL | SORT_FLAG_CASE);
            $key = 'u' . implode(',', array_keys($userPart)) . '|g' . implode(',', array_keys($groupPart));
            if (! isset($combinations[$key])) {
                $combinations[$key] = [
                    'label' => implode(', ', array_merge(array_values($userPart), array_values($groupPart))),
                    'users' => $userPart,
                    'groups' => $groupPart,
                    'unassigned' => $userPart === [] && $groupPart === [],
                ] + self::emptyStats();
            }
            self::add($combinations[$key], $kind, $daysOverdue);

            // B. Individuals: direct, via group, deduplicated union
            $viaGroupUsers = [];
            foreach ($assignedGroups as $groupId) {
                foreach ($groupMembers[$groupId] ?? [] as $userId) {
                    $viaGroupUsers[$userId] = true;
                }
            }
            $directSet = array_fill_keys($directUsers, true);
            foreach ($directSet + $viaGroupUsers as $userId => $_) {
                if (! isset($userNames[$userId])) {
                    continue;
                }
                $individuals[$userId] ??= ['direct' => 0, 'via_group' => 0] + self::emptyStats();
                if (isset($directSet[$userId])) {
                    $individuals[$userId]['direct']++;
                }
                if (isset($viaGroupUsers[$userId])) {
                    $individuals[$userId]['via_group']++;
                }
                self::add($individuals[$userId], $kind, $daysOverdue);
            }

            // C. Groups
            foreach ($assignedGroups as $groupId) {
                $groupStats[$groupId] ??= self::emptyStats();
                self::add($groupStats[$groupId], $kind, $daysOverdue);
            }
        }

        // --- Build rows
        foreach ($ownership as $k => $stats) {
            $ownership[$k] = self::finalize($stats);
        }

        $live = $ownership['total']['live'];
        $priorityRows = [];
        foreach ($priorities as $k => $count) {
            $priorityRows[] = [
                'key' => $k,
                'count' => $count,
                'percent' => self::ratio($count, $live),
            ];
        }

        $combinationRows = array_map(fn ($row) => self::finalize($row), array_values($combinations));
        usort($combinationRows, fn ($a, $b) => [$b['overdue'], $b['live'], $a['label']] <=> [$a['overdue'], $a['live'], $b['label']]);

        $individualRows = [];
        foreach ($users as $user) {
            if ((int) $user->role === User::ROLE_API) {
                continue;
            }
            $userId = (int) $user->id;
            $disabled = (int) $user->role === User::ROLE_DISABLED;
            $stats = $individuals[$userId] ?? ['direct' => 0, 'via_group' => 0] + self::emptyStats();
            if ($disabled && $stats['live'] === 0) {
                continue;
            }
            $individualRows[] = [
                'id' => $userId,
                'name' => (string) $user->name,
                'disabled' => $disabled,
            ] + self::finalize($stats);
        }
        usort($individualRows, fn ($a, $b) => [$b['overdue'], $b['live'], $a['name']] <=> [$a['overdue'], $a['live'], $b['name']]);

        $groupRows = [];
        foreach ($groups as $group) {
            $groupId = (int) $group->id;
            $groupRows[] = [
                'id' => $groupId,
                'name' => (string) $group->name,
                'type' => (string) $group->type,
                'members' => count(array_unique($groupMembers[$groupId] ?? [])),
            ] + self::finalize($groupStats[$groupId] ?? self::emptyStats());
        }
        usort($groupRows, fn ($a, $b) => [$b['overdue'], $b['live'], $a['name']] <=> [$a['overdue'], $a['live'], $b['name']]);

        return [
            'today' => $today,
            'total' => $total,
            'live' => $live,
            'overdue' => $ownership['total']['overdue'],
            'overdue_rate' => $ownership['total']['rate'],
            'ownership_all' => $ownershipAll,
            'ownership' => $ownership,
            'priorities' => $priorityRows,
            'combinations' => $combinationRows,
            'individuals' => $individualRows,
            'groups' => $groupRows,
        ];
    }

    /**
     * Format a percentage according to the current locale (48,1 % / 48.1%).
     */
    public static function formatPercent(float $value): string
    {
        if (app()->getLocale() === 'en') {
            return number_format($value, 1, '.', ',') . '%';
        }

        return number_format($value, 1, ',', "\u{202F}") . "\u{00A0}%";
    }

    /**
     * Classify a measure: null when not live, otherwise [kind, days overdue].
     *
     * @return array{0: string, 1: int}|null
     */
    private static function classify(object $measure, string $today): ?array
    {
        $status = (int) $measure->status;
        if ($status === 1) {
            return ['pending', 0];
        }
        if ($status !== 0) {
            return null;
        }

        $planDate = $measure->plan_date !== null ? substr((string) $measure->plan_date, 0, 10) : null;
        if ($measure->realisation_date === null && $planDate !== null && $planDate < $today) {
            $days = (int) (new DateTimeImmutable($planDate))->diff(new DateTimeImmutable($today))->days;

            return ['overdue', $days];
        }

        return ['not_yet', 0];
    }

    /**
     * @return array{live: int, overdue: int, not_yet: int, pending: int, max_days: int}
     */
    private static function emptyStats(): array
    {
        return ['live' => 0, 'overdue' => 0, 'not_yet' => 0, 'pending' => 0, 'max_days' => 0];
    }

    /**
     * @param array<string, mixed> $stats
     */
    private static function add(array &$stats, string $kind, int $daysOverdue): void
    {
        $stats['live']++;
        $stats[$kind]++;
        if ($daysOverdue > $stats['max_days']) {
            $stats['max_days'] = $daysOverdue;
        }
    }

    /**
     * @param array<string, mixed> $stats
     *
     * @return array<string, mixed>
     */
    private static function finalize(array $stats): array
    {
        $stats['rate'] = self::ratio($stats['overdue'], $stats['live']);

        return $stats;
    }

    private static function ratio(int $numerator, int $denominator): float
    {
        return $denominator === 0 ? 0.0 : $numerator / $denominator * 100;
    }

    /**
     * @param iterable<object> $rows
     *
     * @return array<int, array<int, int>>
     */
    private static function groupPairs(iterable $rows, string $keyColumn, string $valueColumn): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->{$keyColumn}][] = (int) $row->{$valueColumn};
        }
        foreach ($result as $key => $values) {
            $result[$key] = array_values(array_unique($values));
        }

        return $result;
    }
}
