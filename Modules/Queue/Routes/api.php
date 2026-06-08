<?php

use Illuminate\Support\Facades\Route;
use Modules\Queue\Http\Controllers\TicketController;
use Modules\Queue\Http\Controllers\VoiceController;

Route::middleware('api')->group(function (): void {
Route::get('/display/{organization}/status', [TicketController::class, 'publicStatus'])->name('api.display.status');
Route::get('/display/code/{code}/status', [TicketController::class, 'publicStatusByCode'])->name('api.display.status.code');
Route::get('/tickets/track/{publicCode}/status', [TicketController::class, 'trackStatus'])->name('api.tickets.track.status');
Route::get('/voice/tickets/{ticket}', [VoiceController::class, 'ticket'])->name('api.voice.ticket');
Route::get('/voice/organizations/{organizationId}/last-call', [VoiceController::class, 'lastCall'])->name('api.voice.last-call');
});
