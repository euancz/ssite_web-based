{{-- This shared Article-only form keeps create and edit validation messages consistent. --}}
<x-ui.form-errors />
@php($formattingEnabled = app(\App\Services\ArticleContent::class)->available())

{{-- SECURITY: Only title, content, and a validated image are sent; workflow fields stay server-controlled. --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="profile-form" data-content-form>
    @csrf
    @if ($article)
        @method('PATCH')
    @endif

    <p class="post-form-requirements"><span class="post-form-required-mark" aria-hidden="true">*</span> Fields marked with an asterisk are required.</p>

    {{-- Title validation matches the articles table's 200-character limit. --}}
    <div class="dashboard-form-field">
        <label for="article-title">Title <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <input id="article-title" name="title" @error('title') aria-invalid="true" aria-describedby="article-title-error" @enderror type="text" maxlength="200" required value="{{ old('title', $draftValues['title'] ?? $article?->title) }}">
        @error('title') <span id="article-title-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- Blade escapes article content on redisplay and the server validates it as required text. --}}
    @if ($formattingEnabled)
        <div class="dashboard-form-field">
            <label for="article-content-format">Content format</label>
            <select id="article-content-format" name="content_format" aria-describedby="article-format-help @error('content_format') article-format-error @enderror" @error('content_format') aria-invalid="true" @enderror>
                <option value="plain" @selected(old('content_format', $draftValues['content_format'] ?? $article?->content_format ?? 'plain') === 'plain')>Plain text</option>
                <option value="markdown" @selected(old('content_format', $draftValues['content_format'] ?? $article?->content_format) === 'markdown')>Formatted text (Markdown)</option>
            </select>
            <span id="article-format-help" class="profile-field-help">Choose formatted text to use headings, emphasis, lists, and HTTPS links. HTML stays literal; add images using the upload field.</span>
            @error('content_format')<span id="article-format-error" class="profile-field-error">{{ $message }}</span>@enderror
        </div>
    @endif
    <div class="dashboard-form-field">
        <label for="article-content">Content <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        @if ($formattingEnabled)
            <div class="article-format-tools" data-article-tools hidden role="group" aria-label="Format selected text">
                @foreach (['bold' => 'Bold', 'italic' => 'Italic', 'heading' => 'Heading', 'list' => 'List', 'quote' => 'Quote', 'link' => 'Link'] as $command => $label)
                    <button type="button" class="dashboard-button dashboard-button-secondary" data-format-command="{{ $command }}">{{ $label }}</button>
                @endforeach
            </div>
            <span class="profile-field-help">Markdown examples: **bold**, *italic*, ## Heading, and - list item. The 300-word limit counts words in your source, including formatting markers.</span>
        @endif
        <textarea id="article-content" name="content" @error('content') aria-invalid="true" aria-describedby="article-content-error" @enderror rows="14" maxlength="20000" required>{{ old('content', $draftValues['content'] ?? $article?->content) }}</textarea>
        <span class="profile-field-help">Maximum 300 words and 20,000 characters.</span>
        @error('content') <span id="article-content-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- SECURITY: The server validates image bytes, extension, and size before storing a generated filename. --}}
    <div class="dashboard-form-field">
        <label for="article-image">{{ $article ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <input id="article-image" data-image-preview="article-image-preview" name="image" @error('image') aria-invalid="true" aria-describedby="article-image-error" @enderror type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        <span class="profile-field-help">JPG, PNG, or WebP; maximum 2 MB.</span>
        <x-workspace.upload-status input-id="article-image" :max-kb="2048" />
        @error('image') <span id="article-image-error" class="profile-field-error">{{ $message }}</span> @enderror
        @if ($article?->image)
            <img id="article-image-preview" class="article-form-preview" src="{{ $article->imageUrl() }}" alt="Current article image">
        @else
            <span id="article-image-preview-mount" hidden></span>
        @endif
    </div>

    {{-- The form submits only editable fields and returns to the saved article. --}}
    @if ($formattingEnabled)
        <section class="article-preview-panel" data-article-preview data-preview-url="{{ route('workspace.articles.preview') }}" hidden aria-labelledby="article-preview-heading">
            <h2 id="article-preview-heading">Check formatting</h2>
            <p class="profile-field-help">Preview sends this text to the server without saving it. Refresh after editing.</p>
            <button type="button" class="dashboard-button dashboard-button-secondary" data-render-article>Refresh preview</button>
            <p class="profile-field-help" data-article-preview-status role="status" aria-live="polite"></p>
            <div class="article-rich-content" data-article-preview-output hidden></div>
        </section>
        <noscript><p class="profile-field-help">You can type Markdown and save without JavaScript. Check the formatted article on its workspace page after saving.</p></noscript>
    @endif
    <x-workspace.content-preview :fields="[]" />

    @unless ($article)<x-workspace.draft-controls module="articles" :draft="$draft ?? null" />@endunless

    <div class="profile-form-actions">
        <button type="submit" class="dashboard-button">{{ $submitLabel }}</button>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.posts.articles.index') }}">Back to submissions</a>
    </div>
</form>
