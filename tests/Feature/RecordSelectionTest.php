<?php

namespace Tests\Feature;

use App\Models\Liquidation;
use App\Models\User;
use Tests\TestCase;

class RecordSelectionTest extends TestCase
{
    private function renderReport(): string
    {
        $report = new Liquidation();
        $report->forceFill([
            'liquidation_id' => 42, 'title' => 'Gastos 1', 'description' => 'Event expenses',
            'amount' => '10000.00', 'report_date' => '2025-08-10',
            'created_at' => '2026-01-01', 'approval_status' => 'approved', 'content_status' => 'active',
        ]);

        return \Illuminate\Support\Facades\Blade::render(
            '<x-posts.selection :post="$report" id="liquidation-selection-42" route-prefix="liquidations" category="Events" :date="$report->report_date" :expense="true" />',
            ['report' => $report],
        );
    }

    public function test_guest_sees_report_metadata_and_expense_but_no_private_pdf_url(): void
    {
        $html = $this->renderReport();
        $this->assertStringContainsString('transparency-selection-amount', $html);
        $this->assertStringContainsString('&minus;₱10,000', $html);
        $this->assertStringContainsString('August 10, 2025', $html);
        $this->assertStringNotContainsString('January 1, 2026', $html);
        $this->assertStringNotContainsString('data-pdf-url=', $html);
        $this->assertStringContainsString('Sign in to preview', $html);
    }

    public function test_member_preview_uses_a_signed_liquidation_url(): void
    {
        $user = new User();
        $user->forceFill(['user_id' => 15, 'role' => User::ROLE_STUDENT]);
        $this->actingAs($user);
        $html = $this->renderReport();
        $this->assertStringContainsString('data-pdf-url=', $html);
        $this->assertStringContainsString('signature=', $html);
        $this->assertStringContainsString('/liquidation/42/', $html);
        $this->assertStringNotContainsString('/documents/42/', $html);
    }
}
