<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display a listing of system users with roles and permissions.
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $users = User::query()
            ->with('employee:id,name,employee_code')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'roles' => User::roles(),
            'roleDescriptions' => User::roleDescriptions(),
            'permissions' => User::permissions(),
            'rolePermissionMap' => User::rolePermissionMap(),
        ]);
    }

    /**
     * Show the form for creating a new system user.
     */
    public function create(): View
    {
             return view('users.create', [
            'roles' => User::roles(),
            'roleDescriptions' => User::roleDescriptions(),
            'rolePermissions' => User::rolePermissions(),
            'employees' => $this->linkableEmployees(),
        ]);
    }

    /**
     * Store a newly created system user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'employee_id' => ['nullable', 'exists:employees,id', 'unique:users,employee_id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (empty($validated['employee_id'])) {
            $validated['employee_id'] = null;
        }

        $user = User::query()->create($validated);

        app(AuditLogger::class)->log('user.created', "Created user {$user->email} ({$user->role})", $user);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified system user.
     */
    public function edit(User $user): View
    {
        $user->load('employee:id,name,employee_code');

        return view('users.edit', [
            'user' => $user,
            'roles' => User::roles(),
            'roleDescriptions' => User::roleDescriptions(),
            'rolePermissions' => User::rolePermissions(),
            'employees' => $this->linkableEmployees($user->id),
        ]);
    }

    /**
     * Update the specified system user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'employee_id' => ['nullable', 'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Prevent demoting the last super admin
        if ($user->isSuperAdmin()
            && $validated['role'] !== User::ROLE_SUPER_ADMIN
            && $this->superAdminCount() <= 1) {
            return back()->withInput()->with('warning', 'You cannot demote the last super admin.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->employee_id = ! empty($validated['employee_id']) ? $validated['employee_id'] : null;
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->save();

        app(AuditLogger::class)->log('user.updated', "Updated user {$user->email} ({$user->role})", $user);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified system user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('warning', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->with('warning', 'You cannot delete the last super admin.');
        }

        $summary = "Deleted user {$user->email}";
        $user->delete();

        app(AuditLogger::class)->log('user.deleted', $summary);

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function superAdminCount(): int
    {
        return User::query()->where('role', User::ROLE_SUPER_ADMIN)->count();
    }

    /**
     * Employees that are free to link, plus the current user's link when editing.
     */
    private function linkableEmployees(?int $exceptUserId = null)
    {
        $taken = User::query()
            ->whereNotNull('employee_id')
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->pluck('employee_id');

        return Employee::query()
            ->whereNotIn('id', $taken)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);
    }
}
