@php
// Variables expected:
// $field        - DB field name
// $label        - Display label
// $value        - Formatted display value (string|null)
// $rawValue     - (optional) raw value for the input (defaults to $value)
// $editable     - bool, whether this field can be edited
// $type         - 'text'|'date'|'select'|'phone'|'badge'|'geo_country'|'geo_state'|'geo_city' (default 'text')
// $selectOptions- array for type=select
// $isLink       - bool, render as anchor

$rawValue    = $rawValue    ?? $value;
$type        = $type        ?? 'text';
$editable    = $editable    ?? false;
$isLink      = $isLink      ?? false;
$selectOptions = $selectOptions ?? [];
@endphp

<div class="detail-row" id="field-row-{{ $field }}">
    <span class="detail-label">{{ $label }}</span>
    <div class="detail-value" id="field-show-{{ $field }}">
        <span id="field-display-{{ $field }}">
            @if ($isLink && $value)
                <a href="{{ $value }}" target="_blank" rel="noopener" style="color:var(--blue-text);word-break:break-all;">{{ $value }}</a>
            @else
                {{ $value ?: '—' }}
            @endif
        </span>
    </div>
    @if ($editable)
    <i class="bi bi-pencil-fill edit-pencil" onclick="startFieldEdit('{{ $field }}')" title="Edit {{ $label }}"></i>
    @endif
</div>

@if ($editable)
<div class="inline-edit-wrap preview-inline-editor preview-inline-editor-{{ $type }}" id="field-edit-{{ $field }}" style="display:none;">
    @if ($type === 'select')
        <select class="inline-edit-input" id="input-{{ $field }}">
            @foreach ($selectOptions as $optVal => $optLabel)
                <option value="{{ $optVal }}" @selected((string)$rawValue === (string)$optVal)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'phone')
        <input type="hidden" id="input-{{ $field }}-cc" value="+1">
        <input type="tel" class="inline-edit-input js-phone-input" id="input-{{ $field }}" data-cc-target="input-{{ $field }}-cc" value="{{ $rawValue }}">
    @elseif ($type === 'badge')
        <input type="hidden" class="preview-badge-hidden" id="input-{{ $field }}" value="{{ $rawValue }}">
        <div class="subdomain-badge-wrap preview-badge-wrap" id="badge-wrap-{{ $field }}"
             onclick="var i=document.getElementById('badge-input-{{ $field }}');if(i)i.focus();">
            <input type="text" id="badge-input-{{ $field }}"
                   class="subdomain-text-input"
                   placeholder="Type and press Enter"
                   onkeydown="handlePreviewBadgeKey(event,'{{ $field }}')">
        </div>
    @elseif ($type === 'geo_country')
        <select class="inline-edit-input js-geo-select preview-geo-select" id="preview_{{ $field }}"
                data-placeholder="Select Country" onchange="onCandidateCountryChange('preview')">
            <option value="">Select Country</option>
        </select>
    @elseif ($type === 'geo_state')
        <select class="inline-edit-input js-geo-select preview-geo-select" id="preview_{{ $field }}"
                data-placeholder="Select Country First" onchange="onCandidateStateChange('preview')">
            <option value="">Select Country First</option>
        </select>
    @elseif ($type === 'geo_city')
        <select class="inline-edit-input js-geo-select preview-geo-select" id="preview_{{ $field }}"
                data-placeholder="Select State First">
            <option value="">Select State First</option>
        </select>
    @elseif ($type === 'date')
        <input type="date" class="inline-edit-input" id="input-{{ $field }}" value="{{ $rawValue }}">
    @else
        <input type="text" class="inline-edit-input" id="input-{{ $field }}" value="{{ $rawValue }}">
    @endif
    <div class="preview-inline-actions">
        <button class="inline-edit-btn inline-edit-save" onclick="saveFieldEdit('{{ $field }}')">Save</button>
        <button class="inline-edit-btn inline-edit-cancel" onclick="cancelFieldEdit('{{ $field }}')">Cancel</button>
    </div>
</div>
@endif
