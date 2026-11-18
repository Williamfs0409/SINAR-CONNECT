@extends('layouts.app')

@php
    $user = auth()->user();
    $previewRoles = ['teacher', 'principal', 'parent'];
    $requestedRole = request()->query('role', 'teacher');
    $role = $user
        ? $user->role
        : (is_string($requestedRole) && in_array($requestedRole, $previewRoles, true) ? $requestedRole : 'teacher');
    $dashboardDisplayName = $user
        ? $user->name
        : ($role === 'teacher' ? 'Ibu Dewi' : ($role === 'principal' ? 'Ratna' : 'Bapak Andi'));
    $dashboardTeacherName = $user ? $user->name : 'Dewi Puspita';
    $dashboardSection = $section ?? 'ringkasan';
@endphp

@section('title', 'Dashboard — SINAR')
@section('body-class', 'dashboard-page')

@section('content')
    @if ($role === 'admin')
        @include('dashboard.admin', [
            'adminSection' => $adminSection ?? 'ringkasan',
            'adminDashboardData' => $adminDashboardData ?? [],
            'passwordResetAttentionCount' => $passwordResetAttentionCount ?? 0,
        ])
    @elseif ($role === 'teacher')
        @include('dashboard.teacher')
    @elseif ($role === 'parent')
        @include('dashboard.parent')
    @elseif ($role === 'principal')
        @include('dashboard.principal')
    @else
        @php(abort(403))
    @endif
@endsection
