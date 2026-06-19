<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\Pond;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Display a listing of all device tokens.
     */
    public function index(): View
    {
        $devices = DeviceToken::with('pond')->latest()->get();
        $ponds = Pond::orderBy('name')->get();

        return view('admin.devices.index', compact('devices', 'ponds'));
    }

    /**
     * Generate a new device token for a pond (from the form).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pond_id' => 'required|exists:ponds,id',
            'name' => 'required|string|max:255',
        ]);

        $token = DeviceToken::generateToken();

        DeviceToken::create([
            'pond_id' => $request->pond_id,
            'token' => $token,
            'device_name' => $request->name,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.devices.index')
            ->with('success', 'Token perangkat berhasil dibuat.')
            ->with('token', $token);
    }

    /**
     * Regenerate token for an existing device.
     */
    public function regenerate(DeviceToken $device): RedirectResponse
    {
        $newToken = DeviceToken::generateToken();

        $device->update([
            'token' => $newToken,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.devices.index')
            ->with('success', "Token untuk {$device->device_name} berhasil di-regenerate.")
            ->with('token', $newToken);
    }

    /**
     * Revoke (deactivate) the specified device token.
     */
    public function revoke(DeviceToken $device): RedirectResponse
    {
        $device->update(['is_active' => false]);

        return redirect()
            ->route('admin.devices.index')
            ->with('success', "Token {$device->device_name} berhasil dicabut.");
    }
}
