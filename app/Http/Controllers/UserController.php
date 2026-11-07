<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = User::with('role')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($search) {
            $query->search($search);
        }

        $users = $query->paginate(15)->withQueryString();
        return view('users.index', compact('users', 'search'));
    }

    public function create()
    {
        $roles = Role::orderBy('role_name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        
        // Hash the password securely before saving
        $validated['password'] = Hash::make($validated['password']);

        $newUser = User::create($validated);
        $newUser->load('role');

        AuditTrailService::record([
            'module'                => 'users',
            'action'                => 'user.created',
            'entity_type'           => 'User',
            'entity_id'             => $newUser->id,
            'transaction_reference' => 'USR-' . str_pad((string) $newUser->id, 4, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'after_values'          => [
                'name'      => $newUser->name,
                'email'     => $newUser->email,
                'role_id'   => (int) $newUser->role_id,
                'role_name' => $newUser->role?->role_name,
            ],
            'reason'                => 'New staff user account created',
        ]);

        return redirect()->route('users.index')->with('success', 'Staff account created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('role_name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        // Prevent Owner lockout: cannot change or downgrade own role
        if ($user->id === auth()->id() && isset($validated['role_id']) && (int)$validated['role_id'] !== (int)$user->role_id) {
            return back()->withErrors('You cannot change or downgrade your own role.');
        }

        // Prevent leaving zero owners in the system
        if ($user->isOwner() && isset($validated['role_id']) && (int)$validated['role_id'] !== 3) {
            if (User::where('role_id', 3)->count() <= 1) {
                return back()->withErrors('Cannot downgrade the sole remaining Owner account in the system.');
            }
        }

        $user->load('role');
        $beforeValues = [
            'name'      => $user->name,
            'email'     => $user->email,
            'role_id'   => (int) $user->role_id,
            'role_name' => $user->role?->role_name,
        ];

        $roleChanged = isset($validated['role_id']) && (int) $validated['role_id'] !== (int) $user->role_id;
        $passwordChanged = !empty($validated['password']);

        // Only hash and update the password if a new one was provided
        if ($passwordChanged) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->refresh();
        $user->load('role');

        AuditTrailService::record([
            'module'                => 'users',
            'action'                => $roleChanged ? 'user.role_changed' : 'user.updated',
            'entity_type'           => 'User',
            'entity_id'             => $user->id,
            'transaction_reference' => 'USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'before_values'         => $beforeValues,
            'after_values'          => [
                'name'             => $user->name,
                'email'            => $user->email,
                'role_id'          => (int) $user->role_id,
                'role_name'        => $user->role?->role_name,
                'password_changed' => $passwordChanged,
            ],
            'reason'                => $roleChanged ? 'Staff user role modified' : 'Staff user profile updated',
        ]);

        return redirect()->route('users.index')->with('success', 'Staff account updated successfully.');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->isOwner()) {
            abort(403, 'Unauthorized action.');
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors('You cannot delete your own account.');
        }

        // Prevent deleting the sole remaining Owner
        if ($user->isOwner() && User::where('role_id', 3)->count() <= 1) {
            return back()->withErrors('Cannot delete the sole remaining Owner account in the system.');
        }

        $user->load('role');
        $beforeSnapshot = [
            'name'      => $user->name,
            'email'     => $user->email,
            'role_id'   => (int) $user->role_id,
            'role_name' => $user->role?->role_name,
            'status'    => 'active',
        ];

        $user->delete();

        AuditTrailService::record([
            'module'                => 'users',
            'action'                => 'user.archived',
            'entity_type'           => 'User',
            'entity_id'             => $user->id,
            'transaction_reference' => 'USR-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'before_values'         => $beforeSnapshot,
            'after_values'          => array_merge($beforeSnapshot, ['status' => 'archived']),
            'reason'                => 'Staff user account archived (soft-deleted)',
        ]);

        return redirect()->route('users.index')->with('success', 'Staff account deleted successfully.');
    }
}