<?php

namespace Modules\Appointment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Http\Resources\CommissionResource;
use Modules\Appointment\Models\Commission;
use Modules\Appointment\Services\CommissionService;

class CommissionController extends Controller
{
    public function __construct(private readonly CommissionService $commissionService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $this->ensureProfessional($request);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from']) : now()->startOfMonth();
        $to = isset($validated['to']) ? Carbon::parse($validated['to']) : now()->endOfDay();
        $professionalId = (int) $request->user()->id;

        $totals = $this->commissionService->totalsByPeriod($professionalId, $from, $to);
        $items = Commission::where('professional_id', $professionalId)
            ->whereBetween('earned_on', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('earned_on')
            ->get();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => $totals,
            'items' => CommissionResource::collection($items),
        ]);
    }

    public function markPaid(Request $request, Commission $commission): JsonResponse
    {
        $this->ensureProfessional($request);

        if ((int) $commission->professional_id !== (int) $request->user()->id) {
            abort(403);
        }

        $commission->update([
            'status' => 'paid',
            'paid_at' => now(),
            'approved_by' => $request->user()->id,
            'notes' => $request->string('notes')->toString() ?: $commission->notes,
        ]);

        return response()->json([
            'message' => 'Commission marquée comme payée.',
            'commission' => new CommissionResource($commission->fresh()),
        ]);
    }

    private function ensureProfessional(Request $request): void
    {
        if ($request->user()?->role !== 'professional') {
            abort(403, 'Seul le professionnel peut gérer les commissions.');
        }
    }
}
