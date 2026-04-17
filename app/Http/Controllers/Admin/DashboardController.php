<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $activeCandidateStatuses = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold'];

        $stats = [
            'active_candidates' => Candidate::whereIn('status', $activeCandidateStatuses)->count(),
            'active_recruiters' => User::recruiters()->active()->count(),
            'today_applications' => 0,
            'today_interviews' => 0,
            'total_candidates' => Candidate::count(),
            'total_recruiters' => User::recruiters()->count(),
            'placed_candidates' => Candidate::where('status', 'placed')->count(),
        ];

        $todayCandidateLogs = AuditLog::where('module', 'candidates')
            ->whereDate('created_at', today())
            ->whereIn('action', ['created', 'updated'])
            ->get(['action', 'old_values', 'new_values']);

        foreach ($todayCandidateLogs as $log) {
            $oldApplications = (int) data_get($log->old_values, 'no_of_applications', 0);
            $newApplications = (int) data_get($log->new_values, 'no_of_applications', 0);
            $oldInterviews = (int) data_get($log->old_values, 'interviews_count', 0);
            $newInterviews = (int) data_get($log->new_values, 'interviews_count', 0);

            if ($log->action === 'created') {
                $stats['today_applications'] += $newApplications;
                $stats['today_interviews'] += $newInterviews;
                continue;
            }

            $stats['today_applications'] += max($newApplications - $oldApplications, 0);
            $stats['today_interviews'] += max($newInterviews - $oldInterviews, 0);
        }

        $recruiterPerformance = User::recruiters()
            ->withCount('candidates')
            ->withSum('candidates', 'no_of_applications')
            ->withSum('candidates', 'interviews_count')
            ->get()
            ->map(function (User $user) {
                $placed = Candidate::where('recruiter_id', $user->id)
                    ->where('status', 'placed')
                    ->count();

                $user->placed_count = $placed;
                $user->success_rate = $user->candidates_count > 0
                    ? round(($placed / $user->candidates_count) * 100, 1)
                    : 0;

                return $user;
            });

        $topPerformers = $recruiterPerformance
            ->sortByDesc('success_rate')
            ->take(5)
            ->values();

        $conversionRate = $stats['total_candidates'] > 0
            ? round(($stats['placed_candidates'] / $stats['total_candidates']) * 100, 1)
            : 0;

        $last7DaysRaw = Candidate::selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', Carbon::today()->subDays(6))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $last7DaysRows = [];
        $chartLabels = [];
        $chartData = [];

        foreach (CarbonPeriod::create(Carbon::today()->subDays(6), Carbon::today()) as $date) {
            $dayKey = $date->format('Y-m-d');
            $count = (int) ($last7DaysRaw[$dayKey] ?? 0);

            $last7DaysRows[] = [
                'date' => $date->format('M d, Y'),
                'count' => $count,
            ];

            $chartLabels[] = $date->format('D');
            $chartData[] = $count;
        }

        $statusDistribution = Candidate::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'stats' => $stats,
            'recruiterPerformance' => $recruiterPerformance,
            'topPerformers' => $topPerformers,
            'conversionRate' => $conversionRate,
            'last7DaysRows' => $last7DaysRows,
            'trendChartLabels' => $chartLabels,
            'trendChartData' => $chartData,
            'statusChartLabels' => $statusDistribution->keys()->map(fn ($s) => ucfirst($s))->values(),
            'statusChartData' => $statusDistribution->values(),
        ]);
    }
}
