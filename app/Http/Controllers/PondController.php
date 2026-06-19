<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Pond;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PondController extends Controller
{
    /**
     * Display a listing of all ponds.
     */
    public function index(): View
    {
        $ponds = Pond::with('user')->orderBy('name')->get();

        return view('admin.ponds.index', compact('ponds'));
    }

    /**
     * Show the form for creating a new pond.
     */
    public function create(): View
    {
        $users = User::orderBy('name')->get();

        return view('admin.ponds.create', compact('users'));
    }

    /**
     * Store a newly created pond.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:ponds,code'],
            'user_id' => ['nullable', 'exists:users,id'],
            'min_water_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_water_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'max_ph' => ['required', 'numeric', 'min:0', 'max:14'],
        ]);

        Pond::create($validated);

        return redirect()
            ->route('admin.ponds.index')
            ->with('success', 'Kolam berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified pond.
     */
    public function edit(Pond $pond): View
    {
        $users = User::orderBy('name')->get();

        return view('admin.ponds.edit', compact('pond', 'users'));
    }

    /**
     * Update the specified pond.
     */
    public function update(Request $request, Pond $pond): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:ponds,code,' . $pond->id],
            'user_id' => ['nullable', 'exists:users,id'],
            'min_water_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_water_level' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'max_ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $pond->update($validated);

        return redirect()
            ->route('admin.ponds.index')
            ->with('success', 'Kolam berhasil diperbarui.');
    }

    /**
     * Remove the specified pond.
     */
    public function destroy(Pond $pond): RedirectResponse
    {
        $pond->delete();

        return redirect()
            ->route('admin.ponds.index')
            ->with('success', 'Kolam berhasil dihapus.');
    }
}
