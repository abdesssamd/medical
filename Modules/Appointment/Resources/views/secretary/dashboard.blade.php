@extends('layouts.admin')

@section('title', 'Dashboard Secretariat RDV')
@section('page_pretitle', 'Role Secretaire')
@section('page_title', 'Pilotage Accueil et Planning')

@section('content')
@php
    $statusCounts = $todayAppointments->groupBy('status')->map->count();
@endphp

<div class="row row-cards mb-2">
    <div class="col-md-3"><a class="card card-link" href="{{ route('care.module2.index') }}"><div class="card-body">Salle d'attente live</div></a></div>
    <div class="col-md-3"><a class="card card-link" href="{{ route('tickets.create') }}"><div class="card-body">Billetterie patient</div></a></div>
    <div class="col-md-3"><a class="card card-link" href="{{ route('care.module4.index') }}"><div class="card-body">Sterile et labo</div></a></div>
    <div class="col-md-3"><a class="card card-link" href="{{ route('appointment.sec.dashboard') }}"><div class="card-body">Planning du jour</div></a></div>
</div>

<div class="row row-cards">
    <div class="col-md-4">
        <x-tabler-card title="Patients du jour">
            <div class="h1 mb-1">{{ $summary['today_total'] }}</div>
            <x-tabler-status status="En attente" />
        </x-tabler-card>
    </div>
    <div class="col-md-4">
        <x-tabler-card title="Consultes">
            <div class="h1 mb-1">{{ $summary['today_consulted'] }}</div>
            <x-tabler-status status="Termine" />
        </x-tabler-card>
    </div>
    <div class="col-md-4">
        <x-tabler-card title="Commissions cumulees">
            <div class="h1 mb-1">{{ number_format($summary['month_commissions'], 2, ',', ' ') }} MAD</div>
            <x-tabler-status status="En attente" />
        </x-tabler-card>
    </div>
</div>

<div class="row row-cards mt-2">
    <div class="col-md-3"><x-tabler-card title="booked"><div class="h3 mb-0">{{ $statusCounts['booked'] ?? 0 }}</div></x-tabler-card></div>
    <div class="col-md-3"><x-tabler-card title="arrived"><div class="h3 mb-0">{{ $statusCounts['arrived'] ?? 0 }}</div></x-tabler-card></div>
    <div class="col-md-3"><x-tabler-card title="consulted"><div class="h3 mb-0">{{ $statusCounts['consulted'] ?? 0 }}</div></x-tabler-card></div>
    <div class="col-md-3"><x-tabler-card title="cancelled/no_show"><div class="h3 mb-0">{{ ($statusCounts['cancelled'] ?? 0) + ($statusCounts['no_show'] ?? 0) }}</div></x-tabler-card></div>
</div>

<div class="row row-cards mt-2">
    <div class="col-12">
        <x-tabler-card title="Rendez-vous du jour">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Patient</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayAppointments as $rdv)
                            <tr>
                                <td>{{ substr($rdv->start_time, 0, 5) }}</td>
                                <td>{{ $rdv->patient_name }}</td>
                                <td><x-tabler-status :status="$rdv->status" /></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('care.module2.index', ['date' => $rdv->appointment_date?->toDateString(), 'professional_id' => $professionalId]) }}">Suivre flux</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary">Aucun rendez-vous.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-tabler-card>
    </div>
</div>
@endsection

