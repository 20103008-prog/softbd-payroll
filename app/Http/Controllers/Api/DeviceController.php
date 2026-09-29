<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDeviceRequest;
use App\Http\Requests\Api\UpdateDeviceRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DeviceController
 * 
 * Manages biometric device registration, updating, and viewing via API endpoints.
 */
class DeviceController extends Controller
{
    /**
     * Display a paginated listing of biometric devices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Query devices ordered by device name
        $query = Device::query()->orderBy('name');

        $perPage = min((int) $request->query('per_page', 15), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Register a new biometric device in the database.
     *
     * @param StoreDeviceRequest $request
     * @return JsonResponse
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        // Create new device record with validated data
        $device = Device::create($request->validated());

        return response()->json($device, 201);
    }

    /**
     * Display details of a specific biometric device.
     *
     * @param Device $device
     * @return JsonResponse
     */
    public function show(Device $device): JsonResponse
    {
        // Load count of attendance logs associated with this device
        return response()->json($device->loadCount('attendanceLogs'));
    }

    /**
     * Update an existing biometric device record.
     *
     * @param UpdateDeviceRequest $request
     * @param Device $device
     * @return JsonResponse
     */
    public function update(UpdateDeviceRequest $request, Device $device): JsonResponse
    {
        // Update device parameters (name, ip, port, active status)
        $device->update($request->validated());

        return response()->json($device->fresh());
    }

    /**
     * Remove a biometric device from the system.
     *
     * @param Device $device
     * @return JsonResponse
     */
    public function destroy(Device $device): JsonResponse
    {
        // Delete device record
        $device->delete();

        return response()->json(null, 204);
    }
}
