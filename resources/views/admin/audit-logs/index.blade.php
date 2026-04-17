@extends('layouts.admin')
@section('title', 'Audit Logs')
@section('module-title', 'Audit Logs')
@section('module-description', 'Track all user actions with actor details and timestamped changes.')
@section('content')

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">Audit Logs</div>
            <div class="card-subtitle">{{ $logs->total() }} total entries</div>
        </div>

        <div class="d-flex gap-8" style="flex-wrap:wrap;">
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'recruiters'])) }}"
                class="btn {{ $tab === 'recruiters' ? 'btn-primary' : 'btn-outline' }}">Recruiters</a>
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'students'])) }}"
                class="btn {{ $tab === 'students' ? 'btn-primary' : 'btn-outline' }}">Students</a>
            <a href="{{ route('admin.audit-logs', array_merge(request()->except('tab'), ['tab' => 'all'])) }}"
                class="btn {{ $tab === 'all' ? 'btn-primary' : 'btn-outline' }}">All</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-logs') }}" class="d-flex gap-8 mb-16" style="flex-wrap:wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" class="form-control" placeholder="Search actor, module, description..."
            value="{{ $search }}" style="width:260px;">
        <select name="action" class="form-control" style="width:180px;" onchange="this.form.submit()">
            <option value="">All Actions</option>
            @foreach ($actionOptions as $actionOption)
                <option value="{{ $actionOption }}" @selected($action === $actionOption)>{{ ucfirst(str_replace('_', ' ', $actionOption)) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
        @if ($search || $action)
            <a href="{{ route('admin.audit-logs', ['tab' => $tab]) }}" class="btn btn-outline" title="Clear filters">
                <i class="bi bi-x-lg"></i>
            </a>
        @endif
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User / Recruiter</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>Data Changes</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $log)
                    <tr>
                        <td class="text-muted text-sm">{{ $logs->firstItem() + $i }}</td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm" style="width:26px;height:26px;font-size:10px;">
                                    {{ strtoupper(substr($log->actor_name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="avatar-name">{{ $log->actor_name ?? 'System' }}</div>
                                    <div class="avatar-sub">{{ ucfirst($log->actor_type) }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ in_array($log->action, ['created', 'login', 'downloaded']) ? 'badge-success' : (in_array($log->action, ['updated', 'imported', 'exported']) ? 'badge-primary' : (in_array($log->action, ['deleted', 'delete_blocked']) ? 'badge-danger' : 'badge-warning')) }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $log->module)) }}</span></td>
                        <td style="max-width:280px;">{{ $log->description }}</td>
                        <td style="max-width:220px;">
                            @if (!empty($log->old_values) || !empty($log->new_values))
                                <div class="text-sm text-muted">
                                    @if (!empty($log->old_values))
                                        <div>Old: {{ implode(', ', array_keys($log->old_values)) }}</div>
                                    @endif
                                    @if (!empty($log->new_values))
                                        <div>New: {{ implode(', ', array_keys($log->new_values)) }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted text-sm">-</span>
                            @endif
                        </td>
                        <td class="text-muted text-sm" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                            {{ $log->created_at->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="page-empty mb-0">
                                <i class="bi bi-journal-text"></i>
                                <p>No audit entries found{{ ($search || $action) ? ' matching your filters' : '' }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">
                Showing {{ $logs->firstItem() }}-{{ $logs->lastItem() }} of {{ $logs->total() }} entries
            </span>
            {{ $logs->links('pagination.custom') }}
        </div>
    @endif
</div>

@endsection
