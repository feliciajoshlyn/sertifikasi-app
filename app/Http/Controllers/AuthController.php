<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles user registration (defaulting to pending Penyewa), login with approval checks,
 * and session logout.
 */
class AuthController extends Controller
{
    /**
     * Display the login form.
     *
     * @return View Login page view.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate a user and block accounts that are not yet approved.
     *
     * @param Request $request HTTP request containing email and password.
     *
     * @return RedirectResponse Redirect to role home or back with error message.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email or password is incorrect.');
        }

        $user = Auth::user();
        if ($user === null || !$user->isApproved()) {
            $status = $user?->status ?? 'pending';
            $this->terminateSession($request);

            $message = $status === 'rejected'
                ? 'Your registration was rejected by an administrator.'
                : 'Your account is pending Admin verification after KTP review.';

            return back()
                ->withInput($request->only('email'))
                ->with('error', $message);
        }

        // Regenerate the session ID after login to prevent session fixation attacks.
        $request->session()->regenerate();

        return $user->isAdmin()
            ? redirect()->route('admin.reports.index')
            : redirect()->route('penyewa.rentals.index');
    }

    /**
     * Display the renter registration form.
     *
     * @return View Registration page view.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new Penyewa account with pending verification status.
     *
     * @param Request $request HTTP request containing 16-digit NIK and account details.
     *
     * @return RedirectResponse Redirect to login with pending verification notice.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::create([
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'penyewa',
            'status' => 'pending',
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registration submitted. Please wait for Admin KTP verification before signing in.');
    }

    /**
     * Log out the authenticated user and invalidate their session.
     *
     * @param Request $request Current HTTP request.
     *
     * @return RedirectResponse Redirect to the login page.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->terminateSession($request);

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }

    /**
     * Clear authentication state, invalidate the session, and rotate the CSRF token.
     *
     * @param Request $request Current HTTP request.
     *
     * @return void
     */
    private function terminateSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
