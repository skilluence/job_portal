<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;



/**
 * Seeds verifiable test data for the recruiter monthly report:
 *   - 1 manager  (manager@test.skilluence.com)
 *   - 1 recruiter (recruiter@test.skilluence.com)  → assigned to that manager
 *   - 3 candidates assigned to that recruiter
 *   - Daily logs spread across the current month (with real applications / assisment values)
 *   - 2 interviews in the current month with recruiter_id set correctly
 *
 * After running, visit: /admin/users/{recruiter_id}/report
 * Credentials  →  password: password
 */
class ReportTestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->orderBy('id')->first();

        // ── Manager ──────────────────────────────────────────────────────────
        $manager = User::updateOrCreate(
            ['email' => 'manager@test.skilluence.com'],
            [
                'name'            => 'Test Manager',
                'password'        => Hash::make('password'),
                'role'            => 'manager',
                'status'          => 'active',
                'team_manager_id' => null,
            ]
        );

        // ── Recruiter ─────────────────────────────────────────────────────────
        $recruiter = User::updateOrCreate(
            ['email' => 'recruiter@test.skilluence.com'],
            [
                'name'            => 'Test Recruiter',
                'password'        => Hash::make('password'),
                'role'            => 'recruiter',
                'status'          => 'active',
                'team_manager_id' => $manager->id,
            ]
        );

        $adminId = $admin?->id ?? $recruiter->id;

        // ── Candidates ────────────────────────────────────────────────────────
        $candidatesData = [
            ['full_name' => 'Alice Johnson',  'email_id' => 'alice.johnson@testcandidate.com'],
            ['full_name' => 'Bob Martinez',   'email_id' => 'bob.martinez@testcandidate.com'],
            ['full_name' => 'Carol Williams', 'email_id' => 'carol.williams@testcandidate.com'],
        ];

        $candidates = [];
        foreach ($candidatesData as $cData) {
            $candidates[] = Candidate::updateOrCreate(
                ['email_id' => $cData['email_id']],
                [
                    'full_name'             => $cData['full_name'],
                    'first_name'            => explode(' ', $cData['full_name'])[0],
                    'last_name'             => explode(' ', $cData['full_name'])[1],
                    'phone_number'          => '555-000-0000',
                    'domain'                => 'Software Engineering',
                    'status'               => 'active',
                    'login_password'        => Hash::make('Test@1234'),
                    'login_password_plain'  => 'Test@1234',
                    'recruiter_id'          => $recruiter->id,
                    'team_manager_id'       => $manager->id,
                    'created_by'            => $adminId,
                ]
            );
        }

        // ── Daily Logs ────────────────────────────────────────────────────────
        // One log per candidate per day, spread across the first 10 days of the current month.
        // Values are varied so the chart has obvious shape.
        $now  = Carbon::now();
        $year = (int) $now->format('Y');
        $mon  = (int) $now->format('n');

        $logDays = [
            // [day, apps, assisment, remark]
            [1,  5,  3, 'Sent batch applications on day 1.'],
            [2,  8,  5, 'Used AI assisment heavily today.'],
            [3,  4,  2, 'Slower day.'],
            [5,  12, 7, 'High-volume application day.'],
            [6,  6,  4, 'Follow-ups sent.'],
            [7,  9,  6, 'Good response rate.'],
            [8,  3,  1, 'Light day.'],
            [10, 15, 9, 'Best day so far this month.'],
        ];

        foreach ($candidates as $candidate) {
            foreach ($logDays as [$day, $apps, $asst, $remark]) {
                // Skip future days gracefully
                if ($day > $now->daysInMonth) {
                    continue;
                }
                $logDate = Carbon::createFromDate($year, $mon, $day)->toDateString();

                DailyLog::updateOrCreate(
                    ['candidate_id' => $candidate->id, 'log_date' => $logDate],
                    [
                        'recruiter_id'    => $recruiter->id,
                        'applications'    => $apps,
                        'assistant_count' => $asst,
                        'interview_count' => 0,   // will be incremented by interviews below
                        'remark'          => $remark,
                        'created_by'      => $recruiter->id,
                    ]
                );
            }
        }

        // ── Interviews ────────────────────────────────────────────────────────
        // 2 interviews for the first candidate, 1 for the second — all this month.
        $interviewsData = [
            [
                'candidate'          => $candidates[0],
                'role'               => 'Backend Developer',
                'company_name'       => 'Acme Corp',
                'company_domain'     => 'https://acme.com',
                'interview_type'     => 'virtual',
                'scheduled_date'     => Carbon::createFromDate($year, $mon, 3)->toDateString(),
                'scheduled_time'     => '10:00',
                'scheduled_timezone' => 'EST',
                'interview_status'   => 'valid',
                'remark'             => 'First round technical screen.',
            ],
            [
                'candidate'          => $candidates[0],
                'role'               => 'Backend Developer',
                'company_name'       => 'Acme Corp',
                'company_domain'     => 'https://acme.com',
                'interview_type'     => 'on_site',
                'scheduled_date'     => Carbon::createFromDate($year, $mon, 10)->toDateString(),
                'scheduled_time'     => '14:00',
                'scheduled_timezone' => 'EST',
                'interview_status'   => 'valid',
                'remark'             => 'Final round on-site.',
            ],
            [
                'candidate'          => $candidates[1],
                'role'               => 'Frontend Engineer',
                'company_name'       => 'BrightTech',
                'company_domain'     => 'https://brighttech.io',
                'interview_type'     => 'phone_call',
                'scheduled_date'     => Carbon::createFromDate($year, $mon, 7)->toDateString(),
                'scheduled_time'     => '09:00',
                'scheduled_timezone' => 'CST',
                'interview_status'   => 'valid',
                'remark'             => 'Initial HR call.',
            ],
        ];

        foreach ($interviewsData as $iData) {
            $candidate = $iData['candidate'];
            unset($iData['candidate']);

            // Avoid duplicate seeding: match on candidate + company + scheduled_date
            Interview::updateOrCreate(
                [
                    'candidate_id'   => $candidate->id,
                    'company_name'   => $iData['company_name'],
                    'scheduled_date' => $iData['scheduled_date'],
                ],
                array_merge($iData, [
                    'candidate_id' => $candidate->id,
                    'recruiter_id' => $recruiter->id,   // ← critical for report query
                    'created_by'   => $recruiter->id,
                ])
            );
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $this->command->info('✅  ReportTestSeeder complete.');
        $this->command->info("   Manager   → manager@test.skilluence.com  (id: {$manager->id})");
        $this->command->info("   Recruiter → recruiter@test.skilluence.com  (id: {$recruiter->id})");
        $this->command->info("   Candidates: " . implode(', ', array_map(fn($c) => $c->full_name, $candidates)));
        $this->command->info("   Daily logs: " . count($logDays) . " days × " . count($candidates) . " candidates");
        $this->command->info("   Interviews: " . count($interviewsData));
        $this->command->newLine();
        $this->command->info("   Report URL → /admin/users/{$recruiter->id}/report");
        $this->command->info("   Expected totals for {$now->format('F Y')}:");

        $totalApps  = array_sum(array_column($logDays, 1)) * count($candidates);
        $totalAsst  = array_sum(array_column($logDays, 2)) * count($candidates);
        $this->command->info("     Applications : {$totalApps}  (sum across all candidates × days)");
        $this->command->info("     Assisment    : {$totalAsst}");
        $this->command->info("     Interviews   : 3  (scheduled this month)");
    }
}
