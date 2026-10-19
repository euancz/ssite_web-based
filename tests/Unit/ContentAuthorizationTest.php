<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Document;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\UserPolicy;
use Tests\TestCase;

class ContentAuthorizationTest extends TestCase
{
    public function test_only_advisers_can_assign_officer_roles_and_cannot_assign_adviser_roles(): void
    {
        $policy = new UserPolicy();
        $adviser = $this->user(1, User::ROLE_ADVISER);
        $student = $this->user(2, User::ROLE_STUDENT);
        $officer = $this->user(3, User::ROLE_OFFICER);

        $this->assertTrue($policy->changeRole($adviser, $student));
        $this->assertTrue($policy->changeRole($adviser, $officer));
        $this->assertFalse($policy->changeRole($adviser, $adviser));
        $this->assertFalse($policy->changeRole($student, $officer));
    }

    public function test_only_president_can_review_and_non_president_officers_can_edit_their_own_posts(): void
    {
        $policy = new ArticlePolicy();
        $secretary = $this->user(10, User::ROLE_OFFICER, 'Multimedia');
        $president = $this->user(11, User::ROLE_OFFICER, 'President');
        $article = new Article();
        $article->created_by_user_id = $secretary->user_id;
        $article->approval_status = Article::APPROVAL_PENDING;
        $article->content_status = Article::CONTENT_ACTIVE;

        $this->assertTrue($policy->update($secretary, $article));
        $this->assertFalse($policy->approve($secretary, $article));
        $this->assertTrue($policy->approve($president, $article));
        $this->assertFalse($policy->update($this->user(12, User::ROLE_OFFICER, 'Multimedia'), $article));
    }

    public function test_officer_positions_can_post_only_to_their_configured_modules(): void
    {
        $secretary = $this->user(30, User::ROLE_OFFICER, 'Secretary');
        $auditor = $this->user(31, User::ROLE_OFFICER, 'Auditor');
        $multimedia = $this->user(32, User::ROLE_OFFICER, 'Multimedia');
        $president = $this->user(33, User::ROLE_OFFICER, 'President');
        $student = $this->user(34, User::ROLE_STUDENT);

        $this->assertTrue($secretary->canPostIn('documents'));
        $this->assertFalse($secretary->canPostIn('articles'));
        $this->assertTrue($auditor->canPostIn('liquidations'));
        $this->assertFalse($auditor->canPostIn('documents'));
        $this->assertTrue($multimedia->canPostIn('articles'));
        $this->assertTrue($multimedia->canPostIn('activities'));
        $this->assertFalse($multimedia->canPostIn('documents'));
        $this->assertTrue($president->canPostIn('liquidations'));
        $this->assertFalse($student->canPostIn('articles'));
    }

    public function test_pending_rejected_and_archived_posts_are_not_publicly_visible(): void
    {
        $policy = new ArticlePolicy();
        $owner = $this->user(40, User::ROLE_OFFICER, 'Multimedia');
        $other = $this->user(41, User::ROLE_STUDENT);
        $article = new Article();
        $article->created_by_user_id = $owner->user_id;
        $article->content_status = Article::CONTENT_ACTIVE;

        $article->approval_status = Article::APPROVAL_PENDING;
        $this->assertTrue($policy->view($owner, $article));
        $this->assertFalse($policy->view($other, $article));

        $article->approval_status = Article::APPROVAL_REJECTED;
        $this->assertTrue($policy->view($owner, $article));
        $this->assertFalse($policy->view($other, $article));

        $article->approval_status = Article::APPROVAL_APPROVED;
        $article->content_status = Article::CONTENT_ARCHIVED;
        $this->assertFalse($policy->view($other, $article));

        $article->content_status = Article::CONTENT_ACTIVE;
        $this->assertTrue($policy->view($other, $article));
    }

    public function test_private_pdf_is_visible_to_its_owner_and_president_but_not_another_student(): void
    {
        $policy = new DocumentPolicy();
        $owner = $this->user(20, User::ROLE_OFFICER, 'Secretary');
        $president = $this->user(21, User::ROLE_OFFICER, 'President');
        $student = $this->user(22, User::ROLE_STUDENT);
        $document = new Document();
        $document->created_by_user_id = $owner->user_id;
        $document->approval_status = Document::APPROVAL_PENDING;
        $document->content_status = Document::CONTENT_ACTIVE;
        $document->file_path = 'private/documents/example.pdf';

        $this->assertTrue($policy->viewFile($owner, $document));
        $this->assertTrue($policy->viewFile($president, $document));
        $this->assertFalse($policy->viewFile($student, $document));
    }

    private function user(int $id, string $role, ?string $position = null): User
    {
        $user = new User();
        $user->user_id = $id;
        $user->role = $role;
        $user->officer_position = $position;

        return $user;
    }
}
