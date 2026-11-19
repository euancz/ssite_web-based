<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_accounts_are_renamed_and_retain_their_permissions(): void
    {
        $officer = User::factory()->create(['role' => 'officer', 'officer_position' => 'Multimedia']);
        $admin = User::factory()->create(['role' => 'superadmin']);
        $migration = require database_path('migrations/2026_11_18_000001_rename_officer_and_admin_roles.php');
        $migration->up();

        $this->assertSame('Social Media Manager', $officer->refresh()->officer_position);
        $this->assertTrue($officer->canPostIn('articles'));
        $this->assertTrue($officer->canPostIn('activities'));
        $this->assertTrue($officer->canPostIn('achievements'));
        $this->assertFalse($officer->canPostIn('documents'));
        $this->assertSame('admin', $admin->refresh()->role);
        $this->assertTrue($admin->canAssignAdvisers());
        $this->assertFalse($admin->canAssignOfficers());
    }

    public function test_old_names_are_not_authorized_as_aliases(): void
    {
        $officer = new User();
        $officer->forceFill(['role' => 'officer', 'officer_position' => 'Multimedia']);
        $admin = new User();
        $admin->forceFill(['role' => 'superadmin']);

        $this->assertFalse($officer->canPost());
        $this->assertFalse($admin->canAssignAdvisers());
        $this->assertArrayNotHasKey('Multimedia', config('school.positions'));
    }
}
