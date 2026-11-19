@props(['id', 'name', 'value' => '', 'formatValue' => 'markdown', 'formatId', 'labelId', 'formattingEnabled' => false, 'title' => 'Rich text editor', 'subtitle' => 'Write, format, and refine your post.'])

@if ($formattingEnabled)
    <input type="hidden" id="{{ $formatId }}" name="content_format" value="{{ old('content_format', $formatValue) }}" data-rich-format>
    @error('content_format')<span id="{{ $formatId }}-error" class="profile-field-error">{{ $message }}</span>@enderror
    <div class="article-editor-shell" data-rich-editor>
        <div class="article-editor-heading">
            <span><strong>{{ $title }}</strong><small>{{ $subtitle }}</small></span>
        </div>
        <div class="article-format-tools" data-article-tools role="group" aria-label="Post formatting tools">
            <div class="article-tool-group" role="group" aria-label="Text style">
                <select data-rich-heading aria-label="Text style" hidden>
                    <option value="paragraph">Paragraph</option><option value="2">Heading 2</option><option value="3">Heading 3</option>
                </select>
                @foreach (['bold' => ['Bold', 'bold'], 'italic' => ['Italic', 'italic'], 'strike' => ['Strikethrough', 'strikethrough']] as $command => [$label, $icon])
                    <button type="button" class="article-format-tool" data-format-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}"><x-icon :name="$icon" /></button>
                @endforeach
            </div>
            <div class="article-tool-group" role="group" aria-label="Lists and blocks">
                @foreach (['list' => ['Bulleted list', 'list'], 'ordered-list' => ['Numbered list', 'list-ordered'], 'outdent' => ['Decrease list indent', 'outdent'], 'indent' => ['Increase list indent', 'indent'], 'quote' => ['Block quote', 'quote'], 'code' => ['Inline code', 'code'], 'code-block' => ['Code block', 'code-block'], 'divider' => ['Insert divider', 'minus']] as $command => [$label, $icon])
                    <button type="button" class="article-format-tool" data-format-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}"><x-icon :name="$icon" /></button>
                @endforeach
            </div>
            <div class="article-tool-group" role="group" aria-label="Insert and edit">
                @foreach (['link' => ['Insert or edit link', 'link'], 'table' => ['Insert table', 'table'], 'clear' => ['Clear formatting', 'clear-format'], 'undo' => ['Undo (Ctrl+Z)', 'undo'], 'redo' => ['Redo (Ctrl+Shift+Z)', 'redo']] as $command => [$label, $icon])
                    <button type="button" class="article-format-tool" data-format-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}"><x-icon :name="$icon" /></button>
                @endforeach
            </div>
        </div>
        <div class="article-table-tools" data-table-tools role="group" aria-label="Edit table" hidden>
            <span>Table</span>
            @foreach (['add-row' => 'Add row', 'delete-row' => 'Delete row', 'add-column' => 'Add column', 'delete-column' => 'Delete column', 'delete-table' => 'Remove table'] as $command => $label)
                <button type="button" data-format-command="{{ $command }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="article-editor-canvas">
            <textarea id="{{ $id }}" data-rich-body name="{{ $name }}" aria-labelledby="{{ $labelId }}" @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror rows="14" maxlength="20000" required>{{ old($name, $value) }}</textarea>
        </div>
        <dialog class="article-link-dialog" data-rich-link aria-labelledby="{{ $id }}-link-heading">
            <h2 id="{{ $id }}-link-heading">Insert a link</h2>
            <p>Link selected text to a website. With no selection, the URL becomes the link text.</p>
            <label for="{{ $id }}-link-url">Website URL</label>
            <input id="{{ $id }}-link-url" type="url" inputmode="url" placeholder="https://example.com" autocomplete="off" aria-describedby="{{ $id }}-link-error">
            <p id="{{ $id }}-link-error" class="profile-field-error" role="alert" hidden></p>
            <div class="article-link-actions">
                <button type="button" class="dashboard-button dashboard-button-secondary" data-link-cancel>Cancel</button>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-link-remove>Remove link</button>
                <button type="button" class="dashboard-button" data-link-save>Apply link</button>
            </div>
        </dialog>
    </div>
@else
    <textarea id="{{ $id }}" name="{{ $name }}" aria-labelledby="{{ $labelId }}" @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror rows="14" maxlength="20000" required>{{ old($name, $value) }}</textarea>
@endif
