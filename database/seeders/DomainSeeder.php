<?php

namespace Database\Seeders;

use App\Models\Framework;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lang = getenv('LANG') ?: config('app.locale', 'en');
        $lang = strtolower(substr($lang, 0, 2));

        $filename = $lang === 'fr'
            ? 'database/data/domains.fr.csv'
            : 'database/data/domains.en.csv';

        $csvFile = fopen(base_path($filename), 'r');
        if ($csvFile === false) {
            throw new \RuntimeException("Cannot open seed file: {$filename}");
        }

        $domains = [];
        $firstline = true;
        try {
            while (($data = fgetcsv($csvFile, 2000, ',')) !== false) {
                if (! $firstline) {
                    $framework = $data[2] ?? null;
                    $domains[] = [
                        'id' => (int) $data[0],
                        'title' => $data[1],
                        'framework' => $framework,
                        'description' => $data[3] ?? $framework,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $firstline = false;
            }
        } finally {
            fclose($csvFile);
        }

        DB::table('domains')->upsert(
            $domains,
            ['id'],
            ['title', 'framework', 'description', 'updated_at']
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('domains', 'id'), "
                .'COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM domains'
            );
        }

        if (Schema::hasTable('frameworks')) {
            DB::table('domains')
                ->whereNotNull('framework')
                ->where('framework', '<>', '')
                ->distinct()
                ->pluck('framework')
                ->each(fn ($framework) => Framework::ensureForDomainCode((string) $framework));
        }
    }
}
