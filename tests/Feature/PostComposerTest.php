<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\BuildsContentSchema;
use Tests\TestCase;

class PostComposerTest extends TestCase
{
    use BuildsContentSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildContentSchema();
    }

    private function officer(string $position): User
    {
        $user = new User;
        $user->forceFill(['name' => 'Composer author', 'email' => uniqid().'@mcc.edu.ph', 'role' => 'officer', 'officer_position' => $position, 'profile_completed_at' => now()])->save();

        return $user;
    }

    public function test_every_content_type_opens_the_guided_details_step_with_its_original_form(): void
    {
        foreach (['articles' => 'Social Media Manager', 'activities' => 'Social Media Manager', 'achievements' => 'Social Media Manager', 'documents' => 'Secretary', 'liquidations' => 'Auditor'] as $module => $position) {
            $this->actingAs($this->officer($position))->get(route($module.'.create'))->assertOk()
                ->assertSee('data-post-composer', false)
                ->assertSee('aria-current="step"', false)
                ->assertSee('data-composer-submit="Submit for review"', false)
                ->assertSee('action="'.route($module.'.store').'"', false)
                ->assertSee('data-save-draft', false);
        }
    }

    public function test_final_action_follows_auto_approval_configuration(): void
    {
        $president = $this->officer('President');
        config(['school.president_posts_auto_approved' => true]);
        $this->actingAs($president)->get(route('articles.create'))->assertOk()
            ->assertSee('data-composer-submit="Publish post"', false);
        config(['school.president_posts_auto_approved' => false]);
        $this->get(route('articles.create'))->assertOk()
            ->assertSee('data-composer-submit="Submit for review"', false);
    }
}
