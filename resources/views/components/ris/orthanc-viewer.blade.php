@props([
    'studyId' => null,
    'baseUrl' => config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url'))),
])

@php
    $viewerUrl = rtrim((string) $baseUrl, '/');

    if (! empty($studyId)) {
        $viewerUrl .= '/osirix/viewer?study=' . urlencode((string) $studyId);
    }
@endphp

<div class="ris-orthanc-viewer">
    @if(! empty($studyId))
        <div class="mb-2">
            <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Lancer le Viewer</a>
        </div>
    @endif
    <iframe
        src="{{ $viewerUrl }}"
        title="Viewer Orthanc"
        width="100%"
        height="700"
        loading="lazy"
        referrerpolicy="no-referrer"
        style="border: 1px solid #d1d5db; border-radius: 0.5rem;"
    ></iframe>
</div>
