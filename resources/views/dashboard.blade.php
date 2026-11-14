@extends('layouts.app')

@php
    $user = auth()->user();
    $previewRoles = ['teacher', 'principal', 'parent', 'admin'];
    $requestedRole = request()->query('role', 'teacher');
    $role = $user
        ? $user->role
        : (is_string($requestedRole) && in_array($requestedRole, $previewRoles, true) ? $requestedRole : 'teacher');
    $dashboardDisplayName = $user
        ? $user->name
        : ($role === 'teacher' ? 'Ibu Dewi' : ($role === 'principal' ? 'Ratna' : ($role === 'parent' ? 'Bapak Andi' : 'Administrator')));
    $dashboardTeacherName = $user ? $user->name : 'Dewi Puspita';
    $dashboardSection = $section ?? 'ringkasan';
    $adminPreview = ! $user && $role === 'admin';
@endphp

@if ($adminPreview && $dashboardSection !== 'ringkasan')
    @php(abort(404))
@endif

@section('title', 'Dashboard — SINAR')
@section('body-class', 'dashboard-page')

@section('content')
    @if ($role === 'admin')
        @include('dashboard.admin', [
            'adminSection' => $adminSection ?? 'ringkasan',
            'adminDashboardData' => $adminDashboardData ?? [],
            'adminPreview' => $adminPreview,
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
