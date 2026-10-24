<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users with role metrics.
     */
    public function index(Request $request): View
    {
        $query = User::with(['role', 'reseller']);

        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('reseller', function ($rq) use ($search) {
                        $rq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('reseller_code', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->orderBy('id', 'asc')->paginate(15);
        $roles = Role::all();

        // Metrics for quick summary
        $metrics = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'super_admins' => User::whereHas('role', fn ($q) => $q->where('name', Role::SUPER_ADMIN))->count(),
            'resellers' => User::whereHas('role', fn ($q) => $q->where('name', Role::RESELLER))->count(),
            'engineers' => User::whereHas('role', fn ($q) => $q->whereIn('name', [Role::TRANSMISSION, Role::NOC]))->count(),
        ];

        return view('users.index', compact('users', 'roles', 'metrics'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = Role::all();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'reseller_company_name' => ['nullable', 'string', 'max:200'],
            'reseller_code' => ['nullable', 'string', 'max:50', 'unique:resellers,reseller_code'],
            'reseller_address' => ['nullable', 'string'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // If reseller role, require company name
        if ($role->name === Role::RESELLER && empty($validated['reseller_company_name'])) {
            return back()->withErrors(['reseller_company_name' => 'Company Name is required for Reseller users.'])->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Create Reseller record if applicable
        if ($role->name === Role::RESELLER) {
            $code = ! empty($validated['reseller_code'])
                ? $validated['reseller_code']
                : 'RSL-'.strtoupper(Str::random(6));

            Reseller::create([
                'user_id' => $user->id,
                'reseller_code' => $code,
                'company_name' => $validated['reseller_company_name'],
                'address' => $validated['reseller_address'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        ActivityLog::record(
            'user',
            $user->id,
            'USER_CREATED',
            null,
            $role->name,
            "Super Admin created user {$user->name} ({$user->email}) with role {$role->name}."
        );

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' was successfully created with role '".strtoupper($role->name)."'.");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $user->load(['role', 'reseller']);
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
            'reseller_company_name' => ['nullable', 'string', 'max:200'],
            'reseller_code' => ['nullable', 'string', 'max:50'],
            'reseller_address' => ['nullable', 'string'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if ($role->name === Role::RESELLER && empty($validated['reseller_company_name'])) {
            return back()->withErrors(['reseller_company_name' => 'Company Name is required for Reseller users.'])->withInput();
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role_id = $role->id;
        $user->is_active = $request->boolean('is_active', true);

        // Update password if provided
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Handle Reseller profile
        if ($role->name === Role::RESELLER) {
            $reseller = $user->reseller;
            $code = ! empty($validated['reseller_code'])
                ? $validated['reseller_code']
                : ($reseller?->reseller_code ?? 'RSL-'.strtoupper(Str::random(6)));

            if ($reseller) {
                $reseller->update([
                    'company_name' => $validated['reseller_company_name'],
                    'reseller_code' => $code,
                    'address' => $validated['reseller_address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ]);
            } else {
                Reseller::create([
                    'user_id' => $user->id,
                    'reseller_code' => $code,
                    'company_name' => $validated['reseller_company_name'],
                    'address' => $validated['reseller_address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                ]);
            }
        }

        ActivityLog::record(
            'user',
            $user->id,
            'USER_UPDATED',
            null,
            $role->name,
            "Super Admin updated user {$user->name} ({$user->email}) with role {$role->name}."
        );

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' was successfully updated.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $userRole = $user->role->name;

        try {
            DB::transaction(function () use ($user, $userName, $userRole) {
                if ($user->reseller) {
                    $user->reseller->delete();
                }

                ActivityLog::record(
                    'user',
                    $user->id,
                    'USER_DELETED',
                    $userRole,
                    null,
                    "Super Admin deleted user {$userName}."
                );

                $user->delete();
            });

            return redirect()->route('users.index')
                ->with('success', "User '{$userName}' has been deleted.");
        } catch (\Throwable $e) {
            return back()->with('error', "Unable to delete user '{$userName}': ".$e->getMessage());
        }
    }

    /**
     * Impersonate any user from Super Admin account.
     */
    public function impersonate(User $user): RedirectResponse
    {
        $currentAuth = Auth::user();
        $isImpersonating = session()->has('impersonator_id');

        // Only genuine Super Admin or active impersonator can impersonate
        if (! $currentAuth || (! $currentAuth->isSuperAdmin() && ! $isImpersonating)) {
            abort(403, 'Unauthorized. Only Super Admin can impersonate other roles.');
        }

        // Store original admin ID if not already impersonating
        if (! $isImpersonating) {
            session(['impersonator_id' => $currentAuth->id]);
        }

        // If target user is super admin, end impersonation
        if ($user->isSuperAdmin()) {
            session()->forget('impersonator_id');
        }

        Auth::login($user);
        request()->session()->regenerate();

        ActivityLog::record(
            'user',
            $user->id,
            'SUPER_ADMIN_IMPERSONATE',
            null,
            $user->role->name,
            "Super Admin logged in as user {$user->name} ({$user->role->name})."
        );

        return redirect()->route('dashboard')
            ->with('success', "Switched to '{$user->name}' (".strtoupper($user->role->name).'). You now have full access of this role.');
    }

    /**
     * Stop impersonating and return to original Super Admin session.
     */
    public function stopImpersonate(): RedirectResponse
    {
        if (session()->has('impersonator_id')) {
            $adminId = session('impersonator_id');
            session()->forget('impersonator_id');

            $admin = User::find($adminId);

            if ($admin) {
                Auth::login($admin);
                request()->session()->regenerate();

                return redirect()->route('users.index')
                    ->with('success', 'Returned to Super Admin session.');
            }
        }

        return redirect()->route('dashboard');
    }
}
