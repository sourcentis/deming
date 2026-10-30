<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiSubjects;
use App\Services\AiAgentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * AI analysis of a security measure, a control, an action plan or a risk.
 * The registry (AiSubjects) holds every per-type difference.
 */
class AiController extends Controller
{
    public function analyze(Request $request, AiAgentService $service, string $type, int $id): JsonResponse
    {
        $object = $this->subject($type, $id);

        try {
            $result = $service->analyze($type, $object, $request->boolean('force'));
        } catch (AiAgentException $e) {
            // Generic message only, never the agent's response
            return response()->json(['ok' => false, 'error' => $e->translatedMessage()], $e->status);
        }

        return response()->json(['ok' => true] + $result);
    }

    // Context exactly as it would be sent, without the prompt and without calling the agent
    public function preview(string $type, int $id): JsonResponse
    {
        $object = $this->subject($type, $id);

        return response()->json(['ok' => true, 'context' => AiSubjects::context($type, $object)]);
    }

    // Server-side checks, in this order: AI enabled, role, object exists, access rule
    private function subject(string $type, int $id): Model
    {
        abort_if(! Configuration::aiEnabled(), Response::HTTP_NOT_FOUND, '404 Not Found');

        $user = Auth::user();
        abort_if($user->isAuditor() || $user->isAPI(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $object = AiSubjects::find($type, $id);
        abort_if($object === null, Response::HTTP_NOT_FOUND, '404 Not Found');

        abort_if(! AiSubjects::canUseAi($user, $type, $object), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return $object;
    }
}
