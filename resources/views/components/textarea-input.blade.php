@props(['disabled' => false, 'rows' => 3])

<textarea @disabled($disabled) rows="{{ $rows }}" {{ $attributes->merge(['class' => 'form-textarea']) }}>{{ $slot }}</textarea>
