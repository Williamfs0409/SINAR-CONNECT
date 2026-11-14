@php
    $activeFeature = null;
    foreach ($dashboardMenu as $menuItem) {
        if ($menuItem['id'] === $dashboardSection) {
            $activeFeature = $menuItem;
            break;
        }
    }

    $featureDetails = $dashboardFeatureDetails[$dashboardSection] ?? null;

    if ($activeFeature === null || $featureDetails === null) {
        abort(404);
    }

    $dashboardHomeUrl = route('dashboard', auth()->guest() ? ['role' => $dashboardRoleKey] : []);
@endphp

<nav class="role-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ $dashboardHomeUrl }}">Dashboard</a><span aria-hidden="true">›</span>{{ $activeFeature['label'] }}
</nav>

<section class="role-feature-heading">
    <div>
        <p>{{ $dashboardRoleName }} · SINAR</p>
        <h1>{{ $activeFeature['label'] }}</h1>
        <span>{{ $featureDetails['description'] }}</span>
    </div>
    <span class="role-feature-period">Semester ganjil 2026/2027</span>
</section>

<section class="role-feature-stats" aria-label="Ringkasan {{ $activeFeature['label'] }}">
    @foreach ($featureDetails['stats'] as [$label, $value])
        <article class="role-feature-stat">
            <span>{{ $label }}</span>
            <strong>{{ $value }}</strong>
        </article>
    @endforeach
</section>

<section class="role-panel role-feature-table-panel">
    <div class="role-panel-heading">
        <div>
            <h2>{{ $activeFeature['label'] }}</h2>
            <p>{{ $featureDetails['description'] }}</p>
        </div>
        <span class="role-feature-count">{{ count($featureDetails['rows']) }} data contoh</span>
    </div>
    @if ($dashboardSection === 'jadwal')
        <div data-schedule-widget>
            @include('dashboard.schedule-picker')
        </div>
    @endif
    <div class="role-table-wrap">
        <table class="role-table role-feature-table">
            <thead>
                <tr>
                    @foreach ($featureDetails['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($featureDetails['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="role-feature-note">Informasi pada halaman ini merupakan data contoh dashboard.</p>
</section>
