<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
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
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('first_name', $like, "%{$search}%")
                  ->orWhere('last_name', $like, "%{$search}%")
                  ->orWhere('email', $like, "%{$search}%");
            });
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

        User::create($validated);

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

        // Only hash and update the password if a new one was provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

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

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Staff account deleted successfully.');
    }
}