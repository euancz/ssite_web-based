<?php

namespace Database\Seeders;

use App\Models\ContentDraft;
use App\Models\OfficerTerm;
use App\Models\Sponsor;
use App\Models\User;
use App\Services\ContentVersions;
use App\Support\AcademicYear;
use App\Support\ContentModules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/** Explicit opt-in sample data. Existing records and edited samples are never overwritten. */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $people = ['Alex Rivera', 'Jamie Santos', 'Morgan Cruz', 'Taylor Reyes', 'Casey Garcia'];
            $positions = ['Multimedia', 'Secretary', 'Auditor', null, null];
            $users = [];
            foreach ($people as $i => $name) {
                $users[] = User::firstOrCreate(['email' => 'demo.member'.($i + 1).'@example.test'], [
                    'name' => '[DEMO] '.$name,
                    'role' => $positions[$i] ? 'officer' : 'student',
                    'officer_position' => $positions[$i],
                    // These are display records, not usable password or Microsoft accounts.
                    'password' => null,
                    'student_number' => 'DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'institute' => 'ICS', 'program' => 'BSIT', 'year_level' => (string) (($i % 4) + 1),
                    'profile_completed_at' => now(),
                ]);
            }

            $topics = [
                'articles' => ['Welcome to the SSITE Community', 'Five Study Habits for IT Students', 'Building Your First Portfolio', 'Staying Safe Online', 'Meet the Student Tech Community', 'Campus Technology Roundup', 'Student Project Spotlight', 'A Look Back at Tech Week'],
                'activities' => ['Web Development Workshop', 'Campus Coding Challenge', 'Digital Safety Seminar', 'Community Tech Outreach', 'Student Project Exhibition', 'Mobile App Design Lab', 'Career Readiness Forum', 'IT Orientation Day'],
                'achievements' => ['Campus Hackathon Champions', 'Outstanding Student Developer', 'Research Poster Award', 'Community Service Recognition', 'UI Design Challenge Winners', 'Programming Contest Finalists', 'Innovation Project Award', 'Student Leadership Recognition'],
                'documents' => ['General Assembly Minutes', 'Annual Organization Plan', 'Membership Guidelines', 'Workshop Attendance Summary', 'Project Proposal Template', 'Outreach Activity Proposal', 'Committee Progress Report', 'Previous Semester Summary'],
                'liquidations' => ['Workshop Materials Report', 'Coding Challenge Expenses', 'Seminar Logistics Report', 'Outreach Supplies Report', 'Exhibition Expenses Report', 'Design Lab Expenses', 'Career Forum Expenses', 'Orientation Expenses'],
            ];
            foreach (ContentModules::all() as $module => $definition) {
                $author = $users[match ($module) {
                    'documents' => 1, 'liquidations' => 2, default => 0
                }];
                $class = $definition['model'];
                foreach ($topics[$module] as $i => $topic) {
                    $title = '[DEMO] '.$topic;
                    if ($class::where('title', $title)->exists()) {
                        continue;
                    }
                    $text = 'Fictional sample data for demonstrating the SSITE system. '.$topic.'. '
                        .'This example helps preview the listing, details, search, and content workflow. '
                        .'All people, events, awards, and amounts in this sample are for demonstration only.';
                    $values = [
                        'title' => $title, 'created_by_user_id' => $author->id,
                        'approval_status' => match ($i) {
                            5 => 'pending', 6 => 'rejected', default => 'approved'
                        },
                        'content_status' => $i === 7 ? 'archived' : 'active',
                        'rejection_reason' => $i === 6 ? '[DEMO] Please add the supporting details before resubmitting.' : null,
                        $module === 'articles' ? 'content' : 'description' => $text,
                        'created_at' => now()->subDays(8 - $i), 'updated_at' => now()->subDays(8 - $i),
                    ];
                    $date = now()->subDays(15 + $i)->toDateString();
                    $values += match ($module) {
                        'articles' => ['content_format' => 'plain', 'category' => ['Community', 'Student Life', 'Technology'][$i % 3]],
                        'activities' => ['content_format' => 'plain', 'activity_date' => $date, 'location' => ['Computer Laboratory', 'Campus Auditorium', 'Student Activity Center'][$i % 3]],
                        'achievements' => ['content_format' => 'plain', 'achievement_date' => $date, 'awardee' => $users[$i % 5]->name, 'category' => ['Academic', 'Technology', 'Community Service'][$i % 3]],
                        'documents' => ['category' => ['Minutes', 'Plans', 'Guidelines'][$i % 3], 'semester' => ($i % 2) + 1, 'academic_year' => AcademicYear::current()],
                        'liquidations' => ['amount' => 1250 + ($i * 750), 'report_date' => $date],
                    };
                    if (in_array($module, ['documents', 'liquidations'], true)) {
                        $folder = $module === 'documents' ? 'documents' : 'liquidation';
                        // Match the private PDF routes' required 40-character filename format.
                        $path = $folder.'/'.sha1('ssite-demo-v1:'.$module.':'.$i).'.pdf';
                        $lines = [$title, 'DEMONSTRATION ONLY - fictional sample data', 'Academic year: '.AcademicYear::current()];
                        if ($module === 'liquidations') {
                            $lines[] = 'Sample expense total: PHP '.number_format($values['amount'], 2);
                            $lines[] = 'Materials and supplies: PHP '.number_format($values['amount'] * 0.6, 2);
                            $lines[] = 'Logistics and printing: PHP '.number_format($values['amount'] * 0.4, 2);
                        } else {
                            $lines[] = 'Purpose: preview document viewing and downloads.';
                            $lines[] = 'Agenda: updates, planning, assignments, and next steps.';
                        }
                        $pdf = $this->pdf($lines);
                        if (! Storage::disk('local')->exists($path) && ! Storage::disk('local')->put($path, $pdf)) {
                            throw new \RuntimeException('Could not store demo PDF.');
                        }
                        $values += ['file_path' => $path, 'original_name' => 'demo-'.Str::slug($topic).'.pdf', 'file_size' => Storage::disk('local')->size($path)];
                    }
                    $post = $class::create($values);
                    app(ContentVersions::class)->record($post, null);
                }
                $this->command?->info($definition['label'].': '.$class::where('title', 'like', '[DEMO] %')->count().' demo records');
            }

            foreach (['Pixel Grove Studio', 'Bright Circuit Labs', 'Campus Cloud Collective', 'Code Harbor Learning', 'North Star Tech'] as $i => $name) {
                Sponsor::firstOrCreate(['name' => '[DEMO] '.$name], [
                    'description' => 'Fictional sponsor for demonstration only. Supports sample student workshops, learning resources, and community projects.',
                    'sort_order' => 100 + $i, 'is_active' => true,
                ]);
            }
            foreach (['Public Information Officer', 'Secretary', 'Auditor', 'IT Representative I', 'IT Representative II'] as $i => $position) {
                OfficerTerm::firstOrCreate(['academic_year' => AcademicYear::current(), 'name' => $users[$i]->name, 'position' => $position], ['sort_order' => 100 + $i]);
            }
            // Prefer the existing President; otherwise use the matching demo officer.
            $president = User::where('role', 'officer')->where('officer_position', 'President')->first();
            foreach ($topics as $module => $titles) {
                $owner = $president ?? $users[match ($module) {
                    'documents' => 1, 'liquidations' => 2, default => 0
                }];
                $id = (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, 'ssite-demo-draft-v1:'.$owner->id.':'.$module);
                ContentDraft::firstOrCreate(['id' => $id], [
                    'created_by_user_id' => $owner->id, 'module' => $module, 'lock_version' => 1,
                    'payload' => ['title' => '[DEMO] Draft: '.$titles[0], $module === 'articles' ? 'content' : 'description' => 'Fictional draft for trying the editor. Add details and an attachment before submitting.'],
                ]);
            }
            $this->command?->info('Also seeded 5 demo users, 5 sponsors, 5 officer roster entries, and 5 private drafts.');
        });
    }

    /** Generate a small valid, self-contained PDF without adding a PDF library. */
    private function pdf(array $lines): string
    {
        $stream = "BT /F1 12 Tf 50 790 Td 20 TL\n";
        foreach ($lines as $line) {
            $stream .= '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line).") Tj T*\n";
        }
        $stream .= "ET\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream).">>\nstream\n".$stream.'endstream',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
