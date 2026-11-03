{{-- ช่องกรอกที่เพิ่มได้หลายรายการ: @include('ebooks.partials.multi-input', ['name' => ..., 'values' => [...], 'placeholder' => ...]) --}}
@php
    $values = array_values(array_filter((array) $values, fn ($v) => trim((string) $v) !== ''));
    if (empty($values)) {
        $values = [''];
    }
@endphp

<div class="space-y-1.5" data-multi-input>
    @foreach ($values as $value)
        <div class="flex gap-1.5" data-multi-row>
            <input type="text" name="{{ $name }}[]" value="{{ $value }}" placeholder="{{ $placeholder ?? '' }}"
                class="w-full h-8 text-xs rounded-md border-gray-300">
            <button type="button" data-multi-remove title="ลบรายการ"
                class="shrink-0 h-8 w-8 rounded-md border border-gray-300 text-gray-500 hover:bg-red-50 hover:text-red-600">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    @endforeach

    <button type="button" data-multi-add
        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
        <i class="bi bi-plus-circle"></i> เพิ่ม
    </button>
</div>

@once
    <script>
        document.addEventListener('click', function (e) {
            const add = e.target.closest('[data-multi-add]');
            if (add) {
                const box = add.closest('[data-multi-input]');
                const row = box.querySelector('[data-multi-row]').cloneNode(true);
                row.querySelector('input').value = '';
                add.before(row);
                row.querySelector('input').focus();
                return;
            }

            const remove = e.target.closest('[data-multi-remove]');
            if (remove) {
                const box = remove.closest('[data-multi-input]');
                const row = remove.closest('[data-multi-row]');
                if (box.querySelectorAll('[data-multi-row]').length > 1) {
                    row.remove();
                } else {
                    row.querySelector('input').value = '';
                }
            }
        });
    </script>
@endonce
