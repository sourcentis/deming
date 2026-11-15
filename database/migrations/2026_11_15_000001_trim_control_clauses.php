<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Removes the spaces around the clauses of the security measures: some
     * frameworks of the repository ended them with a space.
     */
    public function up(): void
    {
        $controls = DB::table('controls')->whereRaw('LENGTH(clause) <> LENGTH(TRIM(clause))')->get(['id', 'clause']);

        foreach ($controls as $control) {
            $clause = trim($control->clause);

            // Kept as is when the trimmed clause is already used by another measure
            $taken = DB::table('controls')
                ->where('id', '<>', $control->id)
                ->whereRaw('LENGTH(clause) = ?', [strlen($clause)])
                ->where('clause', $clause)
                ->exists();

            if (! $taken) {
                DB::table('controls')->where('id', $control->id)->update(['clause' => $clause]);
            }
        }
    }

    /**
     * The original spaces cannot be restored.
     */
    public function down(): void
    {
    }
};
