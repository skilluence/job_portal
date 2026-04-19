@php
// Variables expected:
// $field        - DB field name
// $label        - Display label
// $value        - Formatted display value (string|null)
// $rawValue     - (optional) raw value for the input (defaults to $value)
// $editable     - bool, whether this field can be edited
// $type         - 'text'|'date'|'select' (default 'text')
// $selectOptions- array for type=select
// $isLink       - bool, render as anchor

$rawValue    = $rawValue    ?? $value;
$type        = $type        ?? 'text';
$editable    = $editable    ?? false;
$isLink      = $isLink      ?? false;
$selectOptions = $selectOptions ?? [];
@endphp

<div class="detail-row">
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
<div class="inline-edit-wrap" id="field-edit-{{ $field }}" style="display:none;padding-left:128px;margin-top:-4px;margin-bottom:4px;">
    @if ($type === 'select')
        <select class="inline-edit-input" id="input-{{ $field }}">
            @foreach ($selectOptions as $optVal => $optLabel)
                <option value="{{ $optVal }}" @selected((string)$rawValue === (string)$optVal)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'date')
        <input type="date" class="inline-edit-input" id="input-{{ $field }}" value="{{ $rawValue }}">
    @else
        <input type="text" class="inline-edit-input" id="input-{{ $field }}" value="{{ $rawValue }}">
    @endif
    <button class="inline-edit-btn inline-edit-save" onclick="saveFieldEdit('{{ $field }}')">Save</button>
    <button class="inline-edit-btn inline-edit-cancel" onclick="cancelFieldEdit('{{ $field }}')">Cancel</button>
</div>
@endif
