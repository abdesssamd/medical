<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\RisAiService;

class RisAiController extends Controller
{
    public function analyze(Request $request, RisOrder $order, RisAiService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'mode' => ['nullable', 'string', 'in:correction,pre_report,summary'],
        ]);

        return response()->json(
            $aiService->analyze($order, $validated['content'], $validated['mode'] ?? 'correction')
        );
    }
}