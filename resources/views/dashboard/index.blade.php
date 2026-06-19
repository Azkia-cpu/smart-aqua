@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

{{-- ========== ROW 1: Level Air | pH Air | Pompa Otomatis ========== --}}
<div class="row g-3 mb-3">

    {{-- LEVEL AIR --}}
    <div class="col-lg-4 col-md-6">
        <div class="sa-card sa-card-metric">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="sa-metric-icon water">
                    <i class="bi bi-droplet-fill"></i>
                </div>
                <h2 class="sa-card-title mb-0">LEVEL AIR</h2>
            </div>
            <div class="d-flex align-items-end gap-2 mb-2">
                <span class="sa-metric-value" id="water-level-value">--</span>
                <span class="sa-metric-unit">cm</span>
            </div>
            <div class="mb-3">
                <span class="sa-badge-status normal" id="water-level-status">NORMAL</span>
            </div>
            <div class="sa-threshold-info">
                <i class="bi bi-info-circle me-1"></i>
                Pompa <strong>ON</strong> ketika air &lt; <span class="sa-threshold-min-level">{{ $currentPond->min_water_level ?? 3 }}</span> cm<br>
                <i class="bi bi-info-circle me-1"></i>
                Pompa <strong>OFF</strong> ketika air &gt; <span class="sa-threshold-max-level">{{ $currentPond->max_water_level ?? 12 }}</span> cm
            </div>
        </div>
    </div>

    {{-- pH AIR --}}
    <div class="col-lg-4 col-md-6">
        <div class="sa-card sa-card-metric">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="sa-metric-icon ph">
                    <span class="sa-ph-text">pH</span>
                </div>
                <h2 class="sa-card-title mb-0">pH AIR</h2>
            </div>
            <div class="d-flex align-items-end gap-2 mb-2">
                <span class="sa-metric-value" id="ph-value">--</span>
            </div>
            <div class="mb-3">
                <span class="sa-badge-status normal" id="ph-status">NORMAL</span>
            </div>
            <div class="sa-threshold-info">
                <i class="bi bi-info-circle me-1"></i>
                Pompa <strong>ON</strong> ketika pH asam &lt; <span class="sa-threshold-min-ph">{{ $currentPond->min_ph ?? 6.5 }}</span><br>
                <i class="bi bi-info-circle me-1"></i>
                Pompa <strong>OFF</strong> ketika pH &gt; <span class="sa-threshold-max-ph">{{ $currentPond->max_ph ?? 8.5 }}</span>
            </div>
        </div>
    </div>

    {{-- POMPA OTOMATIS --}}
    <div class="col-lg-4 col-md-12">
        <div class="sa-card sa-card-pump">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="sa-card-title mb-0">POMPA OTOMATIS</h2>
                <label class="sa-toggle">
                    <input type="checkbox" id="pumpAutoToggle">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="sa-toggle-label-row mb-3">
                <span class="sa-toggle-label-text" id="pumpAutoLabel">AKTIF</span>
            </div>

            <div class="sa-pump-info-row mb-2">
                <span class="sa-pump-status-label">STATUS</span>
                <span class="sa-pump-status-value off" id="pump-auto-status">MATI</span>
            </div>
            <div class="sa-pump-info-row mb-3">
                <span class="sa-pump-status-label">VOLUME HARI INI</span>
                <span class="sa-pump-status-value" id="pump-auto-volume">-- L</span>
            </div>

        </div>
    </div>
</div>

{{-- ========== ROW 2: Grafik Level Air | Grafik pH Air | Pompa Manual ========== --}}
<div class="row g-3 mb-3">

    {{-- GRAFIK LEVEL AIR --}}
    <div class="col-lg-4 col-md-6">
        <div class="sa-card">
            <h2 class="sa-card-title">GRAFIK LEVEL AIR (48 JAM)</h2>
            <div class="sa-chart-container">
                <canvas id="waterLevelChart"></canvas>
            </div>
        </div>
    </div>

    {{-- GRAFIK pH AIR --}}
    <div class="col-lg-4 col-md-6">
        <div class="sa-card">
            <h2 class="sa-card-title">GRAFIK pH AIR (48 JAM)</h2>
            <div class="sa-chart-container">
                <canvas id="phChart"></canvas>
            </div>
        </div>
    </div>

    {{-- POMPA MANUAL --}}
    <div class="col-lg-4 col-md-12">
        <div class="sa-card sa-card-pump">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="sa-card-title mb-0">POMPA MANUAL</h2>
                <label class="sa-toggle danger">
                    <input type="checkbox" id="pumpManualToggle">
                    <span class="slider"></span>
                </label>
            </div>
            <div class="sa-toggle-label-row mb-3">
                <span class="sa-toggle-label-text" id="pumpManualLabel">NONAKTIF</span>
            </div>

            <div class="sa-pump-info-row mb-2">
                <span class="sa-pump-status-label">STATUS</span>
                <span class="sa-pump-status-value off" id="pump-manual-status">MATI</span>
            </div>
            <div class="sa-pump-info-row mb-3">
                <span class="sa-pump-status-label">VOLUME HARI INI</span>
                <span class="sa-pump-status-value" id="pump-manual-volume">-- L</span>
            </div>

            {{-- Manual ON/OFF Buttons --}}
            <div class="sa-manual-buttons" id="manualButtons" style="display: none;">
                <button class="btn sa-btn-pump-on" id="pumpOnBtn">
                    <i class="bi bi-play-fill me-1"></i> NYALAKAN
                </button>
                <button class="btn sa-btn-pump-off" id="pumpOffBtn">
                    <i class="bi bi-stop-fill me-1"></i> MATIKAN
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========== ROW 3: Notifikasi Terbaru | Riwayat Monitoring ========== --}}
<div class="row g-3 mb-3">

    {{-- NOTIFIKASI TERBARU --}}
    <div class="col-lg-6">
        <div class="sa-card">
            <h2 class="sa-card-title">NOTIFIKASI TERBARU</h2>
            <div id="notificationPanel">
                <ul class="sa-notification-list" id="notificationList">
                    <li class="sa-notification-item" style="justify-content:center;color:var(--sa-text-muted);">
                        <i class="bi bi-arrow-repeat sa-spin me-2"></i> Memuat notifikasi...
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- RIWAYAT MONITORING --}}
    <div class="col-lg-6">
        <div class="sa-card">
            <h2 class="sa-card-title">RIWAYAT MONITORING</h2>
            <div class="sa-table-wrapper" id="historyTable">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>WAKTU</th>
                            <th>LEVEL</th>
                            <th>pH</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody">
                        <tr>
                            <td colspan="4" style="text-align:center;color:var(--sa-text-muted);">
                                <i class="bi bi-arrow-repeat sa-spin me-2"></i> Memuat riwayat...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
