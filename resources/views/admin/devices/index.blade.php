@extends('layouts.dashboard')

@section('title', 'Manajemen Perangkat')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-semibold" style="color:var(--sa-white)">
                <i class="bi bi-cpu text-cyan me-2"></i>Manajemen Perangkat & Token
            </h4>
            <p class="mb-0 small" style="color:var(--sa-medium)">Kelola token autentikasi perangkat IoT untuk setiap kolam</p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 mb-4"
             style="background:rgba(6,214,160,.12);color:var(--sa-success)">
            {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('token'))
        <div class="alert alert-dismissible fade show rounded-3 border-0 mb-4"
             style="background:rgba(0,180,216,.12);color:var(--sa-primary-cyan);border:1px solid rgba(0,180,216,.2)!important">
            <strong>Token Baru Dibuat!</strong> Salin sekarang — token tidak akan ditampilkan lagi:
            <div class="mt-2 d-flex align-items-center gap-2">
                <code class="sa-token-mask" id="newTokenDisplay" style="font-size:.9rem;user-select:all">{{ session('token') }}</code>
                <button type="button" class="sa-copy-btn" onclick="copyNewToken()" title="Salin token">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="sa-glass-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrapper">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Kolam</th>
                            <th>Nama Perangkat</th>
                            <th>Token</th>
                            <th>Terakhir Digunakan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td class="fw-medium">{{ $device->pond->name ?? '-' }}</td>
                                <td>{{ $device->name }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="sa-token-mask" id="token-{{ $device->id }}">
                                            {{ $device->masked_token ?? '••••••••••••' }}
                                        </span>
                                        @if($device->plain_token ?? false)
                                        <button type="button" class="sa-reveal-btn"
                                                onclick="toggleReveal(this, '{{ $device->plain_token }}')"
                                                title="Tampilkan/Sembunyikan" data-revealed="false">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @endif
                                        <button type="button" class="sa-copy-btn"
                                                onclick="copyToken('token-{{ $device->id }}')"
                                                title="Salin token">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="small">
                                    {{ $device->last_used_at ? $device->last_used_at->diffForHumans() : 'Belum pernah' }}
                                </td>
                                <td>
                                    <span class="sa-badge-status {{ $device->is_active ? 'active' : 'inactive' }}">
                                        <span class="sa-status-dot {{ $device->is_active ? 'normal' : 'danger' }}"></span>
                                        {{ $device->is_active ? 'Aktif' : 'Dicabut' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        {{-- Generate new token --}}
                                        <form method="POST" action="{{ route('admin.devices.regenerate', $device) }}"
                                              onsubmit="return confirm('Generate token baru? Token lama akan tidak berlaku.')">
                                            @csrf
                                            <button type="submit" class="sa-admin-btn edit">
                                                <i class="bi bi-arrow-clockwise"></i> Regenerate
                                            </button>
                                        </form>

                                        {{-- Revoke token --}}
                                        @if($device->is_active)
                                        <form method="POST" action="{{ route('admin.devices.revoke', $device) }}"
                                              onsubmit="return confirm('Cabut token perangkat ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="sa-admin-btn delete">
                                                <i class="bi bi-shield-x"></i> Cabut
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4" style="color:var(--sa-medium)">
                                    <i class="bi bi-cpu d-block mb-2" style="font-size:1.5rem"></i>
                                    Belum ada perangkat terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Generate Token per Pond --}}
    @if(isset($ponds) && $ponds->count())
    <div class="sa-glass-card mt-4">
        <div class="sa-card-body">
            <div class="sa-section-title">
                <i class="bi bi-key"></i> Generate Token Baru
            </div>
            <form method="POST" action="{{ route('admin.devices.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-sm-4">
                    <label for="pond_id" class="sa-form-label">Kolam</label>
                    <select name="pond_id" id="pond_id" class="form-select sa-form-select" required>
                        <option value="">— Pilih Kolam —</option>
                        @foreach($ponds as $pond)
                            <option value="{{ $pond->id }}">{{ $pond->name }} ({{ $pond->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-4">
                    <label for="device_name" class="sa-form-label">Nama Perangkat</label>
                    <input type="text" name="name" id="device_name"
                           class="form-control sa-form-control"
                           placeholder="Contoh: ESP32-Sensor-A1" required>
                </div>
                <div class="col-12 col-sm-4">
                    <button type="submit" class="sa-form-submit w-100">
                        <i class="bi bi-key me-1"></i> Generate Token
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

@endsection

@section('scripts')
<script>
    function copyToken(elementId) {
        var el = document.getElementById(elementId);
        if (!el) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(function () {
            showCopyFeedback(el);
        });
    }

    function copyNewToken() {
        var el = document.getElementById('newTokenDisplay');
        if (!el) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(function () {
            showCopyFeedback(el);
        });
    }

    function showCopyFeedback(el) {
        var orig = el.style.borderColor;
        el.style.borderColor = 'var(--sa-success)';
        el.style.boxShadow = '0 0 8px rgba(6, 214, 160, 0.3)';
        setTimeout(function () {
            el.style.borderColor = orig;
            el.style.boxShadow = '';
        }, 1200);
    }

    function toggleReveal(btn, fullToken) {
        var revealed = btn.dataset.revealed === 'true';
        var span = btn.previousElementSibling;
        if (!span) return;

        if (revealed) {
            span.textContent = '••••••••••••';
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            btn.dataset.revealed = 'false';
        } else {
            span.textContent = fullToken;
            btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
            btn.dataset.revealed = 'true';
        }
    }
</script>
@endsection
