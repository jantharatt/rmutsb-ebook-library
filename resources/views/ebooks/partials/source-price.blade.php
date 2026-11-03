{{-- แหล่งที่มา (จัดซื้อ / อภินันทนาการ / อื่นๆ) และราคา --}}
@php
    $presetSources = ['จัดซื้อ', 'อภินันทนาการ'];
    $currentSource = old('source', $source ?? '');
    $isOther = $currentSource !== '' && ! in_array($currentSource, array_merge($presetSources, ['อื่นๆ']), true);
    $otherText = old('source_other', $isOther ? $currentSource : '');
    $selected = $isOther ? 'อื่นๆ' : $currentSource;
@endphp

<div>
    <label class="block text-[11px] font-semibold text-gray-600 mb-1">แหล่งที่มา</label>

    <select name="source" id="source_select" class="w-full h-8 text-xs rounded-md border-gray-300"
        onchange="document.getElementById('source_other_wrap').classList.toggle('hidden', this.value !== 'อื่นๆ')">
        <option value="">เลือกแหล่งที่มา</option>
        @foreach (array_merge($presetSources, ['อื่นๆ']) as $option)
            <option value="{{ $option }}" @selected($selected === $option)>
                {{ $option === 'อื่นๆ' ? 'อื่นๆ (โปรดระบุ)' : $option }}
            </option>
        @endforeach
    </select>

    <div id="source_other_wrap" class="mt-1.5 {{ $selected === 'อื่นๆ' ? '' : 'hidden' }}">
        <input type="text" name="source_other" value="{{ $otherText }}" placeholder="โปรดระบุแหล่งที่มา"
            class="w-full h-8 text-xs rounded-md border-gray-300">
    </div>
</div>

<div>
    <label class="block text-[11px] font-semibold text-gray-600 mb-1">ราคา (บาท)</label>

    <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $price ?? '') }}" placeholder="0.00"
        class="w-full h-8 text-xs rounded-md border-gray-300">
</div>
