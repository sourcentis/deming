<?php

namespace App\Services\Ai;

/**
 * Defensive check of the analysis before it is stored and displayed.
 *
 * The response format is defined by the AI gateway (tasks/_schemas/analysis-v1.json
 * in sourcentis-ai-gateway), which validates the answer of the model. Deming
 * follows version 1 of that format and does not define it: this guard only
 * protects the panel.
 *
 * - The essential fields (synthese, statut_evalue) must be strings: otherwise 502.
 * - The other displayed fields are kept when they have the type the panel
 *   expects, and dropped otherwise; an absent field is simply not displayed.
 * - An unknown enum value is not an error: it is displayed as is, with a grey
 *   badge, so that the gateway can evolve its format without breaking Deming.
 * - Fields the panel does not use are dropped.
 */
class AiResponseGuard
{
    // Version of the gateway's response format followed by Deming
    public const SCHEMA = 'analysis-v1';

    private const ESSENTIAL = ['synthese', 'statut_evalue'];
    private const OPTIONAL_STRINGS = ['niveau_risque', 'confiance'];
    private const FINDING = ['type', 'description'];
    private const RECOMMENDATION = ['priorite', 'action', 'justification', 'echeance_suggeree', 'reference'];
    private const VIEWPOINTS = ['ceo', 'rssi', 'dpo', 'auditeur'];

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

        $findings = $this->objects($data['constats'] ?? null, self::FINDING, ['description']);
        if ($findings !== null) {
            $result['constats'] = $findings;
        }

        $recommendations = $this->objects($data['recommandations'] ?? null, self::RECOMMENDATION, ['action']);
        if ($recommendations !== null) {
            $result['recommandations'] = $recommendations;
        }

        if (self::isList($data['questions_ouvertes'] ?? null)) {
            $result['questions_ouvertes'] = array_values(array_filter($data['questions_ouvertes'], 'is_string'));
        }

        if (self::isObject($data['points_de_vue'] ?? null)) {
            $viewpoints = array_filter(
                array_intersect_key($data['points_de_vue'], array_flip(self::VIEWPOINTS)),
                'is_string'
            );
            if ($viewpoints !== []) {
                $result['points_de_vue'] = $viewpoints;
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
