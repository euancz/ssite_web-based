@props(['module', 'post' => null, 'draftValues' => []])
@if (\App\Support\ContentCategories::available($module))
    @php
        $value = old('category', $draftValues['category'] ?? $post?->category);
        $options = \App\Support\ContentCategories::options($module);
        if ($value && ! isset($options[$value])) $options[$value] = $value;
    @endphp
    <div class="dashboard-form-field">
        <label for="{{ $module }}-category">Category</label>
        <select id="{{ $module }}-category" name="category" @error('category') aria-invalid="true" aria-describedby="{{ $module }}-category-error" @enderror>
            <option value="">Not assigned</option>
            @foreach ($options as $key => $label)
                <option value="{{ $key }}" @selected($value === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category')<span id="{{ $module }}-category-error" class="profile-field-error">{{ $message }}</span>@enderror
    </div>
@endif
