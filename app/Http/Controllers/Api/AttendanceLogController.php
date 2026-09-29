<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AttendanceLogController
 * 
 * Handles API endpoints for querying and retrieving attendance punch logs recorded by biometric devices.
 */
class AttendanceLogController extends Controller
{
    /**
     * Display a paginated listing of attendance logs with optional filtering.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Build query loading device details and employee details
        $query = AttendanceLog::query()
            ->with(['device:id,name,ip_address,port', 'employee:employee_code,name,department'])
            ->orderByDesc('punched_at');

        // Filter by specific device ID if provided
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->integer('device_id'));
        }

        // Filter by employee code if provided
        if ($request->filled('employee_code')) {
            $query->where('employee_code', $request->query('employee_code'));
        }

        // Filter by status (Check-In, Check-Out, etc.)
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter by date range start (from)
        if ($request->filled('from')) {
            $query->where('punched_at', '>=', $request->query('from'));
        }

        // Filter by date range end (to)
        if ($request->filled('to')) {
            $query->where('punched_at', '<=', $request->query('to'));
        }

        // Paginate results with max 100 items per page
        $perPage = min((int) $request->query('per_page', 20), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Display the specified attendance log details.
     *
     * @param AttendanceLog $attendanceLog
     * @return JsonResponse
     */
    public function show(AttendanceLog $attendanceLog): JsonResponse
    {
        // Return single log record with loaded device and employee relationships
        return response()->json($attendanceLog->load(['device', 'employee']));
    }
}
