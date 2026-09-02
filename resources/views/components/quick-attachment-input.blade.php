@props([
    'name' => 'document',
    'accept' => null,
    'required' => false,
    'multiple' => false,
    'label' => null,
])

<label {{ $attributes->class(['quick-attachment-input']) }}>
    <input
        type="file"
        name="{{ $name }}"
        @if($accept) accept="{{ $accept }}" @endif
        @if($required) required @endif
        @if($multiple) multiple @endif
        onchange="this.closest('.quick-attachment-input').querySelector('.quick-attachment-input__label').textContent = this.files.length > 1 ? this.files.length + ' files selected' : (this.files[0]?.name || '{{ $label ?: 'Attach document' }}')"
    >
    <span class="quick-attachment-input__icon" aria-hidden="true"><i class="fas fa-paperclip"></i></span>
    <span class="quick-attachment-input__label">{{ $label ?: ($multiple ? 'Attach documents' : 'Attach document') }}</span>
</label>

@once
    <style>
        .quick-attachment-input {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            margin: 0;
            padding: 8px 14px;
            border: 1px solid var(--quick-shell-border, #d9e2ef);
            border-radius: 12px;
            background: var(--quick-shell-card, #fff);
            color: var(--quick-shell-ink, #142033);
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .quick-attachment-input:hover,
        .quick-attachment-input:focus-within {
            border-color: var(--quick-blue, #1769ff);
            box-shadow: 0 0 0 3px rgba(23, 105, 255, .1);
        }
        .quick-attachment-input input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }
        .quick-attachment-input__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(23, 105, 255, .1);
            color: var(--quick-blue, #1769ff);
            flex: 0 0 auto;
        }
        .quick-attachment-input__label {
            min-width: 0;
            overflow: hidden;
            font-size: 13px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endonce
