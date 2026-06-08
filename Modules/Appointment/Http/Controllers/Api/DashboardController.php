<?php

namespace Modules\Appointment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Commission;

class DashboardController extends Controller
{
    public function secretary(Request $request): JsonResponse
    {
        if (! in_array($request->user()?->role, ['secretary', 'professional'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'date' => ['nullable', 'date'],
        ]);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : now();

        $appointmentsToday = Appointment::where('professional_id', $validated['professional_id'])
            ->whereDate('appointment_date', $date->toDateString())
            ->count();

        $consultedToday = Appointment::where('professional_id', $validated['professional_id'])
            ->whereDate('appointment_date', $date->toDateString())
            ->where('status', 'consulted')
            ->count();

        $commissionsMonth = (float) Commission::where('professional_id', $validated['professional_id'])
            ->whereMonth('earned_on', $date->month)
            ->whereYear('earned_on', $date->year)
            ->sum('amount');

        return response()->json([
            'date' => $date->toDateString(),
            'appointments_today' => $appointmentsToday,
            'consulted_today' => $consultedToday,
            'commissions_month_total' => $commissionsMonth,
        ]);
    }
}
