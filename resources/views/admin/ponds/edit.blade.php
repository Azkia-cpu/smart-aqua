@extends('layouts.dashboard')

@section('title', 'Edit Kolam')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.ponds.index') }}" class="sa-form-cancel text-decoration-none d-inline-flex align-items-center gap-1 mb-3" style="font-size:.82rem">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <h4 class="mb-1 fw-semibold" style="color:var(--sa-white)">
            <i class="bi bi-pencil-square text-cyan me-2"></i>Edit Kolam — {{ $pond->name }}
        </h4>
        <p class="mb-0 small" style="color:var(--sa-medium)">Perbarui informasi kolam dan threshold</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="sa-glass-card">
                <div class="sa-card-body">
                    <form method="POST" action="{{ route('admin.ponds.update', $pond) }}">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="sa-form-label">Nama Kolam</label>
                            <input type="text" id="name" name="name"
                                   class="form-control sa-form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $pond->name) }}" required>
                            @error('name')
                                <div class="sa-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div class="mb-3">
                            <label for="code" class="sa-form-label">Kode Kolam</label>
                            <input type="text" id="code" name="code"
                                   class="form-control sa-form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $pond->code) }}" required>
                            @error('code')
                                <div class="sa-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- User Assignment --}}
                        <div class="mb-3">
                            <label for="user_id" class="sa-form-label">Pengguna (Pemilik)</label>
                            <select id="user_id" name="user_id"
                                    class="form-select sa-form-select @error('user_id') is-invalid @enderror">
                                <option value="">— Pilih Pengguna —</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('user_id', $pond->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="sa-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="mb-3">
                            <label for="is_active" class="sa-form-label">Status</label>
                            <select id="is_active" name="is_active"
                                    class="form-select sa-form-select @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $pond->is_active) ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !old('is_active', $pond->is_active) ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                            @error('is_active')
                                <div class="sa-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Water Level Thresholds --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="water_level_min" class="sa-form-label">Level Air Min (cm)</label>
                                <input type="number" step="0.1" id="water_level_min" name="water_level_min"
                                       class="form-control sa-form-control @error('water_level_min') is-invalid @enderror"
                                       value="{{ old('water_level_min', $pond->water_level_min) }}">
                                @error('water_level_min')
                                    <div class="sa-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="water_level_max" class="sa-form-label">Level Air Max (cm)</label>
                                <input type="number" step="0.1" id="water_level_max" name="water_level_max"
                                       class="form-control sa-form-control @error('water_level_max') is-invalid @enderror"
                                       value="{{ old('water_level_max', $pond->water_level_max) }}">
                                @error('water_level_max')
                                    <div class="sa-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- pH Thresholds --}}
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label for="ph_min" class="sa-form-label">pH Min</label>
                                <input type="number" step="0.1" id="ph_min" name="ph_min"
                                       class="form-control sa-form-control @error('ph_min') is-invalid @enderror"
                                       value="{{ old('ph_min', $pond->ph_min) }}">
                                @error('ph_min')
                                    <div class="sa-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="ph_max" class="sa-form-label">pH Max</label>
                                <input type="number" step="0.1" id="ph_max" name="ph_max"
                                       class="form-control sa-form-control @error('ph_max') is-invalid @enderror"
                                       value="{{ old('ph_max', $pond->ph_max) }}">
                                @error('ph_max')
                                    <div class="sa-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex gap-3">
                            <button type="submit" class="sa-form-submit">
                                <i class="bi bi-check-lg me-1"></i> Perbarui
                            </button>
                            <a href="{{ route('admin.ponds.index') }}" class="sa-form-cancel">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
