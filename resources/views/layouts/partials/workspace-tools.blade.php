{{-- Navigation is built from the same authorized destinations as the sidebar. --}}
<dialog id="workspace-jump" class="workspace-dialog" aria-labelledby="workspace-jump-title">
    <header class="workspace-dialog-heading"><div><span class="workspace-eyebrow">FIND YOUR WAY</span><h2 id="workspace-jump-title">Jump to your next task</h2></div><button type="button" data-workspace-close aria-label="Close quick navigation"><x-workspace.icon name="x" /></button></header>
    <label class="workspace-jump-search"><x-workspace.icon name="search" /><span class="visually-hidden">Find a workspace page</span><input type="search" data-workspace-query placeholder="Search pages and actions?" autocomplete="off" maxlength="100"></label>
    <div class="workspace-jump-results" data-workspace-destinations>
        <a href="{{ route($user->dashboardRoute()) }}" data-jump-item><x-workspace.icon name="grid" /><span>Dashboard<small>Your workspace overview</small></span><x-workspace.icon name="arrow-right" /></a>
        @if ($user->canPost())
            <a href="{{ route('workspace.drafts.index') }}" data-jump-item><x-workspace.icon name="file" /><span>My drafts<small>Continue your work in progress</small></span><x-workspace.icon name="arrow-right" /></a>
        @endif
        @foreach ($availableModules as $key => $module)
            @if ($user->canPostIn($key))
                <a href="{{ route($module['create']) }}" data-jump-item><x-workspace.icon name="plus" /><span>Create {{ strtolower($module['singular']) }}<small>Start a new submission</small></span><x-workspace.icon name="arrow-right" /></a>
            @endif
            <a href="{{ route($module['workspace'], ['tab' => 'published']) }}" data-jump-item><x-workspace.icon :name="$module['icon']" /><span>{{ $module['label'] }}<small>Browse the published library</small></span><x-workspace.icon name="arrow-right" /></a>
        @endforeach
        @if ($availableModules->isNotEmpty())
            <a href="{{ route('workspace.content-activity.index') }}" data-jump-item><x-workspace.icon name="clock" /><span>Activity log<small>Follow content changes</small></span><x-workspace.icon name="arrow-right" /></a>
        @endif
        @can(config('workspace.navigation.review.gate'))
            <a href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}" data-jump-item><x-workspace.icon name="check" /><span>Review queue<small>Review pending submissions</small></span><x-workspace.icon name="arrow-right" /></a>
        @endcan
        @can(config('workspace.navigation.officers.gate'))
            <a href="{{ route('adviser.officers.index') }}" data-jump-item><x-workspace.icon name="users" /><span>Officer roster<small>Manage officer information</small></span><x-workspace.icon name="arrow-right" /></a>
        @endcan
        @can(config('workspace.navigation.users.gate'))
            <a href="{{ route('adviser.users.index') }}" data-jump-item><x-workspace.icon name="users" /><span>{{ $user->isSuperadmin() ? 'Adviser accounts' : 'User accounts' }}<small>Manage organization access</small></span><x-workspace.icon name="arrow-right" /></a>
        @endcan
    </div>
    <p class="workspace-jump-empty" data-workspace-no-results hidden>No matching pages. Try ?drafts?, ?articles?, or ?accounts?.</p>
    <footer class="workspace-dialog-footer"><span data-workspace-result-count role="status" aria-live="polite"></span><span><kbd>?</kbd> <kbd>?</kbd> to browse ? <kbd>Esc</kbd> to close</span></footer>
</dialog>
<dialog id="workspace-preferences" class="workspace-dialog workspace-preferences" aria-labelledby="workspace-preferences-title">
    <header class="workspace-dialog-heading"><div><span class="workspace-eyebrow">MAKE IT YOURS</span><h2 id="workspace-preferences-title">Workspace settings</h2></div><button type="button" data-workspace-close aria-label="Close workspace settings"><x-workspace.icon name="x" /></button></header>
    <p>These display preferences apply to this browser.</p>
    <fieldset><legend>Content spacing</legend><label><input type="radio" name="workspace-density" value="comfortable" checked> Comfortable <small>More breathing room for reading.</small></label><label><input type="radio" name="workspace-density" value="compact"> Compact <small>Fit more rows and cards on screen.</small></label></fieldset>
    <label class="workspace-motion-setting"><input type="checkbox" data-workspace-reduce-motion> Reduce workspace animation</label>
    <p class="profile-field-help">Your device?s reduced-motion setting is always respected.</p>
    <footer class="workspace-dialog-footer"><span data-workspace-preference-status role="status" aria-live="polite">Changes save automatically.</span><button type="button" class="dashboard-button" data-workspace-close>Done</button></footer>
</dialog>
