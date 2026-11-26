<?php

namespace App\Services\Ai;

/**
 * Reading and defensive check of the answer before it is stored and displayed.
 *
 * The format is the output contract of AiPrompt (resources/ai/contract.json),
 * locked in the code because the panel parses it.
 *
 * - The JSON object is extracted from the text of the model, tolerating a
 *   Markdown code fence, a reasoning block or text around it.
 * - The essential fields (summary, assessed_status) must be strings: otherwise 502.
 * - The other displayed fields are kept when they have the type the panel
 *   expects, and dropped otherwise; an absent field is simply not displayed.
 * - An unknown enum value is not an error: it is displayed as is, with a grey badge.
 * - Fields the panel does not use are dropped.
 */
class AiResponseGuard
{
    private const ESSENTIAL = ['summary', 'assessed_status'];
    private const OPTIONAL_STRINGS = ['risk_level', 'confidence'];
    private const FINDING = ['type', 'description'];
    private const RECOMMENDATION = ['priority', 'action', 'rationale', 'suggested_due_date', 'reference'];
    private const VIEWPOINTS = ['ceo', 'ciso', 'dpo', 'auditor'];
    private const PRACTICE = ['practice', 'coverage', 'controls', 'comment'];

    /**
     * JSON object contained in the text answered by the model.
     *
     * @throws AiAgentException
     */
    public function extract(string $content): mixed
    {
        // Reasoning of some open-weight models
        $text = trim((string) preg_replace('/<think>.*?<\/think>/s', '', $content));

        $candidates = [$text];
        if (preg_match('/```(?:json)?\s*(.*?)```/si', $text, $match) === 1) {
            $candidates[] = trim($match[1]);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidates[] = substr($text, $start, $end - $start + 1);
        }

        foreach ($candidates as $candidate) {
            $data = json_decode($candidate, true);
            if (is_array($data)) {
                return $data;
            }
        }

        $this->fail();
    }

    /**
     * @return array<string, mixed> the data the panel can display
     *
     * @throws AiAgentException
     */
    public function check(mixed $data): array
    {
        if (! self::isObject($data)) {
            $this->fail();
        }

        $result = [];
        foreach (self::ESSENTIAL as $key) {
            if (! is_string($data[$key] ?? null) || trim($data[$key]) === '') {
                $this->fail();
            }
            $result[$key] = $data[$key];
        }

        foreach (self::OPTIONAL_STRINGS as $key) {
            if (is_string($data[$key] ?? null) && $data[$key] !== '') {
                $result[$key] = $data[$key];
            }
        }

        $practices = $this->objects($data['good_practices'] ?? null, self::PRACTICE, ['practice', 'coverage']);
        if ($practices !== null) {
            $result['good_practices'] = $practices;
        }

        $findings = $this->objects($data['findings'] ?? null, self::FINDING, ['description']);
        if ($findings !== null) {
            $result['findings'] = $findings;
        }

        $recommendations = $this->objects($data['recommendations'] ?? null, self::RECOMMENDATION, ['action']);
        if ($recommendations !== null) {
            $result['recommendations'] = $recommendations;
        }

        if (self::isList($data['open_questions'] ?? null)) {
            $result['open_questions'] = array_values(array_filter($data['open_questions'], 'is_string'));
        }

        if (self::isObject($data['viewpoints'] ?? null)) {
            $viewpoints = array_filter(
                array_intersect_key($data['viewpoints'], array_flip(self::VIEWPOINTS)),
                'is_string'
            );
            if ($viewpoints !== []) {
                $result['viewpoints'] = $viewpoints;
            }
        }

        return $result;
    }

    /**
     * List of objects whose string fields are kept; an item without its
     * required fields is dropped. Null when the value is not a list.
     *
     * @param  array<int, string>  $fields
     * @param  array<int, string>  $required
     * @return array<int, array<string, string>>|null
     */
    private function objects(mixed $value, array $fields, array $required): ?array
    {
        if (! self::isList($value)) {
            return null;
        }

        $result = [];
        foreach ($value as $item) {
            if (! self::isObject($item)) {
                continue;
            }
            $kept = array_filter(array_intersect_key($item, array_flip($fields)), 'is_string');
            if (array_diff($required, array_keys($kept)) === []) {
                $result[] = $kept;
            }
        }

        return $result;
    }

    private static function isObject(mixed $value): bool
    {
        return is_array($value) && ($value === [] || ! array_is_list($value));
    }

    private static function isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    private function fail(): never
    {
        throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
    }
}
