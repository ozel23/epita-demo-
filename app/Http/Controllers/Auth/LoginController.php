<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class LoginController extends Controller
{
    use AuthenticatesUsers;
    protected $redirectTo = '/dashboard';
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function showLoginForm()
    {
        return \Inertia\Inertia::render('auth.login');
    }
    public function username()
    {
        return 'login';
    }
    protected function credentials(Request $request)
    {
        $field = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        return [$field => $request->input('login'), 'password' => $request->input('password'), 'active' => true];
    }
    protected function authenticated(Request $request, $user)
    {
        $request->session()->put('security_version', $user->security_version);
        AuditService::record('auth.login');
        if ($user->plants()->count() === 1) {
            $request->session()->put('active_plant_id', $user->plants()->first()->id);
        }
    }
    protected function sendFailedLoginResponse(Request $request)
    {
        AuditService::record('auth.failed');
        throw ValidationException::withMessages(['login' => 'Identitas atau kata sandi tidak sesuai.']);
    }
}
