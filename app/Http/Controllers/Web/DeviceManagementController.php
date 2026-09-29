<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DeviceManagementController
 * 
 * Manages registered web attendance devices / terminals in the system.
 */
class DeviceManagementController extends Controller
{
    public function index(): View
    {
        $devices = Device::query()->orderBy('name')->paginate(20);

        return view('devices.index', compact('devices'));
    }

    public function create(): View
    {
        return view('devices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:64', 'unique:devices,serial_number'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Device::query()->create([
            ...$validated,
            'serial_number' => isset($validated['serial_number']) && $validated['serial_number'] !== ''
                ? strtoupper(trim($validated['serial_number']))
                : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('devices.index')->with('success', 'Device created successfully.');
    }

    public function edit(Device $device): View
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:64', 'unique:devices,serial_number,'.$device->id],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $device->update([
            ...$validated,
            'serial_number' => isset($validated['serial_number']) && $validated['serial_number'] !== ''
                ? strtoupper(trim($validated['serial_number']))
                : null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted successfully.');
    }
}

