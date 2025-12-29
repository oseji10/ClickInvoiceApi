<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    /**
     * Handle password reset request from the frontend.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token'     => 'required|string',
            'email'     => 'required|email',
            'password'  => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        // Laravel's PasswordBroker will validate the token and reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Optional: fire event (useful for logging, clearing sessions, etc.)
                event(new PasswordReset($user));
            }
        );

        // Password::PASSWORD_RESET = success
        // Other possible values: PASSWORD_RESET_INVALID_TOKEN, PASSWORD_RESET_INVALID_USER, etc.
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been successfully reset.',
            ], 200);
        }

        // Return a generic error message for security (don't reveal if token/email is invalid)
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}