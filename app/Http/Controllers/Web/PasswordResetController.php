<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /* ========================================================================= */
    /* 1. SHOW FORGOT PASSWORD FORM METHOD */
    /* ========================================================================= */
    public function showForgotForm(): View
    {
        // Render auth/forgot-password.blade.php view page
        return view('auth.forgot-password');
    }

    /* ========================================================================= */
    /* 2. SEND RESET LINK METHOD: GENERATE & EMAIL / LOG PASSWORD RESET LINK */
    /* ========================================================================= */
    public function sendResetLink(Request $request): RedirectResponse
    {
        // Validate submitted email address
        $request->validate(['email' => ['required', 'email']]);

        // Generate reset token and trigger password reset notification
        $status = Password::sendResetLink($request->only('email'));

        // Write a huge, unmissable comment banner inside storage/logs/laravel.log
        if ($status === Password::RESET_LINK_SENT) {
            $email = $request->input('email');
            Log::info("\n".
                str_repeat('*', 90)."\n".
                str_repeat('*', 90)."\n".
                "🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑\n".
                "  >>> PASSWORD RESET LINK WAS GENERATED FOR: {$email} <<<\n".
                "  >>> PLEASE LOOK RIGHT BELOW THIS BANNER TO SEE THE FULL RESET URL LINK <<<\n".
                "🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑🔑\n".
                str_repeat('*', 90)."\n".
                str_repeat('*', 90)."\n"
            );
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    /* ========================================================================= */
    /* 3. SHOW RESET FORM METHOD: DISPLAY NEW PASSWORD INPUT FORM */
    /* ========================================================================= */
    public function showResetForm(Request $request, string $token): View
    {
        // Render auth/reset-password.blade.php with reset token and email address
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /* ========================================================================= */
    /* 4. RESET PASSWORD METHOD: UPDATE USER PASSWORD IN DATABASE */
    /* ========================================================================= */
    public function reset(Request $request): RedirectResponse
    {
        // Validate password reset inputs (token, email, password & confirmation)
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Reset user password and save to database
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
