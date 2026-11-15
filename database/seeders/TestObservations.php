<?php

namespace Database\Seeders;

/**
 * Observations of the test data, consistent with the score of the control:
 * 3 = compliant, 2 = partially compliant, 1 = non-compliant.
 *
 * The clauses of the ISO 27001:2022 framework have their own observations
 * (database/data/observations.{fr,en}.php); the other ones receive a generic
 * observation.
 */
class TestObservations
{
    /** @var array<string, array<string, array<int, string>>> by locale */
    private static array $observations = [];

    public static function for(?string $clause, int $score, string $locale): string
    {
        $locale = $locale === 'fr' ? 'fr' : 'en';
        self::$observations[$locale] ??= require database_path('data/observations.' . $locale . '.php');

        return self::$observations[$locale][trim((string) $clause)][$score]
            ?? self::generic($score, $locale);
    }

    private static function generic(int $score, string $locale): string
    {
        $generic = $locale === 'fr'
            ? [
                3 => 'La mesure est mise en œuvre conformément aux exigences et les preuves présentées sont complètes. Aucun écart n\'a été relevé.',
                2 => 'La mesure est partiellement mise en œuvre et certaines preuves sont incomplètes. Des améliorations sont nécessaires pour atteindre la conformité.',
                1 => 'La mesure n\'est pas mise en œuvre et aucune preuve n\'a pu être présentée. Un plan d\'action doit être défini.',
            ]
            : [
                3 => 'The control is implemented in line with the requirements and the evidence provided is complete. No gap was found.',
                2 => 'The control is partially implemented and some evidence is incomplete. Improvements are needed to reach compliance.',
                1 => 'The control is not implemented and no evidence could be provided. An action plan must be defined.',
            ];

        return $generic[$score];
    }
}
