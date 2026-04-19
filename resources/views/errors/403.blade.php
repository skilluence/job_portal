@extends('errors.layout')

@section('code', '403')

@section('icon')
<div class="err-icon-wrap" style="background:#fef2f2;color:#dc2626;">
    {{-- Bootstrap Icons: lock-fill (inline SVG — no font dependency) --}}
    <svg width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
    </svg>
</div>
@endsection

@section('title', 'Access Denied')

@section('description', 'You don\'t have permission to access this page. If you believe this is a mistake, please contact your administrator.')
