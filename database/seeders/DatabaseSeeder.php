<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@skilluence.com'],
            [
                'name'            => 'Admin User',
                'password'        => Hash::make('password'),
                'role'            => 'admin',
                'status'          => 'active',
                'team_manager_id' => null,
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@skilluence.com'],
            [
                'name'            => 'Team Manager',
                'password'        => Hash::make('password'),
                'role'            => 'manager',
                'status'          => 'active',
                'team_manager_id' => $admin->id,
            ]
        );

        $recruiter = User::updateOrCreate(
            ['email' => 'recruiter@skilluence.com'],
            [
                'name'            => 'Demo Recruiter',
                'password'        => Hash::make('password'),
                'role'            => 'recruiter',
                'status'          => 'active',
                'team_manager_id' => $manager->id,
            ]
        );

        Candidate::updateOrCreate(
            ['email_id' => 'candidate1@example.com'],
            [
                'full_name'                   => 'Candidate One',
                'enrollment_date'             => now()->subDays(10)->toDateString(),
                'sales_agent'                 => 'Sales Alpha',
                'no_of_applications'          => 8,
                'status'                      => 'interview',
                'recruiter_id'                => $recruiter->id,
                'linkedin_id'                 => 'linkedin.com/in/candidate-one',
                'linkedin_password'           => 'secret',
                'email_password'              => 'secret',
                'linkedin_updated'            => now()->subDays(2)->toDateString(),
                'address'                     => '123 Main Street, New York, NY',
                'profile'                     => 'PHP/Laravel candidate focused on backend development.',
                'notes'                       => 'Strong communication skills.',
                'login_password'              => Hash::make('password123'),
                'login_password_plain'        => 'password123',
                'interviews_count'            => 2,
                'created_by'                  => $admin->id,
                'cv_file_path'                => null,
                'candidate_details_file_path' => null,
            ]
        );

        Candidate::updateOrCreate(
            ['email_id' => 'candidate2@example.com'],
            [
                'full_name'                   => 'Candidate Two',
                'enrollment_date'             => now()->subDays(5)->toDateString(),
                'sales_agent'                 => 'Sales Beta',
                'no_of_applications'          => 5,
                'status'                      => 'offer',
                'recruiter_id'                => $recruiter->id,
                'linkedin_id'                 => 'linkedin.com/in/candidate-two',
                'linkedin_password'           => 'secret',
                'email_password'              => 'secret',
                'linkedin_updated'            => now()->subDay()->toDateString(),
                'address'                     => '456 Lake Road, Austin, TX',
                'profile'                     => 'Full-stack candidate with React and Laravel experience.',
                'notes'                       => 'Ready for final round.',
                'login_password'              => Hash::make('password123'),
                'login_password_plain'        => 'password123',
                'interviews_count'            => 3,
                'created_by'                  => $admin->id,
                'cv_file_path'                => null,
                'candidate_details_file_path' => null,
            ]
        );
    }
}
