<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('deduplication_key', 64)->nullable()->after('medical_record_number');
        });

        $seenKeys = [];

        DB::table('patients')
            ->select(['id', 'phone', 'cin', 'date_of_birth'])
            ->orderBy('id')
            ->chunkById(200, function ($patients) use (&$seenKeys): void {
                foreach ($patients as $patient) {
                    $phone = preg_replace('/\D+/', '', (string) $patient->phone) ?: '';
                    $cin = strtoupper(trim((string) $patient->cin));

                    if ($phone === '' || $cin === '' || empty($patient->date_of_birth)) {
                        continue;
                    }

                    $key = hash('sha256', implode('|', [$phone, $cin, Carbon::parse($patient->date_of_birth)->toDateString()]));

                    if (isset($seenKeys[$key])) {
                        continue;
                    }

                    $seenKeys[$key] = true;

                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update(['deduplication_key' => $key]);
                }
            });

        Schema::table('patients', function (Blueprint $table): void {
            $table->unique('deduplication_key', 'patients_deduplication_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->dropUnique('patients_deduplication_key_unique');
            $table->dropColumn('deduplication_key');
        });
    }
};