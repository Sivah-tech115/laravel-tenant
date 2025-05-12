<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to the appropriate home screen. The controller uses 
    | a trait to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // Validate login credentials
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate the user
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Regenerate the session to prevent session fixation
            $request->session()->regenerate();

            // Redirect based on the user's role
            $user = Auth::user();

            // Check user role and redirect accordingly
            if ($user->role === 'admin') {
                return redirect()->route('main.admin'); // Redirect to main admin dashboard
            }

            if ($user->role === 'tenant_admin') {
                $tenant = explode('.', request()->getHost())[0]; // Extract subdomain // You should store tenant information in user model or session
                return redirect()->route('tenant.admin', ['tenant' => $tenant]); // Redirect to tenant admin dashboard
            }

            // Default redirect if no specific role is found
            return redirect()->intended('/login'); 
        }

        // If authentication fails, redirect back with an error message
        return back()->with('error', 'These credentials do not match our records.');
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
