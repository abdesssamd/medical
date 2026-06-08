@extends('layouts.admin')

@section('title', 'Verification ordonnance')
@section('page_pretitle', 'Module 3')
@section('page_title', 'Verification QR Ordonnance')

@section('content')
<div class="card">
    <div class="card-body">
        <h3>Ordonnance verifiee</h3>
        <p><strong>Numero:</strong> {{ $prescription->prescription_number }}</p>
        <p><strong>Date:</strong> {{ optional($prescription->issued_at)->format('d/m/Y H:i') }}</p>
        <p><strong>Patient:</strong> {{ $prescription->patient?->full_name }} ({{ $prescription->patient?->medical_record_number }})</p>
        <p><strong>Praticien:</strong> {{ $prescription->practitioner?->name ?: '-' }}</p>
        <p><strong>Statut:</strong> <span class="badge bg-green-lt">{{ $prescription->status }}</span></p>
        <a class="btn btn-primary" target="_blank" href="{{ route('care.module3.prescriptions.pdf', ['prescription' => $prescription->id]) }}">Voir PDF</a>
    </div>
</div>
@endsection
