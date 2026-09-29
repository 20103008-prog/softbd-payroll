<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 * 
 * =========================================================================
 * [EXAM SUMMARY & NOTES]:
 * This model represents the system user accounts, role definitions,
 * authorization gates, and role hierarchy security rules.
 * =========================================================================
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /* =========================================================================
     * 1. ROLE CONSTANTS
     * ========================================================================= */
    public const ROLE_SUPER_ADMIN = 'super_admin'; // Super Admin: Full system control
    public const ROLE_ADMIN = 'admin';             // Admin: Operational control except user management
    public const ROLE_HR = 'hr_manager';           // HR Manager: People operations (Employees, Leave, Payroll)
    public const ROLE_MANAGER = 'manager';         // Manager: Scoped to their assigned department only
    public const ROLE_USER = 'user';               // Ordinary User: Employee self-service portal

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id', // ID of linked Employee record (if any)
    ];

    /* =========================================================================
     * 2. RELATIONSHIPS & PORTAL CHECKERS
     * ========================================================================= */

    /**
     * Relationship: BelongsTo linked Employee model.
     */
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Checks if this user account is linked to an Employee record.
     */
    public function hasEmployeeProfile(): bool
    {
        return $this->employee_id !== null;
    }

    /**
     * Checks if this account is a portal-only user (no admin navigation access).
     */
    public function isPortalOnly(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Determines the landing route after login based on account role.
     */
    public function homeRouteName(): string
    {
        if ($this->isPortalOnly() && $this->hasEmployeeProfile()) {
            return 'me.attendance'; // Send ordinary employees to their self-service attendance portal
        }

        return 'home'; // Send management roles to main dashboard
    }

    /* =========================================================================
     * 3. ROLE CHECKER HELPER METHODS
     * ========================================================================= */

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isHr(): bool
    {
        return $this->role === self::ROLE_HR;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /**
     * Managers are scoped to their assigned department only.
     */
    public function isTeamScoped(): bool
    {
        return $this->isManager();
    }

    /**
     * Retrieves the department ID managed by this manager.
     */
    public function managedDepartmentId(): ?int
    {
        if (! $this->isTeamScoped()) {
            return null;
        }

        $this->loadMissing('employee');

        return $this->employee?->department_id;
    }

    /**
     * Checks if the user is authorized to view/access the given employee.
     */
    public function canAccessEmployee(?Employee $employee): bool
    {
        if (! $employee) {
            return false;
        }

        // Non-manager roles have global employee access
        if (! $this->isTeamScoped()) {
            return true;
        }

        // Managers are restricted to employees in their own department
        $departmentId = $this->managedDepartmentId();

        return $departmentId !== null
            && (int) $employee->department_id === (int) $departmentId;
    }

    /**
     * Role rank score for privilege comparisons:
     * Super Admin (5) > Admin (4) > HR Manager (3) > Manager (2) > User (1)
     */
    public function roleRank(): int
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 5,
            self::ROLE_ADMIN => 4,
            self::ROLE_HR => 3,
            self::ROLE_MANAGER => 2,
            self::ROLE_USER => 1,
            default => 0,
        };
    }

    /* =========================================================================
     * 4. ROLE HIERARCHY & PROFILE MANAGEMENT SECURITY
     * ========================================================================= */

    /**
     * Determines if this user can manage (view, edit, delete, or add punches for) an employee profile.
     *
     * Hierarchy & Privilege Security Rules:
     * 1. Super Admin: Can manage EVERYONE.
     * 2. Self Restriction: No user can manage their own profile or attendance.
     * 3. Admin: Can manage everyone EXCEPT Super Admin profiles and self.
     * 4. HR Manager: Can manage everyone EXCEPT Super Admin, Admin profiles, and self.
     * 5. Manager: Can manage ordinary employees in their department (NOT Super Admin, Admin, HR, or other Managers).
     * 6. Ordinary User: Cannot manage anyone.
     */
    public function canManageEmployeeProfile(?Employee $employee): bool
    {
        if (! $employee) {
            return false;
        }

        // 1. Super Admin has unrestricted access to all employees
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. Self restriction: Cannot manage own employee profile or attendance records
        if ($this->employee_id && (int) $this->employee_id === (int) $employee->id) {
            return false;
        }
        if ($this->employee?->employee_code && $this->employee->employee_code === $employee->employee_code) {
            return false;
        }
        if (! empty($employee->email) && ! empty($this->email) && strtolower($employee->email) === strtolower($this->email)) {
            return false;
        }

        // 3. Department scope restriction for managers
        if (! $this->canAccessEmployee($employee)) {
            return false;
        }

        // Determine target employee's linked User role (if any)
        $targetRole = null;
        try {
            $targetUser = User::query()->where('employee_id', $employee->id)->orWhere(function ($q) use ($employee) {
                if (! empty($employee->email)) {
                    $q->where('email', $employee->email);
                }
            })->first();

            if ($targetUser) {
                if ((int) $targetUser->id === (int) $this->id) {
                    return false; // Self restriction check by User ID
                }
                $targetRole = $targetUser->role;
            }
        } catch (\Throwable $e) {
            // Fail-safe if query fails
        }

        // 4. Admin restrictions: Cannot manage Super Admin
        if ($this->isAdmin()) {
            if ($targetRole === self::ROLE_SUPER_ADMIN) {
                return false;
            }
            return true;
        }

        // 5. HR Manager restrictions: Cannot manage Super Admin or Admin
        if ($this->isHr()) {
            if (in_array($targetRole, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true)) {
                return false;
            }
            return true;
        }

        // 6. Manager restrictions: Cannot manage Super Admin, Admin, HR Manager, or other Managers
        if ($this->isManager()) {
            if (in_array($targetRole, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_HR, self::ROLE_MANAGER], true)) {
                return false;
            }
            return true;
        }

        // 7. Ordinary User cannot manage anyone
        return false;
    }

    /* =========================================================================
     * 5. LEAVE REQUEST APPROVAL SECURITY
     * ========================================================================= */

    /**
     * Determines if this user can approve or reject a leave request based on role hierarchy.
     *
     * Hierarchy & Approval Rules:
     * 1. Super Admin: Can approve/reject ALL leave requests.
     * 2. Self Restriction: No user can approve/reject their own leave request.
     * 3. Admin: Can approve HR Manager, Manager, and Employee requests.
     * 4. HR Manager: Can approve Manager and Employee requests.
     * 5. Manager: Can approve Employee requests in their department only.
     */
    public function canApproveLeaveRequest(LeaveRequest $leave): bool
    {
        // 1. Super Admin can approve/reject anyone's request
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. Non-Super Admins cannot approve their own leave request
        if ($leave->isOwnedBy($this)) {
            return false;
        }

        $leave->loadMissing('employee');
        $employee = $leave->employee;

        if (! $employee) {
            return false;
        }

        // Determine target employee's linked User role (defaults to ROLE_USER if none)
        $targetRole = self::ROLE_USER;
        if (! empty($employee->user_id)) {
            $targetUser = User::find($employee->user_id);
            if ($targetUser) {
                $targetRole = $targetUser->role;
            }
        } else {
            $targetUser = User::query()
                ->where('employee_id', $employee->id)
                ->orWhere(function ($q) use ($employee) {
                    if (! empty($employee->email)) {
                        $q->where('email', $employee->email);
                    }
                })
                ->first();

            if ($targetUser) {
                $targetRole = $targetUser->role;
            }
        }

        // 3. Admin: Can approve HR Manager, Manager, and Employee requests
        if ($this->isAdmin()) {
            return in_array($targetRole, [self::ROLE_HR, self::ROLE_MANAGER, self::ROLE_USER], true);
        }

        // 4. HR Manager: Can approve Manager and Employee requests
        if ($this->isHr()) {
            return in_array($targetRole, [self::ROLE_MANAGER, self::ROLE_USER], true);
        }

        // 5. Manager: Can approve Employee requests in their department only
        if ($this->isManager()) {
            if (! $this->canAccessEmployee($employee)) {
                return false;
            }

            return $targetRole === self::ROLE_USER;
        }

        return false;
    }

    /**
     * Permission to delete leave requests follows the same hierarchy rules as leave approval.
     */
    public function canDeleteLeaveRequest(LeaveRequest $leave): bool
    {
        return $this->canApproveLeaveRequest($leave);
    }

    /* =========================================================================
     * 6. QUERY SCOPES & PERMISSION MAP
     * ========================================================================= */

    /**
     * Scopes an Employee query builder to the manager's assigned department.
     */
    public function scopeManagedEmployees($query)
    {
        if (! $this->isTeamScoped()) {
            return $query;
        }

        $departmentId = $this->managedDepartmentId();

        if ($departmentId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('department_id', $departmentId);
    }

    /**
     * Human-readable role names dictionary.
     */
    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_HR => 'HR Manager',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_USER => 'User',
        ];
    }

    /**
     * Human-readable descriptions for each role.
     */
    public static function roleDescriptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Full access, including users & roles and all settings.',
            self::ROLE_ADMIN => 'Runs day-to-day operations; everything except managing users.',
            self::ROLE_HR => 'People operations: employees, leave and payroll.',
            self::ROLE_MANAGER => 'Oversees their department only: reports, attendance edits and leave approvals (link to an employee in that department).',
            self::ROLE_USER => 'Employee portal: own attendance, leave requests and payslips (when linked).',
        ];
    }

    /**
     * Master catalog of system permissions (Ability key => Human label).
     */
    public static function permissions(): array
    {
        return [
            'view-reports' => 'View attendance reports & export CSV',
            'manage-attendance' => 'Add, edit & delete attendance records',
            'manage-employees' => 'Manage employees & organization',
            'manage-devices' => 'Manage devices & push setup',
            'manage-leave' => 'Manage & approve leave',
            'manage-payroll' => 'Manage payroll & salaries',
            'manage-settings' => 'Manage company & policy settings',
            'manage-users' => 'Manage users & roles',
            'view-audit' => 'View audit log of sensitive changes',
        ];
    }

    /**
     * Role to Permission mapping table (defines which role receives which permissions).
     */
    public static function rolePermissionMap(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => array_keys(self::permissions()),
            self::ROLE_ADMIN => [
                'view-reports', 'manage-attendance', 'manage-employees',
                'manage-devices', 'manage-leave', 'manage-payroll', 'manage-settings',
                'view-audit',
            ],
            self::ROLE_HR => [
                'view-reports', 'manage-attendance', 'manage-employees',
                'manage-leave', 'manage-payroll',
            ],
            self::ROLE_MANAGER => [
                'view-reports', 'manage-attendance', 'manage-leave',
            ],
            self::ROLE_USER => [
                // Portal access is granted via linked employee_id, not a Gate permission.
            ],
        ];
    }

    /**
     * Checks if this user's role grants a specific permission.
     */
    public function hasPermission(string $ability): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($ability, self::rolePermissionMap()[$this->role] ?? [], true);
    }

    public function permissionKeys(): array
    {
        return self::rolePermissionMap()[$this->role] ?? [];
    }

    public static function rolePermissions(): array
    {
        $labels = self::permissions();

        $out = [];
        foreach (self::rolePermissionMap() as $role => $abilities) {
            $out[$role] = array_map(fn ($a) => $labels[$a] ?? $a, $abilities);
        }

        return $out;
    }

    public function roleLabel(): string
    {
        return self::roles()[$this->role] ?? ucfirst((string) $this->role);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
