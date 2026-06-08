<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSpecialty
{
    public function handle(Request $request, Closure $next, string ...$specialties): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Authentification requise.');
        }

        $codes = collect($specialties)
            ->flatMap(fn (string $s) => str_contains($s, ',') ? explode(',', $s) : [$s])
            ->map(fn (string $s) => strtoupper(trim($s)))
            ->filter()
            ->values();

        if ($codes->isEmpty()) {
            return $next($request);
        }

        $userSpecialtyCodes = collect();

        if ($user->specialty_id) {
            $primaryCode = \App\Models\Specialty::where('id', $user->specialty_id)->value('code');
            if ($primaryCode) {
                $userSpecialtyCodes->push(strtoupper($primaryCode));
            }
        }

        if (method_exists($user, 'specialties')) {
            $pivotCodes = $user->specialties()->pluck('code')->map(fn ($c) => strtoupper($c));
            $userSpecialtyCodes = $userSpecialtyCodes->merge($pivotCodes)->unique()->values();
        }

        if ($userSpecialtyCodes->intersect($codes)->isEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Votre spécialité ne permet pas d\'accéder à ce module.',
                    'required_specialties' => $codes->all(),
                ], 403);
            }

            abort(403, 'Votre spécialité ne permet pas d\'accéder à ce module.');
        }

        return $next($request);
    }
}
