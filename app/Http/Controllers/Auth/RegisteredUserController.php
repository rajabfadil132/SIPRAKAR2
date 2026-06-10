<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'phone' => $request->phone,
            'cabang_id' => $request->cabang_id,
            'role_id' => 3, // Staff Teknis — registration gets staff role by default
            'status' => 'active',
            'created_by' => null,
        ]);

        event(new Registered($user));

        // Auto-login after registration
        Auth::login($user);

        return redirect()->route($user->accessibleRouteName())
            ->with('success', 'Registrasi berhasil. Selamat datang, ' . $user->name . '!');
    }
}