<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\DailyLog;
use App\Models\Interview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LocalDemoSeeder extends Seeder
{
    private const LOGIN_PASSWORD = 'Test@123';

    public function run(): void
    {
        $this->wipeLocalData();

        $admin = $this->seedAdmin();
        $managers = $this->seedManagers(10);
        $recruiters = $this->seedRecruiters($managers, 20);
        $candidates = $this->seedCandidates($recruiters, $admin, 50);

        $heavyCandidates = $candidates->shuffle()->take(5)->values();
        $regularCandidates = $candidates->whereNotIn('id', $heavyCandidates->pluck('id'))->values();

        $this->seedInterviews($heavyCandidates, 10);
        $this->seedInterviews($regularCandidates, 1);
        $this->seedDailyLogs($candidates, 10);
        $this->seedAttentionCheckCandidates($recruiters, $admin);
        $this->syncInterviewCounts();

        $this->command?->info('Local demo data seeded.');
        $this->command?->info('Admin: admin@skilluence.com / ' . self::LOGIN_PASSWORD);
        $this->command?->info('Managers: 10 | Recruiters: 20 | Candidates: 52');
        $this->command?->info('Interviews: 95 | Daily logs: 500');
    }

    private function wipeLocalData(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->delete();
        }

        if (Schema::hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->delete();
        }

        AuditLog::query()->delete();
        DailyLog::query()->delete();
        Interview::query()->delete();
        CandidateResume::query()->delete();
        Candidate::withTrashed()->forceDelete();
        User::query()->delete();
    }

    private function seedAdmin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'email' => 'admin@skilluence.com',
            'password' => Hash::make(self::LOGIN_PASSWORD),
            'role' => 'admin',
            'status' => 'active',
            'team_manager_id' => null,
        ]);
    }

    private function seedManagers(int $count): Collection
    {
        $managers = collect();

        for ($i = 1; $i <= $count; $i++) {
            $managers->push(User::create([
                'name' => 'Team Manager ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => 'manager' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '@skilluence.local',
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 'manager',
                'status' => 'active',
                'team_manager_id' => null,
            ]));
        }

        return $managers;
    }

    private function seedRecruiters(Collection $managers, int $count): Collection
    {
        $recruiters = collect();
        $managerRotation = $managers->shuffle()->values();
        $managerCount = $managerRotation->count();

        for ($i = 1; $i <= $count; $i++) {
            $manager = $managerRotation->get(($i - 1) % $managerCount);
            $recruiters->push(User::create([
                'name' => 'Recruiter ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'email' => 'recruiter' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '@skilluence.local',
                'password' => Hash::make(self::LOGIN_PASSWORD),
                'role' => 'recruiter',
                'status' => 'active',
                'team_manager_id' => $manager->id,
            ]));
        }

        return $recruiters;
    }

    private function seedCandidates(Collection $recruiters, User $admin, int $count): Collection
    {
        $faker = fake();
        $statuses = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold'];
        $domains = ['Java', 'Data Science', 'QA Automation', 'DevOps', 'Python', 'Frontend'];
        $skills = [
            'Spring Boot', 'Microservices', 'Docker', 'Kubernetes', 'React', 'Node.js',
            'Selenium', 'Cypress', 'AWS', 'Azure', 'SQL', 'Power BI', 'Tableau', 'Python',
        ];
        $states = ['NY', 'TX', 'CA', 'NJ', 'FL', 'IL', 'WA', 'VA', 'PA', 'OH'];
        $visaStatuses = ['us_citizen', 'green_card', 'h1b', 'opt_f1', 'stem_opt', 'h4_ead'];
        $workAuthStatuses = ['applied_pending', 'not_applied', 'already_obtained'];

        $candidates = collect();

        for ($i = 1; $i <= $count; $i++) {
            $recruiter = $recruiters->random();
            $firstName = $faker->firstName();
            $middleName = random_int(0, 1) ? strtoupper($faker->randomLetter()) : null;
            $lastName = $faker->lastName();
            $subDomain = collect($skills)->shuffle()->take(random_int(2, 4))->implode(',');
            $email = 'candidate' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '@skilluence.local';

            $payload = [
                'full_name' => trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName),
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'date_of_birth' => $faker->date('Y-m-d', '2002-01-01'),
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'nationality' => $faker->randomElement(['Indian', 'Nepalese', 'Pakistani', 'Bangladeshi']),
                'email_id' => $email,
                'phone_number' => '+1' . random_int(2000000000, 9999999999),
                'domain' => $faker->randomElement($domains),
                'sub_domain' => $subDomain,
                'ssn' => random_int(100, 999) . '-' . random_int(10, 99) . '-' . random_int(1000, 9999),
                'date_of_arrival_usa' => Carbon::now()->subDays(random_int(200, 1500))->toDateString(),
                'current_salary' => random_int(60000, 110000),
                'expected_salary' => random_int(75000, 130000),
                'street_address' => $faker->streetAddress(),
                'apartment_unit' => random_int(0, 1) ? 'Apt ' . random_int(1, 999) : null,
                'city' => $faker->city(),
                'state_province' => $faker->randomElement($states),
                'zip_code' => (string) random_int(10000, 99999),
                'country' => 'United States',
                'visa_immigration_status' => $faker->randomElement($visaStatuses),
                'work_auth_status' => $faker->randomElement($workAuthStatuses),
                'open_to_relocation' => (bool) random_int(0, 1),
                'preferred_city' => $faker->city(),
                'visa_expiry_date' => Carbon::now()->addDays(random_int(120, 1200))->toDateString(),
                'marketing_phone' => '+1' . random_int(2000000000, 9999999999),
                'marketing_email' => 'marketing' . $i . '@skilluence.local',
                'marketing_email_password' => self::LOGIN_PASSWORD,
                'marketing_linkedin_id' => 'linkedin-user-' . $i,
                'marketing_linkedin_password' => self::LOGIN_PASSWORD,
                'github_url' => 'https://github.com/candidate' . $i,
                'linkedin_url' => 'https://linkedin.com/in/candidate' . $i,
                'portfolio_url' => 'https://portfolio' . $i . '.example.com',
                'masters_university' => $faker->randomElement(['UT Dallas', 'ASU', 'NJIT', 'NEU']),
                'masters_program' => $faker->randomElement(['MS Computer Science', 'MS Data Science', 'MS IT']),
                'masters_start' => Carbon::now()->subYears(random_int(2, 4))->startOfYear()->toDateString(),
                'masters_end' => Carbon::now()->subYears(random_int(0, 1))->endOfYear()->toDateString(),
                'masters_country' => 'United States',
                'bachelors_university' => $faker->randomElement(['Mumbai University', 'Pune University', 'Delhi University']),
                'bachelors_program' => $faker->randomElement(['B.Tech CS', 'B.E. IT', 'B.Sc. Computer Science']),
                'bachelors_start' => Carbon::now()->subYears(random_int(7, 10))->startOfYear()->toDateString(),
                'bachelors_end' => Carbon::now()->subYears(random_int(4, 6))->endOfYear()->toDateString(),
                'bachelors_country' => $faker->randomElement(['India', 'Nepal']),
                'recruiter_notes' => $faker->sentence(8),
                'no_of_applications' => random_int(1, 12),
                'interviews_count' => 0,
                'status' => $faker->randomElement($statuses),
                'recruiter_id' => $recruiter->id,
                'team_manager_id' => $recruiter->team_manager_id,
                'created_by' => $admin->id,
                'login_password' => Hash::make(self::LOGIN_PASSWORD),
                'login_password_plain' => self::LOGIN_PASSWORD,
            ];

            $candidate = Candidate::create($this->filterCandidatePayload($payload));
            $candidates->push($candidate);
        }

        return $candidates;
    }

    private function seedInterviews(Collection $candidates, int $perCandidate): void
    {
        $roles = ['Java Developer', 'Data Engineer', 'QA Engineer', 'DevOps Engineer', 'Frontend Developer'];
        $companies = ['Acme Corp', 'BrightTech', 'Skyline Systems', 'CoreBridge', 'Nova Labs', 'NextWave'];
        $timezones = ['EST', 'CST', 'MST', 'PST'];
        $types = ['phone_call', 'virtual', 'on_site'];

        foreach ($candidates as $candidate) {
            for ($i = 1; $i <= $perCandidate; $i++) {
                $scheduledDate = now()->addDays(random_int(-2, 14))->toDateString();
                $hour = random_int(8, 18);
                $minute = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];
                $scheduledTime = sprintf('%02d:%02d:00', $hour, $minute);
                $company = $companies[array_rand($companies)];

                Interview::create([
                    'candidate_id' => $candidate->id,
                    'recruiter_id' => $candidate->recruiter_id,
                    'role' => $roles[array_rand($roles)],
                    'company_name' => $company,
                    'company_domain' => 'https://' . Str::slug($company) . '.com',
                    'mail_date' => Carbon::parse($scheduledDate)->subDay()->toDateString(),
                    'mail_time' => sprintf('%02d:%02d:00', random_int(8, 18), random_int(0, 1) ? 0 : 30),
                    'interview_type' => $types[array_rand($types)],
                    'interview_status' => random_int(0, 100) <= 80 ? 'valid' : 'invalid',
                    'remark' => 'Interview ' . $i . ' scheduled for demo data.',
                    'scheduled_date' => $scheduledDate,
                    'scheduled_time' => $scheduledTime,
                    'scheduled_timezone' => $timezones[array_rand($timezones)],
                    'created_by' => $candidate->team_manager_id ?: $candidate->recruiter_id,
                ]);
            }
        }
    }

    private function seedDailyLogs(Collection $candidates, int $days): void
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $interviewCountByCandidateDate = Interview::query()
            ->selectRaw('candidate_id, scheduled_date, COUNT(*) as cnt')
            ->whereNotNull('scheduled_date')
            ->groupBy('candidate_id', 'scheduled_date')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = $row->candidate_id . '|' . Carbon::parse($row->scheduled_date)->toDateString();
                return [$key => (int) $row->cnt];
            });

        foreach ($candidates as $candidate) {
            for ($offset = 0; $offset < $days; $offset++) {
                $logDate = $startDate->copy()->addDays($offset)->toDateString();
                $key = $candidate->id . '|' . $logDate;

                DailyLog::create([
                    'candidate_id' => $candidate->id,
                    'recruiter_id' => $candidate->recruiter_id,
                    'log_date' => $logDate,
                    'applications' => random_int(2, 15),
                    'assistant_count' => random_int(1, 8),
                    'interview_count' => (int) ($interviewCountByCandidateDate[$key] ?? 0),
                    'remark' => 'Auto-seeded demo activity log.',
                    'created_by' => random_int(0, 1)
                        ? $candidate->recruiter_id
                        : ($candidate->team_manager_id ?: $candidate->recruiter_id),
                ]);
            }
        }
    }

    private function seedAttentionCheckCandidates(Collection $recruiters, User $admin): void
    {
        $olderRecruiter = $recruiters->random();
        $recentRecruiter = $recruiters->random();

        $olderCreatedAt = now()->subDays(25)->startOfDay();
        $recentCreatedAt = now()->subDays(10)->startOfDay();

        Candidate::create($this->filterCandidatePayload([
            'full_name' => 'Attention Test Old No Interview',
            'first_name' => 'Attention',
            'last_name' => 'Old',
            'email_id' => 'attention-old-no-interview@skilluence.local',
            'domain' => 'QA Automation',
            'status' => 'active',
            'no_of_applications' => 5,
            'interviews_count' => 0,
            'recruiter_id' => $olderRecruiter->id,
            'team_manager_id' => $olderRecruiter->team_manager_id,
            'created_by' => $admin->id,
            'login_password' => Hash::make(self::LOGIN_PASSWORD),
            'login_password_plain' => self::LOGIN_PASSWORD,
            'created_at' => $olderCreatedAt,
            'updated_at' => $olderCreatedAt,
        ]));

        Candidate::create($this->filterCandidatePayload([
            'full_name' => 'Attention Test Recent Untouched',
            'first_name' => 'Attention',
            'last_name' => 'Recent',
            'email_id' => 'attention-recent-untouched@skilluence.local',
            'domain' => 'Java',
            'status' => 'active',
            'no_of_applications' => 5,
            'interviews_count' => 0,
            'recruiter_id' => $recentRecruiter->id,
            'team_manager_id' => $recentRecruiter->team_manager_id,
            'created_by' => $admin->id,
            'login_password' => Hash::make(self::LOGIN_PASSWORD),
            'login_password_plain' => self::LOGIN_PASSWORD,
            'created_at' => $recentCreatedAt,
            'updated_at' => $recentCreatedAt,
        ]));
    }

    private function syncInterviewCounts(): void
    {
        $counts = Interview::query()
            ->selectRaw('candidate_id, COUNT(*) as cnt')
            ->groupBy('candidate_id')
            ->pluck('cnt', 'candidate_id');

        Candidate::query()->get(['id'])->each(function (Candidate $candidate) use ($counts) {
            $candidate->update(['interviews_count' => (int) ($counts[$candidate->id] ?? 0)]);
        });
    }

    private function filterCandidatePayload(array $payload): array
    {
        static $candidateColumns = null;

        if ($candidateColumns === null) {
            $candidateColumns = array_flip(Schema::getColumnListing('candidates'));
        }

        return array_intersect_key($payload, $candidateColumns);
    }
}
