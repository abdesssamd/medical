<?php

namespace Modules\Appointment\Services;

use Illuminate\Support\Facades\Log;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\PatientRecall;
use Modules\Appointment\Models\RecallRule;
use Modules\Appointment\Models\ReminderDispatchLog;
use Modules\ClinicalRecord\Models\ClinicalProcedure;

class RecallAutomationService
{
    public function ensureDefaultRules(): void
    {
        $defaults = [
            ['code' => 'DETARTRAGE_6M', 'label' => 'Detartrage tous les 6 mois', 'trigger_type' => 'keyword', 'trigger_value' => 'detartrage', 'interval_days' => 180],
            ['code' => 'IMPLANT_1Y', 'label' => 'Controle implant annuel', 'trigger_type' => 'implant', 'trigger_value' => 'implant', 'interval_days' => 365],
            ['code' => 'ORTHO_3M', 'label' => 'Controle ortho trimestriel', 'trigger_type' => 'keyword', 'trigger_value' => 'ortho', 'interval_days' => 90],
        ];

        foreach ($defaults as $rule) {
            RecallRule::updateOrCreate(['code' => $rule['code']], $rule + ['is_active' => true, 'channels' => ['sms']]);
        }
    }

    public function generateRecallsFromHistory(int $patientId): int
    {
        $this->ensureDefaultRules();
        $rules = RecallRule::where('is_active', true)->get();

        $procedures = ClinicalProcedure::where('patient_id', $patientId)
            ->whereIn('status', ['completed', 'in_progress'])
            ->latest('performed_at')
            ->limit(300)
            ->get();

        $created = 0;

        foreach ($rules as $rule) {
            $matched = $procedures->first(function (ClinicalProcedure $p) use ($rule): bool {
                $needle = mb_strtolower((string) ($rule->trigger_value ?? ''));
                $name = mb_strtolower((string) $p->name);
                $code = mb_strtolower((string) $p->procedure_code);

                if ($rule->trigger_type === 'implant') {
                    return str_contains($name, 'implant') || str_contains($code, 'imp');
                }

                return $needle !== '' && (str_contains($name, $needle) || str_contains($code, $needle));
            });

            if (! $matched) {
                continue;
            }

            $baseDate = $matched->performed_at ? $matched->performed_at->copy()->startOfDay() : now()->startOfDay();
            $dueDate = $baseDate->addDays((int) $rule->interval_days)->toDateString();

            $exists = PatientRecall::where('patient_id', $patientId)
                ->where('recall_rule_id', $rule->id)
                ->whereDate('due_date', $dueDate)
                ->exists();

            if ($exists) {
                continue;
            }

            PatientRecall::create([
                'patient_id' => $patientId,
                'appointment_id' => $matched->appointment_id,
                'recall_rule_id' => $rule->id,
                'reason' => $rule->label,
                'due_date' => $dueDate,
                'status' => 'pending',
                'meta' => [
                    'based_on_procedure_id' => $matched->id,
                    'procedure_code' => $matched->procedure_code,
                ],
            ]);
            $created++;
        }

        return $created;
    }

    public function dispatchAppointment24hReminders(): int
    {
        $targetDate = now()->addDay()->toDateString();

        $appointments = Appointment::with('patient')
            ->whereDate('appointment_date', $targetDate)
            ->where('status', 'booked')
            ->limit(300)
            ->get();

        $sent = 0;

        foreach ($appointments as $apt) {
            $phone = $apt->patient?->phone;
            $email = $apt->patient?->email;

            $msg = sprintf(
                'Rappel RDV demain %s a %s. Merci de confirmer votre presence.',
                $targetDate,
                substr((string) $apt->start_time, 0, 5)
            );

            if ($phone) {
                ReminderDispatchLog::create([
                    'patient_id' => $apt->patient_id,
                    'appointment_id' => $apt->id,
                    'channel' => 'sms',
                    'context' => 'appointment_24h',
                    'target' => $phone,
                    'status' => 'sent',
                    'payload' => ['message' => $msg],
                    'sent_at' => now(),
                ]);
                $sent++;
            }

            if ($email) {
                ReminderDispatchLog::create([
                    'patient_id' => $apt->patient_id,
                    'appointment_id' => $apt->id,
                    'channel' => 'email',
                    'context' => 'appointment_24h',
                    'target' => $email,
                    'status' => 'sent',
                    'payload' => ['message' => $msg],
                    'sent_at' => now(),
                ]);
                $sent++;
            }

            Log::info('appointment.reminder_24h.sent', ['appointment_id' => $apt->id, 'message' => $msg]);
        }

        return $sent;
    }

    public function dispatchDueRecalls(): int
    {
        $due = PatientRecall::with('patient')
            ->where('status', 'pending')
            ->whereDate('due_date', '<=', now()->toDateString())
            ->limit(200)
            ->get();

        $sent = 0;

        foreach ($due as $recall) {
            $target = $recall->patient?->phone ?: $recall->patient?->email;
            $channel = $recall->patient?->phone ? 'sms' : 'email';

            if (! $target) {
                continue;
            }

            $msg = sprintf('Rappel prevention: %s. Merci de contacter le cabinet pour planifier.', $recall->reason);

            ReminderDispatchLog::create([
                'patient_id' => $recall->patient_id,
                'appointment_id' => null,
                'patient_recall_id' => $recall->id,
                'channel' => $channel,
                'context' => 'recall',
                'target' => $target,
                'status' => 'sent',
                'payload' => ['message' => $msg],
                'sent_at' => now(),
            ]);

            $recall->update([
                'last_notified_at' => now()->toDateString(),
                'status' => 'notified',
            ]);

            $sent++;
        }

        return $sent;
    }
}
