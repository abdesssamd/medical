<?php

namespace Modules\Appointment\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Commission;

class SecretaryController extends Controller
{
    public function dashboard(Request $request)
    {
        if (! in_array($request->user()?->role, ['secretary', 'professional'], true)) {
            abort(403);
        }

        $professionalId = (int) $request->integer('professional_id', $request->user()?->id);
        $today = today()->toDateString();

        $todayAppointments = Appointment::where('professional_id', $professionalId)
            ->whereDate('appointment_date', $today)
            ->orderBy('start_time')
            ->get();

        $summary = [
            'today_total' => $todayAppointments->count(),
            'today_consulted' => $todayAppointments->where('status', 'consulted')->count(),
            'month_commissions' => (float) Commission::where('professional_id', $professionalId)
                ->whereMonth('earned_on', now()->month)
                ->whereYear('earned_on', now()->year)
                ->sum('amount'),
        ];

        return view('appointment::secretary.dashboard', compact('todayAppointments', 'summary', 'professionalId'));
    }
}
