<?php

namespace App\Services\Report;

use App\Models\Action;
use App\Models\Attribute;
use App\Models\Control;
use App\Models\Domain;
use App\Models\Exception;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

/**
 * Tables et colonnes utilisables dans les balises ${table:...} des modèles de rapport.
 *
 * Seules les tables déclarées ici sont accessibles (liste blanche) ; les colonnes sensibles
 * (mots de passe, jetons) ne sont jamais exposées. Chaque table peut déclarer des colonnes
 * calculées (relations, libellés) et un filtre sur le référentiel choisi pour le rapport.
 */
class ReportTableRegistry
{
    /** Colonnes jamais exposées, quelle que soit la table. */
    private const EXCLUDED_COLUMNS = [
        'password', 'remember_token', 'standard', 'deleted_at',
        'ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash',
    ];

    /**
     * @return array<string, array{
     *     model: class-string<Model>,
     *     trans: string,
     *     labels?: array<string, string>,
     *     with?: array<int, string>,
     *     virtual?: array<string, array{label: string, value: \Closure}>,
     *     framework?: \Closure
     * }>
     */
    private static function definitions(): array
    {
        $names = fn ($items, string $attribute) => $items->pluck($attribute)->filter()->implode(', ');

        $byDomainFramework = fn (string $relation) => fn (Builder $query, string $framework) => $query->whereHas(
            $relation,
            fn (Builder $q) => $q->where('framework', $framework)
        );

        return [
            'domains' => [
                'model' => Domain::class,
                'trans' => 'domain',
                // "title" est présenté comme le nom du domaine dans l'application
                'labels' => ['title' => 'cruds.domain.fields.name'],
                'framework' => fn (Builder $query, string $framework) => $query->where('framework', $framework),
            ],
            'controls' => [
                'model' => Control::class,
                'trans' => 'control',
                'with' => ['domain'],
                'virtual' => [
                    'domain' => ['label' => 'cruds.control.fields.domain', 'value' => fn (Control $c) => $c->domain?->title],
                    'framework' => ['label' => 'cruds.domain.fields.framework', 'value' => fn (Control $c) => $c->domain?->framework],
                ],
                'framework' => $byDomainFramework('domain'),
            ],
            'measures' => [
                'model' => Measure::class,
                'trans' => 'measure',
                'with' => ['controls', 'users'],
                'virtual' => [
                    'clauses' => ['label' => 'cruds.measure.fields.clauses', 'value' => fn (Measure $m) => $names($m->controls, 'clause')],
                    'owners' => ['label' => 'cruds.measure.fields.owners', 'value' => fn (Measure $m) => $names($m->users, 'name')],
                ],
                'framework' => $byDomainFramework('controls.domain'),
            ],
            'actions' => [
                'model' => Action::class,
                'trans' => 'action',
                'with' => ['controls', 'owners'],
                'virtual' => [
                    'clauses' => ['label' => 'cruds.action.fields.clauses', 'value' => fn (Action $a) => $names($a->controls, 'clause')],
                    'owners' => ['label' => 'cruds.action.fields.owners', 'value' => fn (Action $a) => $names($a->owners, 'name')],
                ],
                'framework' => $byDomainFramework('controls.domain'),
            ],
            'risks' => [
                'model' => Risk::class,
                'trans' => 'risk',
                'with' => ['owner'],
                'virtual' => [
                    'owner' => ['label' => 'cruds.risk.fields.owner', 'value' => fn (Risk $r) => $r->owner?->name],
                    'score' => ['label' => 'cruds.risk.fields.score', 'value' => fn (Risk $r) => $r->risk_score],
                    'level' => ['label' => 'report_template.columns.level', 'value' => fn (Risk $r) => $r->risk_level_label],
                    'status_label' => ['label' => 'cruds.risk.fields.status', 'value' => fn (Risk $r) => Risk::statusLabel($r->status)],
                ],
            ],
            'exceptions' => [
                'model' => Exception::class,
                'trans' => 'exception',
                'with' => ['measure'],
                'virtual' => [
                    'clause' => ['label' => 'cruds.exception.fields.measure', 'value' => fn (Exception $e) => $e->measure?->clause],
                    'status_label' => ['label' => 'cruds.exception.fields.status', 'value' => fn (Exception $e) => $e->status_label],
                ],
                'framework' => $byDomainFramework('measure.domain'),
            ],
            'attributes' => [
                'model' => Attribute::class,
                'trans' => 'attribute',
            ],
            'users' => [
                'model' => User::class,
                'trans' => 'user',
            ],
            'user_groups' => [
                'model' => UserGroup::class,
                'trans' => 'group',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function tables(): array
    {
        return array_keys(self::definitions());
    }

    public static function has(string $table): bool
    {
        return array_key_exists($table, self::definitions());
    }

    /** Libellé lisible d'une table (ex. "Plans d'action"). */
    public static function tableLabel(string $table): string
    {
        $key = 'report_template.tables.' . $table;

        return Lang::has($key) ? trans($key) : $table;
    }

    /**
     * Colonnes réelles exposées (colonnes de la table moins les colonnes exclues).
     *
     * @return array<int, string>
     */
    public static function realColumns(string $table): array
    {
        return array_values(array_diff(Schema::getColumnListing($table), self::EXCLUDED_COLUMNS));
    }

    /**
     * Toutes les colonnes utilisables avec leur libellé : colonnes réelles puis calculées.
     *
     * @return array<string, string>
     */
    public static function columns(string $table): array
    {
        $definition = self::definitions()[$table];
        $columns = [];

        foreach (self::realColumns($table) as $column) {
            $columns[$column] = self::label($definition, $column);
        }

        foreach ($definition['virtual'] ?? [] as $column => $virtual) {
            $columns[$column] = Lang::has($virtual['label']) ? trans($virtual['label']) : $column;
        }

        return $columns;
    }

    /**
     * Description des tables pour l'aide du modèle : libellé, filtre par référentiel, colonnes.
     *
     * @return array<string, array{label: string, framework: bool, columns: array<string, array{label: string, virtual: bool}>}>
     */
    public static function describe(): array
    {
        $tables = [];
        foreach (self::tables() as $table) {
            $columns = [];
            foreach (self::columns($table) as $column => $label) {
                $columns[$column] = ['label' => $label, 'virtual' => self::isVirtual($table, $column)];
            }
            $tables[$table] = [
                'label' => self::tableLabel($table),
                'framework' => self::supportsFramework($table),
                'columns' => $columns,
            ];
        }

        return $tables;
    }

    public static function isVirtual(string $table, string $column): bool
    {
        return isset(self::definitions()[$table]['virtual'][$column]);
    }

    /** Requête de base de la table, avec les relations nécessaires aux colonnes calculées. */
    public static function query(string $table): Builder
    {
        $definition = self::definitions()[$table];

        return $definition['model']::query()->with($definition['with'] ?? []);
    }

    /** Filtre la requête sur un référentiel, si la table est rattachée aux domaines. */
    public static function applyFramework(string $table, Builder $query, ?string $framework): void
    {
        $filter = self::definitions()[$table]['framework'] ?? null;

        if ($framework !== null && $framework !== '' && $filter !== null) {
            $filter($query, $framework);
        }
    }

    public static function supportsFramework(string $table): bool
    {
        return isset(self::definitions()[$table]['framework']);
    }

    /** Valeur d'une colonne (réelle ou calculée) pour un enregistrement. */
    public static function value(string $table, Model $record, string $column): mixed
    {
        $virtual = self::definitions()[$table]['virtual'][$column] ?? null;

        if ($virtual !== null) {
            return ($virtual['value'])($record);
        }

        return $record->getAttributeValue($column);
    }

    /**
     * Libellé d'une colonne réelle : libellé propre à la table, puis libellé du champ de
     * l'entité, puis libellé générique (id, dates techniques...), sinon le nom de la colonne.
     */
    private static function label(array $definition, string $column): string
    {
        $keys = [
            $definition['labels'][$column] ?? null,
            "cruds.{$definition['trans']}.fields.{$column}",
            "report_template.columns.{$column}",
        ];

        foreach (array_filter($keys) as $key) {
            if (Lang::has($key)) {
                return trans($key);
            }
        }

        return $column;
    }
}
