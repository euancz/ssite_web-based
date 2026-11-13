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
    <div class="dashboard-form-field">
        <label id="article-content-label" for="article-content">Content <span class="post-form-required-mark" aria-hidden="true">*</span></label>
        <x-rich-content-editor
            id="article-content"
            name="content"
            format-id="article-content-format"
            label-id="article-content-label"
            title="Article editor"
            subtitle="Write, format, and refine your story."
            :value="$draftValues['content'] ?? $article?->content"
            :format-value="$draftValues['content_format'] ?? $article?->content_format ?? 'markdown'"
            :formatting-enabled="$formattingEnabled"
        />
        @if ($formattingEnabled)
            <span class="profile-field-help">Use the toolbar or paste formatted text. Preview below to check how readers will see your article.</span>
        @endif
        <span class="profile-field-help">Maximum 300 words and 20,000 characters.</span>
        @error('content') <span id="article-content-error" class="profile-field-error">{{ $message }}</span> @enderror
    </div>

    {{-- SECURITY: The server validates image bytes, extension, and size before storing a generated filename. --}}
    <div class="dashboard-form-field">
        <label for="article-image">{{ $article ? 'Replace image (optional)' : 'Image (optional)' }}</label>
        <x-workspace.attachment title="Attach a feature image" help="JPG or PNG up to 2 MB">
            <input id="article-image" data-image-preview="article-image-preview" name="image" @error('image') aria-invalid="true" aria-describedby="article-image-error" @enderror type="file" accept=".jpg,.png">
        </x-workspace.attachment>
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
