<?php

namespace App\Services;

use App\Models\ContentDraft;
use App\Models\User;
use App\Support\ContentModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

    public function save(User $user, string $module, array $values, ?UploadedFile $file = null): ContentDraft
    {
        $stored = null;
        if ($file) {
            $stored = $this->storeAttachment($file, $user, $module);
        }
        try {
            return DB::transaction(function () use ($user, $module, $values, $stored) {
            $draft = empty($values['draft_id']) ? new ContentDraft : $this->owned($user)
                ->where('module', $module)->whereKey($values['draft_id'])->lockForUpdate()->firstOrFail();
            if ($draft->exists) {
                $this->checkVersion($draft, (int) $values['draft_version']);
            }
            unset($values['draft_id'], $values['draft_version']);
            unset($values['image'], $values['file']);
            $oldAttachment = $draft->attachment();
            if ($stored) {
                $values['attachment'] = $stored;
            } elseif ($oldAttachment) {
                $values['attachment'] = $oldAttachment;
            }
            $draft->forceFill(['created_by_user_id' => $user->user_id, 'module' => $module,
                'payload' => $values, 'lock_version' => $draft->exists ? $draft->lock_version + 1 : 1])->save();
            if ($stored && $oldAttachment && $oldAttachment['path'] !== $stored['path']) {
                DB::afterCommit(fn () => app(PostFiles::class)->deleteDraft($oldAttachment['path']));
            }

            return $draft;
            });
        } catch (\Throwable $exception) {
            if ($stored) {
                app(PostFiles::class)->deleteDraft($stored['path']);
            }
            throw $exception;
        }
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
            $attachment = $draft->attachment();
            if ($attachment) {
                $path = app(PostFiles::class)->promoteDraft($attachment['path'], $post);
                if ($post->getTable() === 'documents' || $post->getTable() === 'liquidations') {
                    $post->file_path = $path;
                    $post->original_name = $attachment['original_name'];
                    $post->file_size = $attachment['size'];
                } else {
                    $post->image = $path;
                }
                DB::afterCommit(fn () => app(PostFiles::class)->deleteDraft($attachment['path']));
            }
            if (! $draft->delete()) {
                throw new \RuntimeException('The draft could not be consumed.');
            }
        });
    }

    public function delete(ContentDraft $draft): void
    {
        $attachment = $draft->attachment();
        DB::transaction(function () use ($draft): void {
            $draft->delete();
        });
        if ($attachment) {
            app(PostFiles::class)->deleteDraft($attachment['path']);
        }
    }

    private function storeAttachment(UploadedFile $file, User $user, string $module): array
    {
        $isPdf = in_array($module, ['documents', 'liquidations'], true);
        if ($isPdf) {
            $handle = @fopen($file->getRealPath(), 'rb');
            $header = $handle ? fread($handle, 5) : '';
            if ($handle) fclose($handle);
            if ($file->getMimeType() !== 'application/pdf' || strtolower($file->getClientOriginalExtension()) !== 'pdf' || $header !== '%PDF-') {
                throw ValidationException::withMessages(['file' => 'Choose a genuine PDF file with a .pdf filename.']);
            }
        }
        $extension = $isPdf ? 'pdf' : strtolower($file->guessExtension() ?: 'bin');
        $path = app(PostFiles::class)->store($file, 'drafts/'.$user->user_id, 'local', $isPdf);
        $name = basename(str_replace('\\', '/', (string) $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/u', '', $name) ?? '';

        return ['path' => $path, 'type' => $isPdf ? 'pdf' : 'image', 'original_name' => mb_substr($name !== '' ? $name : 'attachment.'.$extension, 0, 255), 'size' => (int) $file->getSize()];
    }
}
