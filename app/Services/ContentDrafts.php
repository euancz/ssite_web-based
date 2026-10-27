<?php

namespace App\Services;

use App\Models\ContentDraft;
use App\Models\User;
use App\Support\ContentModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentDrafts
{
    public function owned(User $user): Builder
    {
        $modules = array_keys(array_filter(ContentModules::all(), fn ($module) => $user->can('create', $module['model'])));

        return ContentDraft::query()->where('created_by_user_id', $user->user_id)->whereIn('module', $modules);
    }

    public function checkVersion(ContentDraft $draft, int $version): void
    {
        if ($draft->lock_version !== $version) {
            throw ValidationException::withMessages(['draft_version' => 'This draft changed in another tab. Copy your unsaved text, then reopen the draft from My drafts before saving or submitting.']);
        }
    }

    public function save(User $user, string $module, array $values): ContentDraft
    {
        return DB::transaction(function () use ($user, $module, $values) {
            $draft = empty($values['draft_id']) ? new ContentDraft : $this->owned($user)
                ->where('module', $module)->whereKey($values['draft_id'])->lockForUpdate()->firstOrFail();
            if ($draft->exists) {
                $this->checkVersion($draft, (int) $values['draft_version']);
            }
            unset($values['draft_id'], $values['draft_version']);
            $draft->forceFill(['created_by_user_id' => $user->user_id, 'module' => $module,
                'payload' => $values, 'lock_version' => $draft->exists ? $draft->lock_version + 1 : 1])->save();

            return $draft;
        });
    }

    /** The post and draft removal commit together; PostFiles cleans new uploads on failure. */
    public function submit(Model $post, Request $request): void
    {
        app(PostFiles::class)->save($post, function () use ($post, $request) {
            $values = $request->validate(['draft_id' => ['nullable', 'uuid'],
                'draft_version' => ['required_with:draft_id', 'nullable', 'integer', 'min:1']]);
            if (empty($values['draft_id'])) {
                return;
            }
            $draft = $this->owned($request->user())->where('module', $post->getTable())
                ->whereKey($values['draft_id'])->lockForUpdate()->firstOrFail();
            $this->checkVersion($draft, (int) $values['draft_version']);
            if (! $draft->delete()) {
                throw new \RuntimeException('The draft could not be consumed.');
            }
        });
    }
}
