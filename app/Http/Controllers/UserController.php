<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Eager load roles to prevent N+1 query problems
        $users = User::with('role')->orderBy('name')->paginate(15);
        return view('users.index', compact('users'));
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

        // Only hash and update the password if a new one was provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Staff account updated successfully.');
    }
}