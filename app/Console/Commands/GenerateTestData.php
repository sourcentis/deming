<?php

namespace App\Console\Commands;

use App\Models\Control;
use App\Models\Measure;
use Carbon\Carbon;
use Database\Seeders\RiskTestDataSeeder;
use Database\Seeders\TestObservations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deming:generate-tests {--locale= : Locale of the generated test data (fr or en)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test data';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->components->info('Generate test data');

        // get language : --locale option, fallback to system LANG
        $locale = strtolower((string) $this->option('locale'));
        if (! in_array($locale, ['fr', 'en'], true)) {
            $locale = strtolower(substr((string) getenv('LANG'), 0, 2)) === 'fr' ? 'fr' : 'en';
        }

        // No security measure : import the ISO 27001:2022 framework in the selected language
        if (DB::table('controls')->count() === 0) {
            $this->components->info('No security measure found: import the ISO 27001:2022 framework');
            $this->call('deming:import-framework', [
                'filename' => storage_path('app/repository/ISO27001-2022.' . $locale . '.xlsx'),
            ]);
        }

        // Remove data in documents and measures tables
        DB::table('documents')->delete();
        DB::table('action_control')->delete();
        DB::table('action_user')->delete();
        DB::table('actions')->delete();
        DB::table('measures')->update(['next_id' => null]);
        DB::table('measure_user_group')->delete();
        DB::table('control_measure')->delete();
        DB::table('measures')->delete();

        // Get all attributes
        $attributes = [];
        $attributesDB = DB::table('attributes')
            ->select('values')
            ->get();
        foreach ($attributesDB as $attribute) {
            foreach (explode(' ', $attribute->values) as $value) {
                if (strlen($value) > 0) {
                    array_push($attributes, $value);
                }
            }
        }
        sort($attributes);

        // period in month
        $period = 12;

        // Start date
        $curDate = Carbon::now()->addMonths(-$period)->day(1);

        // get all security measures (controls)
        $securityControls = Control::All();
        $cntMeasure = DB::table('controls')->count();

        // audit instances per period
        $perPeriod = (int) ($cntMeasure / $period);

        // loop on security measures
        $delta = $perPeriod - rand(-$perPeriod / 2, $perPeriod / 2);

        // Loop on security measures
        foreach ($securityControls as $secControl) {
            $delta--;
            if ($delta <= 0) {
                // go to next period
                $curDate->addMonth();
                $delta = $perPeriod - rand(-$perPeriod / 3, $perPeriod / 3);
            }

            // create an audit instance (measure)
            $measure = new Measure();
            $measure->name = $secControl->name;
            $measure->objective = $secControl->objective;
            $measure->attributes = $secControl->attributes;
            $measure->model = $secControl->model;
            $measure->input = $secControl->input;
            $measure->action_plan = $secControl->action_plan;
            $measure->periodicity = 12;
            // do it
            $measure->plan_date = (new Carbon($curDate))->day(rand(0, 28))->toDateString();
            $measure->realisation_date = (new Carbon($curDate))->addDays(rand(0, 28))->toDateString();
            $measure->score = $this->randomScore();
            $measure->observations = TestObservations::for($secControl->clause, $measure->score, $locale);
            $measure->status = 2;
            $measure->save();
            $measure->controls()->sync([$secControl->id]);

            // create a previous audit instance
            $prev_measure = new Measure();
            $prev_measure->name = $secControl->name;
            $prev_measure->objective = $secControl->objective;
            $prev_measure->attributes = $secControl->attributes;
            $prev_measure->input = $secControl->input;
            $prev_measure->model = $secControl->model;
            $prev_measure->action_plan = $secControl->action_plan;
            $prev_measure->periodicity = 12;
            // do it
            $prev_measure->plan_date = (new Carbon($curDate))->addMonths(-$measure->periodicity)->day(rand(0, 28))->toDateString();
            $prev_measure->realisation_date = (new Carbon($curDate))->addMonths(-$measure->periodicity)->addDays(rand(0, 28))->toDateString();
            $prev_measure->score = $this->randomScore();
            $prev_measure->observations = TestObservations::for($secControl->clause, $prev_measure->score, $locale);
            $prev_measure->next_id = $measure->id;
            $prev_measure->status = 2;
            $prev_measure->save();
            $prev_measure->controls()->sync([$secControl->id]);

            // create next audit instance
            $nextMeasure = new Measure();
            $nextMeasure->name = $secControl->name;
            $nextMeasure->objective = $secControl->objective;
            $nextMeasure->attributes = $secControl->attributes;
            $nextMeasure->input = $secControl->input;
            $nextMeasure->model = $secControl->model;
            $nextMeasure->action_plan = $secControl->action_plan;
            $nextMeasure->periodicity = 12;
            // next one
            $nextMeasure->plan_date = (new Carbon($curDate))->day(rand(0, 28))->addMonths(12)->toDateString();
            // fix it
            $nextMeasure->realisation_date = null;
            $nextMeasure->score = null;
            $nextMeasure->status = 0;
            // save it
            $nextMeasure->save();
            $nextMeasure->controls()->sync([$secControl->id]);

            // link them
            $measure->next_id = $nextMeasure->id;
            $measure->update();
        }

        // Generate test data for risks, action plans and exceptions
        $this->components->info('Generate risks, action plans and exceptions test data');
        (new RiskTestDataSeeder())->run($locale);
    }

    /**
     * Draw a score: 5% non-compliant (red), 20% to improve (orange)
     * and 75% compliant (green).
     */
    private function randomScore(): int
    {
        $draw = rand(1, 100);

        if ($draw <= 5) {
            return 1;
        }

        return $draw <= 25 ? 2 : 3;
    }
}
