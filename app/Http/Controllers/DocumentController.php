<?php

namespace App\Http\Controllers;

// Models
use App\Models\Document;
use App\Models\Measure;
use App\Services\Report\ReportTableRegistry;
use App\Services\Report\TemplateTableRenderer;
// Framework
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function getTemplate(Request $request)
    {
        //  Get document teample id
        $id = (int) $request->get('id');

        if ($id === 1) {
            // return default model
            return response()->download(storage_path('app/models/control_'. Auth::user()->language .'.docx'));
        }
        if ($id === 2) {
            // check exists new model
            if (file_exists(storage_path('app/models/control_.docx'))) {
                // return new model
                return response()->download(storage_path('app/models/control_.docx'));
            }
        }
        if ($id === 3) {
            // return default model
            return response()->download(storage_path('app/models/pilotage_'. Auth::user()->language .'.docx'));
        }
        if ($id === 4) {
            // check exists new model
            if (file_exists(storage_path('app/models/pilotage_.docx'))) {
                // return new model
                return response()->download(storage_path('app/models/pilotage_.docx'));
            }
        }
        return null;
    }

    public function saveTemplate(Request $request)
    {
        // Only for administrator
        abort_if(
            (Auth::user()->role !== 1),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $messages = Collect();

        if ($request->has('template1')) {
            // Get image file
            $template = $request->file('template1');
            // Upload image
            $template->storeAs('models', 'control_.docx');

            $messages->push('Template updated !');
        }

        if ($request->has('template2')) {
            $request->validate([
                'template2' => ['file', 'mimes:docx', 'max:10240'],
            ]);
            $template = $request->file('template2');

            // Vérifie les balises de tableau ${table:...} avant d'accepter le modèle
            $errors = (new TemplateTableRenderer())->validate($template->getRealPath());
            if ($errors !== []) {
                return redirect('/config/parameters?tab=templates')
                    ->withErrors(['template2' => $errors]);
            }

            $template->storeAs('models', 'pilotage_.docx');

            $messages->push(trans('report_template.upload_success'));
        }

        return redirect('/config/parameters?tab=templates')
            ->with('messages', $messages->all());
    }

    public function get(int $id)
    {
        // Not for API
        abort_if(
            (Auth::user()->role === 4),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $document = Document::Find($id);

        // Document not found
        abort_if($document === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        // May get documents from assigned measures only, unless seeing all data
        abort_if(
            (!Auth::user()->seesAllData()) &&
            ! DB::table('measure_user')
                ->where('user_id', Auth::user()->id)
                ->where('measure_id', $document->measure_id)
                ->exists(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // get path
        $path = storage_path('docs/' . $id);

        // check file exists
        abort_if(! file_exists($path), Response::HTTP_NOT_FOUND, '404 Not Found');

        // get file content
        $file_contents = file_get_contents($path);

        return response($file_contents)
            ->header('Cache-Control', 'no-cache private')
            ->header('Content-Description', 'File Transfer')
            ->header('Content-Type', $document->mimetype)
            ->header('Content-length', (string) strlen($file_contents))
            ->header('Content-Disposition', 'attachment; filename="' . $document->filename .'"')
            ->header('Content-Transfer-Encoding', 'binary');
    }

    public function store(Request $request)
    {
        // Not for API
        abort_if((Auth::user()->isAPI()), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Get file
        $file = $request->file('file');

        // Get Measure (audit instance)
        $measure_id = $request->get('control');

        // May save document to assigned measure only, unless seeing all data
        abort_if(
            !Auth::user()->seesAllData() &&
            ! (DB::table('measure_user')
                    ->where('measure_id', $measure_id)
                    ->where('user_id', Auth::user()->id)
                    ->exists()
                ||
                DB::table('measure_user_group')
                    ->join('user_user_group', 'measure_user_group.user_group_id', '=', 'user_user_group.user_group_id')
                    ->where('measure_user_group.measure_id', $measure_id)
                    ->where('user_user_group.user_id', Auth::user()->id)
                    ->exists()),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Calculate file hash
        $hash = hash_file('sha256', $file->path());

        // Check if a document with this hash already exists
        $existingDocument = Document::where('hash', $hash)->first();

        // Create new document record
        $doc = new Document();
        $doc->measure_id = $measure_id;
        $doc->filename = $file->getClientOriginalName();
        $doc->mimetype = $file->getClientMimeType();
        $doc->size = $file->getSize();
        $doc->hash = $hash;
        $doc->save();

        $newPath = storage_path('docs/' . $doc->id);

        $deduplicated = false;
        if ($existingDocument !== null) {
            // Reuse existing file - create hard link
            $existingPath = storage_path('docs/' . $existingDocument->id);

            if (file_exists($existingPath)) {
                if (link($existingPath, $newPath)) {
                    $deduplicated = true;
                } else {
                    // Fallback: hard-link failed (e.g., different filesystem)
                    $file->move(storage_path('docs'), (string) $doc->id);
                    }
            } else {
                // Fallback: if existing file is missing, store the uploaded file
                $file->move(storage_path('docs'), (string) $doc->id);
            }
        } else {
            // New file - store it
            $file->move(storage_path('docs'), (string) $doc->id);
        }

        // response
        return response()->json(
            [
                'success' => $doc->filename,
                'id' => $doc->id,
                'deduplicated' => $deduplicated,
            ]
        );
    }

    public function delete(int $id)
    {
        // Not for API and Auditor
        abort_if(
            (Auth::user()->role === 3) ||
            (Auth::user()->role === 4),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Find the document
        $document = Document::Find($id);

        if ($document === null) {
            return response()
                ->json(
                    ['errorMessage' => 'File not found !']
                );
        }

        // May delete documents from assigned measures when not yet realised,
        // unless seeing all data
        abort_if(
            (!Auth::user()->seesAllData()) &&
            ! DB::table('measure_user')
                ->where('user_id', Auth::user()->id)
                ->where('measure_id', $document->measure_id)
                ->leftjoin('measures', 'measures.id', '=', 'measure_user.measure_id')
                ->whereNull('measures.realisation_date')
                ->exists(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $path = storage_path('docs/'.$document->id);

        if (file_exists($path)) {
            unlink($path);
        }
        $document->delete();

        return null;
    }

    // Data of the "Documents" tab of the settings page
    public function tabData(): array
    {
        return [
            'count' => Document::count(),
            'sum' => Document::sum('size'),
            'duration' => config('deming.cleanup-duration'),
            'reportTables' => ReportTableRegistry::describe(),
        ];
    }

    public function check()
    {
        abort_if(
            ! Auth::user()->isAdmin(),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        $documents = Document::all()->map(function (Document $doc): Document {
            $filePath = storage_path('docs/' . $doc->id);
            $fileExists = file_exists($filePath);

            $doc->file_exists = $fileExists;
            $doc->link_count = 0;
            $doc->hash_valid = false;

            if ($fileExists) {
                $stats = stat($filePath);
                if ($stats !== false) {
                    $doc->link_count = $stats['nlink'];
                }
                $computedHash = hash_file('sha256', $filePath);
                $doc->hash_valid = $computedHash !== false
                    && hash_equals($doc->hash, $computedHash);
            }

            return $doc;
        });

        return view('/documents/check')
            ->with('documents', $documents);
    }

    public function saveConfig(Request $request)
    {
        // Only for administrator
        abort_if(
            (Auth::user()->role !== 1),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden'
        );

        // Get duration from request
        $duration = $request->get('duration');

        // Get action
        $action = $request->input('action');

        // Act
        if ($action === 'save') {
            // Set duration
            config(['deming.cleanup-duration' => $duration]);

            // Save configuration
            $text = '<?php return ' . var_export(config('deming'), true) . ';';
            file_put_contents(config_path('deming.php'), $text);

            // Message
            $messages = Collect(['Configuration saved !']);
        } elseif (($action === 'test') && ($duration > 0)) {
            $dateLimit = Carbon::now()->subMonths($duration)->toDateString();

            $result = Measure::cleanup($dateLimit, true);

            $messages = Collect(
                [
                    "{$result['logCount']} log(s) will be deleted.",
                    "{$result['documentCount']} document(s) will be deleted.",
                    "{$result['measureCount']} measure(s) will be deleted.",
                ]
            );
        } elseif (($action === 'delete') && ($duration > 0)) {
            $dateLimit = Carbon::now()->subMonths($duration)->toDateString();

            $result = Measure::cleanup($dateLimit, false);

            $messages = Collect(
                [
                    "{$result['logCount']} log(s) deleted.",
                    "{$result['documentCount']} document(s) deleted.",
                    "{$result['measureCount']} measure(s) deleted.",
                ]
            );
        } else {
            $messages = null;
        }

        // Back to the "Documents" tab, keeping the selected duration
        return redirect('/config/parameters?tab=documents')
            ->withInput()
            ->with('messages', $messages?->all());
    }
}
