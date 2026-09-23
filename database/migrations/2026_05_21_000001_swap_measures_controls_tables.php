<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inverse les rôles des tables :
 *   measures (référentiel)        → controls
 *   controls (instances d'audit)  → measures
 *
 * Sur MySQL/MariaDB le DDL n'est pas transactionnel : cette migration est donc
 * rejouable après un échec partiel (chaque étape détecte si elle a déjà été faite).
 * Sur PostgreSQL/SQLite, Laravel l'exécute dans une transaction.
 *
 * Avant toute modification, une copie des trois tables est conservée
 * (bak_20260521_*) et la volumétrie est vérifiée à la fin.
 * Les tables de sauvegarde peuvent être supprimées une fois l'upgrade validé.
 */
return new class extends Migration
{
    private const BACKUP_PREFIX = 'bak_20260521_';

    /** [table, ancienne colonne, nouvelle colonne] */
    private const COLUMN_RENAMES = [
        ['control_user',       'control_id', 'measure_id'],
        ['control_user_group', 'control_id', 'measure_id'],
        ['actions',            'control_id', 'measure_id'],
        ['documents',          'control_id', 'measure_id'],
        ['action_measure',     'measure_id', 'control_id'],
        ['exceptions',         'measure_id', 'control_id'],
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);

        $expected = $this->backupAndExpectedCounts();

        Schema::disableForeignKeyConstraints();
        try {
            // ── Étape 1 : échange des tables ──────────────────────────────────
            $this->swapTables();

            // ── Étape 2 : pivot control_measure ───────────────────────────────
            if ($isMysql) {
                $this->swapPivotColumnsMysql();
            } else {
                $this->swapPivotValues($driver);
            }

            // ── Étapes 3 à 8 : renommage des colonnes de liaison ─────────────
            foreach (self::COLUMN_RENAMES as [$table, $from, $to]) {
                if (Schema::hasTable($table)
                    && Schema::hasColumn($table, $from)
                    && ! Schema::hasColumn($table, $to)) {
                    Schema::table($table, fn (Blueprint $t) => $t->renameColumn($from, $to));
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->assertCounts($expected);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Les instances d'audit sont les seules à porter realisation_date :
     * si cette colonne est dans `measures`, l'échange des tables est fait.
     */
    private function tablesSwapped(): bool
    {
        return ! Schema::hasTable('controls_swap_tmp')
            && Schema::hasTable('measures')
            && Schema::hasColumn('measures', 'realisation_date');
    }

    /**
     * Copie les tables au premier passage et retourne la volumétrie attendue
     * après migration (calculée depuis les copies, donc stable en cas de reprise).
     */
    private function backupAndExpectedCounts(): array
    {
        $sources = ['measures', 'controls', 'control_measure'];

        $untouched = Schema::hasTable('measures')
            && Schema::hasTable('controls')
            && ! Schema::hasTable('controls_swap_tmp')
            && ! $this->tablesSwapped();

        if ($untouched) {
            foreach ($sources as $table) {
                $backup = self::BACKUP_PREFIX.$table;
                if (! Schema::hasTable($backup)) {
                    $this->copyTable($table, $backup);
                }
            }
        }

        $expected = [];
        $map = ['measures' => 'controls', 'controls' => 'measures', 'control_measure' => 'control_measure'];
        foreach ($map as $old => $new) {
            $backup = self::BACKUP_PREFIX.$old;
            if (Schema::hasTable($backup)) {
                $expected[$new] = DB::table($backup)->count();
            }
        }

        return $expected;
    }

    /**
     * Copie structure + données, sans les clés étrangères.
     * MySQL/MariaDB : CREATE TABLE … LIKE conserve la clé primaire (compatible
     * sql_require_primary_key) et évite CREATE … SELECT, refusé avec
     * enforce_gtid_consistency sur MySQL < 8.0.21.
     */
    private function copyTable(string $source, string $target): void
    {
        switch (DB::getDriverName()) {
            case 'mysql':
            case 'mariadb':
                DB::statement("CREATE TABLE `{$target}` LIKE `{$source}`");
                DB::statement("INSERT INTO `{$target}` SELECT * FROM `{$source}`");
                break;
            case 'pgsql':
                DB::statement("CREATE TABLE \"{$target}\" (LIKE \"{$source}\" INCLUDING DEFAULTS)");
                DB::statement("INSERT INTO \"{$target}\" SELECT * FROM \"{$source}\"");
                break;
            default:
                DB::statement("CREATE TABLE \"{$target}\" AS SELECT * FROM \"{$source}\"");
        }
    }

    /** Étape 1, rejouable depuis n'importe quel état intermédiaire. */
    private function swapTables(): void
    {
        if (Schema::hasTable('controls_swap_tmp')) {
            // Reprise : measures → controls_swap_tmp a déjà été fait
            if (! Schema::hasTable('measures')) {
                Schema::rename('controls', 'measures');
            }
            Schema::rename('controls_swap_tmp', 'controls');

            return;
        }

        if (! $this->tablesSwapped()) {
            Schema::rename('measures', 'controls_swap_tmp');
            Schema::rename('controls', 'measures');
            Schema::rename('controls_swap_tmp', 'controls');
        }
    }

    /**
     * Étape 2 pour MySQL/MariaDB : on échange les NOMS des colonnes (et des index
     * associés) au lieu d'échanger les valeurs. Aucune donnée n'est réécrite et
     * l'état est détectable via la cible de la FK : une fois fait, control_id
     * référence `controls` (le référentiel).
     */
    private function swapPivotColumnsMysql(): void
    {
        $table = 'control_measure';

        if (! Schema::hasColumn($table, 'swap_tmp')) {
            $target = $this->foreignTable($table, 'control_id');

            if ($target === null) {
                throw new RuntimeException(
                    "Aucune clé étrangère sur {$table}.control_id : impossible de déterminer l'état du pivot. "
                    .'Vérifier manuellement avant de relancer la migration.'
                );
            }

            if ($target !== 'controls') {
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn('control_id', 'swap_tmp'));
            }
        }

        if (Schema::hasColumn($table, 'swap_tmp')) {
            if (! Schema::hasColumn($table, 'control_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->renameColumn('measure_id', 'control_id'));
            }
            Schema::table($table, fn (Blueprint $t) => $t->renameColumn('swap_tmp', 'measure_id'));
        }

        // Les index suivent les colonnes : on échange aussi leurs noms pour que
        // 2026_05_21_000002 puisse recréer les FK sous leurs noms conventionnels.
        $a = 'control_measure_control_id_foreign';
        $b = 'control_measure_measure_id_foreign';
        $tmp = 'control_measure_swap_tmp_index';

        if ($this->indexColumn($table, $a) === 'measure_id') {
            Schema::table($table, fn (Blueprint $t) => $t->renameIndex($a, $tmp));
        }
        if ($this->indexColumn($table, $b) === 'control_id') {
            Schema::table($table, fn (Blueprint $t) => $t->renameIndex($b, $a));
        }
        if ($this->indexColumn($table, $tmp) !== null) {
            Schema::table($table, fn (Blueprint $t) => $t->renameIndex($tmp, $b));
        }
    }

    /** Étape 2 pour PostgreSQL/SQLite (transactionnel) : échange des valeurs. */
    private function swapPivotValues(string $driver): void
    {
        if ($driver === 'pgsql') {
            foreach (Schema::getForeignKeys('control_measure') as $fk) {
                DB::statement("ALTER TABLE control_measure DROP CONSTRAINT \"{$fk['name']}\"");
            }
        }

        DB::statement('ALTER TABLE control_measure ADD COLUMN swap_tmp INTEGER NULL');
        DB::statement('UPDATE control_measure SET swap_tmp = control_id');
        DB::statement('UPDATE control_measure SET control_id = measure_id');
        DB::statement('UPDATE control_measure SET measure_id = swap_tmp');
        Schema::table('control_measure', fn (Blueprint $t) => $t->dropColumn('swap_tmp'));
    }

    private function foreignTable(string $table, string $column): ?string
    {
        foreach (Schema::getForeignKeys($table) as $fk) {
            if ($fk['columns'] === [$column]) {
                return $fk['foreign_table'];
            }
        }

        return null;
    }

    private function indexColumn(string $table, string $index): ?string
    {
        foreach (Schema::getIndexes($table) as $idx) {
            if (strtolower($idx['name']) === $index) {
                return $idx['columns'][0] ?? null;
            }
        }

        return null;
    }

    private function assertCounts(array $expected): void
    {
        foreach ($expected as $table => $count) {
            $actual = DB::table($table)->count();
            if ($actual !== $count) {
                throw new RuntimeException(
                    "Swap measures/controls : {$table} contient {$actual} lignes, {$count} attendues. "
                    .'Les données d\'origine sont dans les tables '.self::BACKUP_PREFIX.'*.'
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function down(): void
    {
        // Équivalent au down() précédent : l'état final de up() est identique
        // (mêmes colonnes, mêmes noms d'index, FK recréées par 000002).
        $driver = DB::getDriverName();

        Schema::disableForeignKeyConstraints();
        try {
            foreach (array_reverse(self::COLUMN_RENAMES) as [$table, $from, $to]) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $to)) {
                    Schema::table($table, fn (Blueprint $t) => $t->renameColumn($to, $from));
                }
            }

            $this->swapPivotValues($driver);

            Schema::rename('controls', 'controls_swap_tmp');
            Schema::rename('measures', 'controls');
            Schema::rename('controls_swap_tmp', 'measures');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        if ($driver === 'pgsql') {
            Schema::table('control_measure', function (Blueprint $table) {
                $table->foreign('control_id')->references('id')->on('controls');
                $table->foreign('measure_id')->references('id')->on('measures');
            });
        }
    }
};