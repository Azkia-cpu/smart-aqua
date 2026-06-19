@extends('layouts.dashboard')

@section('title', 'Manajemen Kolam')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-semibold" style="color:var(--sa-white)">
                <i class="bi bi-droplet-half text-cyan me-2"></i>Manajemen Kolam
            </h4>
            <p class="mb-0 small" style="color:var(--sa-medium)">Kelola semua kolam akuakultur Anda</p>
        </div>
        <a href="{{ route('admin.ponds.create') }}" class="sa-admin-btn create">
            <i class="bi bi-plus-lg"></i> Tambah Kolam
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 mb-4"
             style="background:rgba(6,214,160,.12);color:var(--sa-success)">
            {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="sa-glass-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrapper">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Pengguna</th>
                            <th>Status</th>
                            <th>Threshold Air</th>
                            <th>Threshold pH</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ponds as $pond)
                            <tr>
                                <td class="fw-medium">{{ $pond->name }}</td>
                                <td>
                                    <span class="sa-token-mask">{{ $pond->code }}</span>
                                </td>
                                <td>{{ $pond->user->name ?? '—' }}</td>
                                <td>
                                    <span class="sa-badge-status {{ $pond->is_active ? 'active' : 'inactive' }}">
                                        <span class="sa-status-dot {{ $pond->is_active ? 'normal' : 'danger' }}"></span>
                                        {{ $pond->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $pond->water_level_min ?? '-' }} — {{ $pond->water_level_max ?? '-' }} cm
                                </td>
                                <td class="small">
                                    {{ $pond->ph_min ?? '-' }} — {{ $pond->ph_max ?? '-' }}
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('admin.ponds.edit', $pond) }}" class="sa-admin-btn edit">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.ponds.destroy', $pond) }}"
                                              onsubmit="return confirm('Yakin ingin menghapus kolam ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sa-admin-btn delete">
                                                <i class="bi bi-trash3"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4" style="color:var(--sa-medium)">
                                    <i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem"></i>
                                    Belum ada kolam terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
