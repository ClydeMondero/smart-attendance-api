<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        return User::where('role', '!=', 'admin')->get();
    }

    public function show(User $user)
    {
        return $user;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,teacher,operator',
            'status'   => 'required|in:active,disabled',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => $request->status,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|string|email|unique:users,email,' . $user->id,
            'role'   => 'required|in:admin,teacher,operator',
            'status' => 'required|in:active,disabled',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
            'status' => $request->status,
        ]);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    /**
     * Send password reset link to user email
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::createToken(User::where('email', $request->email)->first());

        $user = User::where('email', $request->email)->first();
        $user->notify(new ResetPasswordNotification($status));

        return response()->json(['message' => 'Reset link sent to your email.']);
    }

    public function toggleStatus(Request $request, User $user)
    {
        $request->validate([
            'status' => 'required|in:active,disabled',
        ]);

        $user->update([
            'status' => $request->status,
        ]);

        return response()->json($user);
    }

    public function handleReset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 400);
    }
}
