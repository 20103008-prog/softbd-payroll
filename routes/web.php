<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AttendanceRecordController;
use App\Http\Controllers\Web\AttendanceReportController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CompanySettingController;
use App\Http\Controllers\Web\DepartmentController;
use App\Http\Controllers\Web\DesignationController;
use App\Http\Controllers\Web\DeviceManagementController;
use App\Http\Controllers\Web\EmployeeManagementController;
use App\Http\Controllers\Web\HolidayController;
use App\Http\Controllers\Web\LatePolicyController;
use App\Http\Controllers\Web\LeaveBalanceController;
use App\Http\Controllers\Web\LeaveRequestController;
use App\Http\Controllers\Web\LeaveTypeController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\PayrollController;
use App\Http\Controllers\Web\RosterController;
use App\Http\Controllers\Web\SalaryComponentController;
use App\Http\Controllers\Web\SalaryController;
use App\Http\Controllers\Web\SelfServiceController;
use App\Http\Controllers\Web\SummaryReportController;
use App\Http\Controllers\Web\UserManagementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {

Route::get('/', [\App\Http\Controllers\Web\DashboardController::class, 'index'])->name('home');

Route::prefix('me')->name('me.')->group(function () {
    Route::get('/attendance', [SelfServiceController::class, 'attendance'])->name('attendance');
    Route::post('/attendance/punch', [SelfServiceController::class, 'punchNow'])->name('attendance.punch');
    Route::get('/leave', [SelfServiceController::class, 'leaveIndex'])->name('leave');
    Route::get('/leave/create', [SelfServiceController::class, 'leaveCreate'])->name('leave.create');
    Route::post('/leave', [SelfServiceController::class, 'leaveStore'])->name('leave.store');
    Route::get('/payslips', [SelfServiceController::class, 'payslips'])->name('payslips');
    Route::get('/payslips/{payslip}', [SelfServiceController::class, 'payslip'])->name('payslip');
    Route::get('/payslips/{payslip}/pdf', [SelfServiceController::class, 'payslipPdf'])->name('payslip.pdf');
});

if (app()->environment('local')) {
    Route::get('/_debug/php', function () {
        return response()->json([
            'extension_sockets_loaded' => extension_loaded('sockets'),
            'php_version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'php_ini_loaded_file' => php_ini_loaded_file(),
            'php_binary' => defined('PHP_BINARY') ? PHP_BINARY : null,
            'loaded_extensions' => array_values(array_filter(get_loaded_extensions(), fn ($e) => str_contains($e, 'socket'))),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    });
}

Route::middleware('can:manage-employees')->group(function () {
    Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeManagementController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeManagementController::class, 'store'])->name('employees.store');
    Route::post('/employees/import-missing', [EmployeeManagementController::class, 'importMissing'])->name('employees.import-missing');
    Route::get('/employees/{employee}', [EmployeeManagementController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeManagementController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeManagementController::class, 'destroy'])->name('employees.destroy');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('organization.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::post('/designations', [DesignationController::class, 'store'])->name('designations.store');
    Route::put('/designations/{designation}', [DesignationController::class, 'update'])->name('designations.update');
    Route::delete('/designations/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');

    Route::get('/roster', [RosterController::class, 'index'])->name('roster.index');
    Route::post('/roster/shifts', [RosterController::class, 'storeShift'])->name('roster.shifts.store');
    Route::put('/roster/shifts/{shift}', [RosterController::class, 'updateShift'])->name('roster.shifts.update');
    Route::delete('/roster/shifts/{shift}', [RosterController::class, 'destroyShift'])->name('roster.shifts.destroy');
    Route::post('/roster/assignments', [RosterController::class, 'storeAssignment'])->name('roster.assignments.store');
    Route::delete('/roster/assignments/{assignment}', [RosterController::class, 'destroyAssignment'])->name('roster.assignments.destroy');
});

Route::middleware('can:view-reports')->group(function () {
    Route::get('/reports/attendance', [AttendanceReportController::class, 'index'])->name('reports.attendance');
    Route::get('/reports/attendance/export', [AttendanceReportController::class, 'exportCsv'])->name('reports.attendance.export');
    Route::get('/reports/summary', [SummaryReportController::class, 'index'])->name('reports.summary');
    Route::get('/reports/summary/data', [SummaryReportController::class, 'data'])->name('reports.summary.data');
});

Route::middleware('can:view-audit')->group(function () {
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
});

Route::middleware('can:manage-attendance')->group(function () {
    Route::get('/reports/day/{code}/{date}', [AttendanceRecordController::class, 'editDay'])
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->name('reports.day');
    Route::post('/reports/punches', [AttendanceRecordController::class, 'storePunch'])->name('reports.punches.store');
    Route::put('/reports/punches/{attendanceLog}', [AttendanceRecordController::class, 'updatePunch'])->name('reports.punches.update');
    Route::delete('/reports/punches/{attendanceLog}', [AttendanceRecordController::class, 'destroyPunch'])->name('reports.punches.destroy');
    Route::post('/employees/{employee}/punch', [EmployeeManagementController::class, 'punchNow'])->name('employees.punch');
});

Route::middleware('can:manage-devices')->group(function () {
    Route::get('/devices', [DeviceManagementController::class, 'index'])->name('devices.index');
    Route::get('/devices/create', [DeviceManagementController::class, 'create'])->name('devices.create');
    Route::post('/devices', [DeviceManagementController::class, 'store'])->name('devices.store');
    Route::get('/devices/{device}/edit', [DeviceManagementController::class, 'edit'])->name('devices.edit');
    Route::put('/devices/{device}', [DeviceManagementController::class, 'update'])->name('devices.update');
    Route::delete('/devices/{device}', [DeviceManagementController::class, 'destroy'])->name('devices.destroy');
});

Route::middleware('can:manage-settings')->group(function () {
    Route::get('/settings/late-policy', [LatePolicyController::class, 'edit'])->name('settings.late-policy');
    Route::put('/settings/late-policy', [LatePolicyController::class, 'update'])->name('settings.late-policy.update');
    Route::get('/settings/company', [CompanySettingController::class, 'edit'])->name('settings.company');
    Route::put('/settings/company', [CompanySettingController::class, 'update'])->name('settings.company.update');
    Route::delete('/settings/company/logo', [CompanySettingController::class, 'removeLogo'])->name('settings.company.logo.destroy');
});

Route::middleware('can:manage-leave')->group(function () {
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::get('/leave/balances', [LeaveBalanceController::class, 'index'])->name('leave.balances');
    Route::get('/leave/create', [LeaveRequestController::class, 'create'])->name('leave.create');
    Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::post('/leave/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leave.reject');
    Route::delete('/leave/{leave}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');

    Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
    Route::put('/leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
    Route::delete('/leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
});

Route::middleware('can:manage-payroll')->group(function () {
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/salaries', [SalaryController::class, 'index'])->name('payroll.salaries');
    Route::get('/payroll/salaries/{employee}/components', [SalaryComponentController::class, 'index'])->name('payroll.components');
    Route::post('/payroll/salaries/{employee}/components', [SalaryComponentController::class, 'store'])->name('payroll.components.store');
    Route::post('/payroll/salaries/{employee}', [SalaryController::class, 'update'])->name('payroll.salaries.update');
    Route::put('/payroll/components/{component}', [SalaryComponentController::class, 'update'])->name('payroll.components.update');
    Route::delete('/payroll/components/{component}', [SalaryComponentController::class, 'destroy'])->name('payroll.components.destroy');
    Route::get('/payroll/{run}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('/payroll/{run}/lock', [PayrollController::class, 'lock'])->name('payroll.lock');
    Route::post('/payroll/{run}/unlock', [PayrollController::class, 'unlock'])->name('payroll.unlock');
    Route::get('/payroll/{run}/export', [PayrollController::class, 'exportCsv'])->name('payroll.export');
    Route::get('/payroll/{run}/payslip/{payslip}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::get('/payroll/{run}/payslip/{payslip}/pdf', [PayrollController::class, 'payslipPdf'])->name('payroll.payslip.pdf');
    Route::delete('/payroll/{run}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
});

Route::middleware('can:manage-users')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

});
