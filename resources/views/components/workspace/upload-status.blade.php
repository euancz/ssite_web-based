@props(['inputId', 'maxKb'])
<div class="workspace-upload-status" data-upload-status data-upload-input="{{ $inputId }}" data-upload-max-kb="{{ $maxKb }}" hidden>
    <p id="{{ $inputId }}-selection" class="profile-field-help" data-upload-summary role="status" aria-live="polite"></p>
    <button type="button" class="dashboard-button dashboard-button-secondary" data-upload-clear hidden>Clear selection</button>
</div>
