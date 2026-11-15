<?php

namespace App\Http\Controllers;

use App\Models\Control;
use App\Models\Document;
use App\Models\Domain;
use App\Models\Measure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MeasureImportController extends Controller
{
    /**
     * Show Import Measure screen
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        // Only for Administrator
        abort_if(Auth::user()->role !== 1, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $models = Storage::disk('local')->files('repository');

        // filter .xlsx files
        $models = array_filter(
            $models,
            fn ($str) => str_ends_with($str, '.xlsx')
        );

        return view('controls.import')
            ->with('models', $models);
    }

    /**
     * Download Measures
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */

    public function download(Request $request)
    {
        abort_if(Auth::user()->role !== 1, Response::HTTP_FORBIDDEN, '403 Forbidden');

        \Log::debug('download called');

        $request->validate([
            'model' => 'required|string',
        ]);

        $model = '/' . $request->get('model') . '.xlsx';
        $file = current(
            array_filter(
                Storage::disk('local')->files('repository'),
                fn ($e) => str_contains($e, $model)
            )
        );

        if (! $file) {
            abort(404, 'Model not found');
        }

        $fileName = Storage::disk('local')->path($file);

        return response()->download(
            $fileName,
            $request->get('model') . '.xlsx',
            ['Content-Type' => 'application/octet-stream']
        );
    }

    /**
     * Import Measures
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function import(Request $request)
    {
        // Only for Administrator
        abort_if(Auth::user()->role !== 1, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'file' => 'required_without:model|mimes:xls,xlsx',
            'model' => 'required_without:file',
        ]);

        $errors = Collect();
        $messages = Collect();

        try {
            // Get Filename
            $fileName = null;
            if ($request->file()) {
                // Save temp file
                $fileName = Storage::path($request->file('file')->store());
            } else {
                // Find file from repositories
                $model = '/' . $request->get('model') . '.xlsx';
                $file = current(
                    array_filter(
                        Storage::disk('local')->files('repository'),
                        function ($e) use ($model) {
                            return str_contains($e, $model);
                        }
                    )
                );
                // Get full path
                $fileName = Storage::disk('local')->path($file);
            }

            // XLSX
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($fileName);

            $sheet = $spreadsheet->getSheet($spreadsheet->getFirstSheetIndex());
            $data = $sheet->toArray();

            if ($this->canImportFromFile($data, $request->has('clean'), $errors)) {
                // Clear database
                if ($request->has('clean')) {
                    $this->clean();
                    $messages->push('Database cleared');
                }
                $this->importFromFile($data, $messages);
            }
        } finally {
            if ($request->file() && ($fileName !== null) && file_exists($fileName)) {
                unlink($fileName);
            }
        }

        // Generate fake test data
        if ($request->has('test')) {
            // Call command
            Artisan::call('deming:generate-tests');
            $messages->push('Test data generated');
        }

        return back()
            ->with('errors', $errors)
            ->with('messages', $messages)
            ->with('file', $fileName);
    }

    /**
     * Check Measures from an XLSX file
     *
     * @return Bool
     */
    public function canImportFromFile(
        array $data,
        bool $clear,
        Collection $errors
    ): bool {
        /*
        +-------------+---------------+------+-----+---------+----------------+
        | Field       | Type          | Null | Key | Default | Extra          |
        +-------------+---------------+------+-----+---------+----------------+
      0 | framework | varchar(32)  | NO   | MUL | NULL     |                |
      1 | domain.name | varchar(32)  | NO   | MUL | NULL     |                |
      2 | domain.desc | varchar(255)  | NO   | MUL | NULL    |                |
      3 | clause      | varchar(32)   | NO   |     | NULL    |                |
      4 | name        | varchar(255)  | NO   |     | NULL    |                |
      5 | objective   | text          | YES  |     | NULL    |                |
      6 | attributes  | varchar(1024) | YES  |     | NULL    |                |
      7 | input       | text          | YES  |     | NULL    |                |
      8 | model       | text          | YES  |     | NULL    |                |
      9 | indicator   | text          | YES  |     | NULL    |                |
      10 | action_plan | text          | YES  |     | NULL    |                |
        +-------------+---------------+------+-----+---------+----------------+
        */

        $data = self::trimClauses($data);

        // Check for errors
        $lastLine = count($data);

        for ($line = 1; $line < $lastLine; $line++) {
            if ($errors->count() > 10) {
                $errors->push('too many errors...');
                break;
            }

            if (($data[$line][0] === null)) {
                $errors->push(($line + 1) . ': framework name is empty');
                continue;
            }
            if (($data[$line][1] === null)) {
                $errors->push(($line + 1) . ': domain name is empty');
                continue;
            }
            if (($data[$line][3] === null)) {
                $errors->push(($line + 1) . ': close is empty');
                continue;
            }
            // delete line ?
            if (
                ($data[$line][4] === null) &&
                ($data[$line][5] === null) &&
                ($data[$line][6] === null) &&
                ($data[$line][7] === null) &&
                ($data[$line][8] === null) &&
                ($data[$line][9] === null) &&
                ($data[$line][10] === null)
            ) {
                continue;
            }
            if (strlen($data[$line][0]) >= 32) {
                $errors->push(($line + 1) . ': framework name is too long');
                continue;
            }
            if (strlen($data[$line][1]) >= 32) {
                $errors->push(($line + 1) . ': domain name is too long');
                continue;
            }
            if (strlen($data[$line][2]) >= 255) {
                $errors->push(($line + 1) . ': domain description is too long');
                continue;
            }
            if (strlen($data[$line][3]) === 0) {
                $errors->push(($line + 1) . ': close name is empty');
                continue;
            }
            if (strlen($data[$line][3]) >= 32) {
                $errors->push(($line + 1) . ': close name too long');
                continue;
            }
            if (! $clear && Control::where('clause', $data[$line][3])->exists()) {
                $errors->push(($line + 1) . ': close name not unique');
                continue;
            }
            if (strlen($data[$line][4]) === 0) {
                $errors->push(($line + 1) . ': name is empty');
                continue;
            }
            if (strlen($data[$line][4]) >= 255) {
                $errors->push(($line + 1) . ': name too long ');
                continue;
            }
            // TODO: check tags
        }

        return $errors->count() === 0;
    }

    /**
     * Removes the spaces around the clauses: some frameworks of the repository
     * end them with a space, which MySQL ignores in comparisons but which is
     * stored and shown as is.
     *
     * @param  array<int, array<int, mixed>>  $data
     * @return array<int, array<int, mixed>>
     */
    private static function trimClauses(array $data): array
    {
        foreach ($data as $line => $row) {
            if (is_string($row[3] ?? null)) {
                $data[$line][3] = trim($row[3]);
            }
        }

        return $data;
    }

    /**
     * Import Measures from an XLSX file
     */
    public function importFromFile(
        array $data,
        Collection $messages
    ) {
        // Initialize counters
        $deleteCount = 0;
        $insertCount = 0;
        $updateCount = 0;
        $newDomainCount = 0;
        $deleteControlCount = 0;
        $deleteDocumentCount = 0;

        $data = self::trimClauses($data);

        // Read file
        $lastLine = count($data);
        for ($line = 1; $line < $lastLine; $line++) {
            // Update domain description ?
            // delete line ?
            if (
                ($data[$line][3] !== null) &&
                ($data[$line][4] === null) &&
                ($data[$line][5] === null) &&
                ($data[$line][6] === null) &&
                ($data[$line][7] === null) &&
                ($data[$line][8] === null) &&
                ($data[$line][9] === null) &&
                ($data[$line][10] === null)
            ) {
                // delete documents
                $documents = DB::table('documents')
                    ->join('measures', 'measures.id', '=', 'documents.measure_id')
                    ->join('controls', 'controls.id', '=', 'measures.control_id')
                    ->where('controls.clause', $data[$line][3])
                    ->select('documents.id')
                    ->get();

                foreach ($documents as $document) {
                    unlink(storage_path('docs/' . $document->id));
                    DB::table('documents')->where('id', $document->id)->delete();
                    $deleteDocumentCount++;
                }

                // Break link between audit instances
                Measure::join('controls', 'controls.id', '=', 'measures.control_id')
                    ->where('controls.clause', $data[$line][3])
                    ->update(['next_id' => null]);

                // Delete audit instances (measures)
                $oldMeasures = Measure::join('controls', 'controls.id', '=', 'measures.control_id')
                    ->where('controls.clause', $data[$line][3])
                    ->get(['measures.id']);

                Measure::destroy($oldMeasures->toArray());

                $deleteControlCount += count($oldMeasures);

                // delete security measure (control)
                Control::where('clause', $data[$line][3])->delete();

                // TODO: delete empty domains

                $deleteCount++;
                continue;
            }
            // update or insert ?
            $control = Control::where('clause', $data[$line][3])->first();

            if ($control !== null) {
                // update or create domain
                $domain = Domain
                    ::where('framework', trim($data[$line][0]))
                        ->where('title', trim($data[$line][1]))
                        ->first();
                if ($domain === null) {
                    // create domain
                    $domain = new Domain();
                    $domain->framework = trim($data[$line][0]);
                    $domain->title = trim($data[$line][1]);
                    $domain->description = trim($data[$line][2]);
                    $domain->save();

                    $newDomainCount++;
                } else {
                    $domain->description = trim($data[$line][2]);
                    $domain->update();
                }

                // update security measure (control)
                $control->name = $data[$line][4];
                $control->domain_id = $domain->id;
                $control->objective = $data[$line][5];
                $control->attributes = $data[$line][6];
                $control->input = $data[$line][7];
                $control->model = $data[$line][8];
                $control->indicator = $data[$line][9];
                $control->action_plan = $data[$line][10];
                $control->update();

                $updateCount++;
            } else {
                // insert

                // get domain id
                $domain = Domain
                    ::where('framework', trim($data[$line][0]))
                        ->where('title', trim($data[$line][1]))
                        ->first();

                if ($domain === null) {
                    // create domain
                    $domain = new Domain();
                    $domain->framework = trim($data[$line][0]);
                    $domain->title = trim($data[$line][1]);
                    $domain->description = trim($data[$line][2]);
                    $domain->save();

                    $newDomainCount++;
                } else {
                    $domain->description = trim($data[$line][2]);
                    $domain->update();
                }

                // create security measure (control)
                $control = new Control();

                $control->domain_id = $domain->id;
                $control->clause = $data[$line][3];
                $control->name = $data[$line][4];
                $control->objective = $data[$line][5];
                $control->attributes = $data[$line][6];
                $control->input = $data[$line][7];
                $control->model = $data[$line][8];
                $control->indicator = $data[$line][9];
                $control->action_plan = $data[$line][10];

                $control->save();

                $insertCount++;
            }
        }

        if ($insertCount > 0) {
            $messages->push($insertCount . ' lines inserted');
        }
        if ($updateCount > 0) {
            $messages->push($updateCount . ' lines updated');
        }
        if ($deleteCount > 0) {
            $messages->push($deleteCount . ' lines deleted');
        }
        if ($deleteControlCount > 0) {
            $messages->push($deleteControlCount . ' controls deleted');
        }
        if ($deleteDocumentCount > 0) {
            $messages->push($deleteDocumentCount . ' documents deleted');
        }
        if ($newDomainCount > 0) {
            $messages->push($newDomainCount . ' new domains created');
        }
    }

    /**
     * Truncate database
     */
    public function clean()
    {
        Schema::disableForeignKeyConstraints();

        // Delete all documents
        Document::truncate();

        // Delete all audit instances
        Measure::truncate();

        // Delete all security measures
        Control::truncate();

        // Delete all domains
        Domain::truncate();

        Schema::enableForeignKeyConstraints();
    }
}
