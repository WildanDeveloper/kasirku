<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('sales')
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->orderBy('is_admin')->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        ActivityLogger::record('user.created', 'Membuat user '.$user->name.' ('.$user->email.')', $user);

        return back()->with('success', 'User dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($data['is_active'] === false && $user->is($request->user())) {
            return back()->with('warning', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        if (! $data['is_admin'] && $user->isAdmin() && User::where('is_admin', true)->count() <= 1) {
            return back()->with('warning', 'Minimal harus ada satu admin.');
        }

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        ActivityLogger::record('user.updated', 'Memperbarui user '.$user->name, $user);

        return back()->with('success', 'User diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'Tidak bisa menghapus akun sendiri.');
        abort_if($user->isAdmin() && User::where('is_admin', true)->count() <= 1, 422, 'Minimal harus ada satu admin.');

        ActivityLogger::record('user.deleted', 'Menghapus user '.$user->name, $user);

        $user->delete();

        return back()->with('success', 'User dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'.($user ? ','.$user->id : '')],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(6)],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'] ?? null,
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}