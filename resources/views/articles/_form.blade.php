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

    @if (\App\Support\ArticleCategory::available())
        <div class="dashboard-form-field">
            <label for="article-category">Category (optional)</label>
            <input id="article-category" name="category" type="text" maxlength="100" value="{{ old('category', $draftValues['category'] ?? $article?->category) }}" @error('category') aria-invalid="true" aria-describedby="article-category-error" @enderror>
            <span class="profile-field-help">Use a short, consistent category name to help readers find related articles.</span>
            @error('category')<span id="article-category-error" class="profile-field-error">{{ $message }}</span>@enderror
        </div>
    @endif

    {{-- Blade escapes article content on redisplay and the server validates it as required text. --}}
    @if ($formattingEnabled)
        <input type="hidden" id="article-content-format" name="content_format" value="{{ old('content_format', $draftValues['content_format'] ?? $article?->content_format ?? 'markdown') }}">
        @error('content_format')<span id="article-format-error" class="profile-field-error">{{ $message }}</span>@enderror
    @endif
    <div class="dashboard-form-field">
        <label id="article-content-label" for="article-content">Content <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        @if ($formattingEnabled)
            <div class="article-editor-shell">
                <div class="article-format-tools" data-article-tools role="toolbar" aria-label="Article formatting tools">
                @foreach (['bold' => 'Bold', 'italic' => 'Italic', 'heading' => 'Heading', 'quote' => 'Quote', 'code' => 'Inline code', 'link' => 'Link', 'list' => 'Bulleted list', 'ordered-list' => 'Numbered list'] as $command => $label)
                    <button type="button" class="article-format-tool" data-format-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}">
                        <x-icon :name="match ($command) { 'heading' => 'heading-2', 'ordered-list' => 'list-ordered', default => $command }" />
                    </button>
                @endforeach
                </div>
                <div class="article-editor-canvas">
                    <textarea id="article-content" name="content" @error('content') aria-invalid="true" aria-describedby="article-content-error" @enderror rows="14" maxlength="20000" required>{{ old('content', $draftValues['content'] ?? $article?->content) }}</textarea>
                </div>
            </div>
            <span class="profile-field-help">Select text to format it. Use Ctrl+B for bold or Ctrl+I for italic.</span>
        @else
            <textarea id="article-content" name="content" @error('content') aria-invalid="true" aria-describedby="article-content-error" @enderror rows="14" maxlength="20000" required>{{ old('content', $draftValues['content'] ?? $article?->content) }}</textarea>
        @endif
        <span class="profile-field-help">Maximum 300 words and 20,000 characters.</span>
        @error('content') <span id="article-content-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- SECURITY: The server validates image bytes, extension, and size before storing a generated filename. --}}
    <div class="dashboard-form-field">
        <label for="article-image">{{ $article ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <div class="article-attachment-dropzone">
            <span class="article-attachment-icon" aria-hidden="true">↗</span>
            <span><strong>Attach a feature image</strong><small>JPG, PNG, or WebP up to 2 MB</small></span>
            <input id="article-image" data-image-preview="article-image-preview" name="image" @error('image') aria-invalid="true" aria-describedby="article-image-error" @enderror type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </div>
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
