@extends('layouts.app')

@section('title', 'Imagerie Médicale - ' . $patient->full_name)
@section('page-title', 'Imagerie Médicale : ' . $patient->full_name)

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>📷 Imagerie Médicale</h2>
            <p class="muted">
                {{ $patient->full_name }} ({{ $patient->medical_record_number }})
            </p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">← Dossier</a>
        </div>
    </section>

    {{-- Filtres --}}
    <section class="card">
        <form method="GET" class="form-row">
            <div>
                <label class="label">Type d'image</label>
                <select class="select" name="type">
                    <option value="">Tous</option>
                    <option value="xray">🩻 Radio (X-Ray)</option>
                    <option value="cbct">🧊 CBCT (3D)</option>
                    <option value="intraoral_photo">📷 Photo intra-orale</option>
                    <option value="stl_scan">🦷 Scan STL</option>
                    <option value="dicom">💿 DICOM</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit" style="align-self: flex-end;">🔍 Filtrer</button>
        </form>
    </section>

    {{-- Galerie d'images --}}
    <section class="card">
        <h3 class="card-title">📁 Images ({{ $images->total() }})</h3>
        
        @if($images->isEmpty())
            <div class="empty-state">
                <p>Aucune image médicale pour ce patient.</p>
            </div>
        @else
            <div class="images-gallery">
                @foreach($images as $image)
                <div class="image-card" onclick="openImageModal({{ $image->id }})">
                    <div class="image-thumbnail image-type-{{ $image->type }}">
                        <div class="image-icon">
                            @if($image->type === 'dicom')
                                💿
                            @elseif($image->type === 'cbct')
                                🧊
                            @elseif($image->type === 'stl_scan')
                                🦷
                            @elseif($image->type === 'xray')
                                🩻
                            @else
                                📷
                            @endif
                        </div>
                        <div class="image-type-badge">{{ $image->type }}</div>
                    </div>
                    <div class="image-details">
                        <div class="image-date">{{ $image->taken_at?->format('d/m/Y') ?? '-' }}</div>
                        @if($image->associated_teeth)
                            <div class="image-teeth">
                                Dents: {{ implode(', ', $image->associated_teeth) }}
                            </div>
                        @endif
                        @if($image->file_size)
                            <div class="image-size">{{ $image->human_readable_size }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-2">
                {{ $images->links() }}
            </div>
        @endif
    </section>
</div>

@push('head')
<style>
    .images-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: var(--spacing-lg);
    }

    .image-card {
        background: var(--color-white);
        border: 1px solid var(--color-gray-200);
        border-radius: var(--radius-lg);
        overflow: hidden;
        cursor: pointer;
        transition: all var(--transition-fast);
    }

    .image-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .image-thumbnail {
        height: 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .image-icon {
        font-size: 3.5rem;
        margin-bottom: var(--spacing-sm);
    }

    .image-type-badge {
        position: absolute;
        bottom: var(--spacing-sm);
        right: var(--spacing-sm);
        padding: 0.15rem 0.5rem;
        background: rgba(255, 255, 255, 0.9);
        border-radius: var(--radius-full);
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .image-type-xray { background: linear-gradient(135deg, #e0f2fe, #bae6fd); }
    .image-type-cbct { background: linear-gradient(135deg, #ede9fe, #ddd6fe); }
    .image-type-intraoral_photo { background: linear-gradient(135deg, #fef3c7, #fde68a); }
    .image-type-stl_scan { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }
    .image-type-dicom { background: linear-gradient(135deg, #fee2e2, #fecaca); }

    .image-details {
        padding: var(--spacing-sm) var(--spacing-md);
        font-size: 0.85rem;
    }

    .image-date {
        font-weight: 600;
        color: var(--color-gray-700);
        margin-bottom: var(--spacing-xs);
    }

    .image-teeth {
        color: var(--color-gray-500);
        font-size: 0.8rem;
    }

    .image-size {
        color: var(--color-gray-400);
        font-size: 0.75rem;
        font-family: var(--font-mono);
    }

    @media (max-width: 768px) {
        .images-gallery { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
        .images-gallery { grid-template-columns: 1fr; }
    }
</style>
@endpush
@endsection
