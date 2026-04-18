<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user        = $request->user();
        $isAdmin     = $user->isAdmin();
        $isRecruiter = $user->isRecruiter();

        // Resolve chart date range
        $range       = $request->get('range', 'week');
        $customStart = $request->get('custom_start');
        $customEnd   = $request->get('custom_end');

        [$rangeStart, $rangeEnd, $rangeLabel] = $this->resolveRange($range, $customStart, $customEnd);

        // Base query closure — filters by recruiter when needed
        $base = fn () => Candidate::query()
            ->when($isRecruiter, fn ($q) => $q->where('recruiter_id', $user->id));

        $activeCandidateStatuses = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold'];

        $stats = [
            'active_candidates'          => $base()->whereIn('status', $activeCandidateStatuses)->count(),
            'active_recruiters'          => $isAdmin ? User::recruiters()->active()->count() : 1,
            'today_applications'         => 0,
            'today_interviews'           => 0,
            'today_scheduled_interviews' => $base()->where('status', 'interview')
                ->whereDate('updated_at', today())->count(),
            'today_pending_applications' => $base()->whereIn('status', ['active', 'enrolled'])
                ->where('no_of_applications', '>', 0)->count(),
        ];

        // Derive today's application / interview deltas from audit logs
        $todayLogsQuery = AuditLog::where('module', 'candidates')
            ->whereDate('created_at', today())
            ->whereIn('action', ['created', 'updated'])
            ->when($isRecruiter, fn ($q) => $q->where('user_id', $user->id));

        foreach ($todayLogsQuery->get(['action', 'old_values', 'new_values']) as $log) {
            $oldApps  = (int) data_get($log->old_values, 'no_of_applications', 0);
            $newApps  = (int) data_get($log->new_values, 'no_of_applications', 0);
            $oldIntvw = (int) data_get($log->old_values, 'interviews_count', 0);
            $newIntvw = (int) data_get($log->new_values, 'interviews_count', 0);

            if ($log->action === 'created') {
                $stats['today_applications'] += $newApps;
                $stats['today_interviews']   += $newIntvw;
                continue;
            }

            $stats['today_applications'] += max($newApps - $oldApps, 0);
            $stats['today_interviews']   += max($newIntvw - $oldIntvw, 0);
        }

        // Top performers & recruiter table — admin only
        $topPerformers       = collect();
        $recruiterPerformance = collect();

        if ($isAdmin) {
            $monthStart = Carbon::now()->startOfMonth();

            $topPerformers = User::recruiters()
                ->withCount('candidates')
                ->withSum(['candidates as monthly_applications' => fn ($q) => $q->where('created_at', '>=', $monthStart)], 'no_of_applications')
                ->get()
                ->sortByDesc('monthly_applications')
                ->take(5)
                ->values();

            $recruiterPerformance = User::recruiters()
                ->withCount('candidates')
                ->withSum('candidates', 'no_of_applications')
                ->withSum('candidates', 'interviews_count')
                ->get()
                ->map(function (User $rec) {
                    $placed = Candidate::where('recruiter_id', $rec->id)->where('status', 'placed')->count();
                    $rec->placed_count = $placed;
                    $rec->success_rate = $rec->candidates_count > 0
                        ? round(($placed / $rec->candidates_count) * 100, 1)
                        : 0;
                    return $rec;
                });
        }

        // Last N candidates in range
        $last7DaysCandidates = $base()
            ->with('recruiter:id,name')
            ->whereDate('created_at', '>=', $rangeStart)
            ->when($rangeEnd, fn ($q) => $q->whereDate('created_at', '<=', $rangeEnd))
            ->orderBy('created_at', 'desc')
            ->get(['id', 'full_name', 'created_at', 'recruiter_id', 'status']);

        // Chart data for the range
        $chartRaw = $base()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereDate('created_at', '>=', $rangeStart)
            ->when($rangeEnd, fn ($q) => $q->whereDate('created_at', '<=', $rangeEnd))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day');

        $chartLabels = [];
        $chartData   = [];

        $endDate = $rangeEnd ?? Carbon::today();
        foreach (CarbonPeriod::create($rangeStart, $endDate) as $date) {
            $chartLabels[] = $date->format($range === 'year' ? 'M' : 'M d');
            $chartData[]   = (int) ($chartRaw[$date->format('Y-m-d')] ?? 0);
        }

        // For year range: aggregate by month instead of day
        if ($range === 'year') {
            $monthlyRaw = $base()
                ->selectRaw("DATE_TRUNC('month', created_at) as month, COUNT(*) as total")
                ->whereDate('created_at', '>=', $rangeStart)
                ->groupBy(DB::raw("DATE_TRUNC('month', created_at)"))
                ->orderBy(DB::raw("DATE_TRUNC('month', created_at)"))
                ->pluck('total', 'month');

            $chartLabels = [];
            $chartData   = [];
            for ($m = 0; $m < 12; $m++) {
                $monthDate     = Carbon::today()->startOfYear()->addMonths($m);
                $chartLabels[] = $monthDate->format('M');
                $key           = $monthDate->format('Y-m-d') . ' 00:00:00';
                $chartData[]   = (int) ($monthlyRaw[$key] ?? 0);
            }
        }

        $statusDistribution = $base()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.dashboard', [
            'stats'               => $stats,
            'topPerformers'       => $topPerformers,
            'recruiterPerformance'=> $recruiterPerformance,
            'last7DaysCandidates' => $last7DaysCandidates,
            'trendChartLabels'    => $chartLabels,
            'trendChartData'      => $chartData,
            'statusChartLabels'   => $statusDistribution->keys()->map(fn ($s) => ucfirst($s))->values(),
            'statusChartData'     => $statusDistribution->values(),
            'isAdmin'             => $isAdmin,
            'isRecruiter'         => $isRecruiter,
            'activeRange'         => $range,
            'customStart'         => $customStart,
            'customEnd'           => $customEnd,
            'rangeLabel'          => $rangeLabel,
        ]);
    }

    private function resolveRange(string $range, ?string $customStart, ?string $customEnd): array
    {
        return match ($range) {
            'today'  => [Carbon::today(), Carbon::today(), 'Today'],
            'month'  => [Carbon::today()->startOfMonth(), Carbon::today(), 'This Month'],
            'year'   => [Carbon::today()->startOfYear(), Carbon::today(), 'This Year'],
            'custom' => [
                $customStart ? Carbon::parse($customStart)->startOfDay() : Carbon::today()->subDays(6),
                $customEnd   ? Carbon::parse($customEnd)->endOfDay()     : Carbon::today(),
                'Custom Range',
            ],
            default  => [Carbon::today()->subDays(6), Carbon::today(), 'Last 7 Days'],
        };
    }
}
