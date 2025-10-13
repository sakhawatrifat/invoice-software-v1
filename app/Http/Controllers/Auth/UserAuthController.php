<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\Currency;
use App\Mail\ActivateAccount;
use App\Mail\ResetUserPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class UserAuthController extends Controller
{

    public function checkAuth()
    {
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }
    }

    public function loginForm()
    {
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|max:255|email',
            'password' => 'required|min:8|max:255',
        ]);

        $remember = $request->has('remember') ? true : false;

        $user = User::where('email', $request->email)->first();
        if(!empty($user) && $user->status != 'Active'){
            return back()->with('error', 'Your account is currently restricted!');
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $remember)) {
            //return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
            if($user->user_type == 'admin'){
                return redirect()->intended(route('admin.dashboard'));
            }else{
                return redirect()->intended(route('user.dashboard'));
            }
        } else {
            return back()->with('error' , 'User or Password is Invalid!',);
        }
    }

    public function forgotPasswordForm()
    {
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }
        return view('auth.passwords.email');
    }

    public function forgotPassword(Request $request)
    {
        if (Auth::user()) {
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        $request->validate([
            'email' => 'required|max:255|email',
        ]);

        $user = User::whereEmail($request->email)->first();

        if (!$user) {
            return back()->with('error', 'Email Not Found!',);
        }

        // Check if the user has already requested a reset within the last minute
        $lastResetRequest = DB::table('password_reset_tokens')
                              ->where('email', $request->email)
                              ->orderBy('created_at', 'desc')
                              ->first();

        $duration = 60; // Seconds
        if ($lastResetRequest) {
            $createdAt = Carbon::parse($lastResetRequest->created_at)->setTimezone(env('APP_TIMEZONE'));
            $now = Carbon::now(env('APP_TIMEZONE'));

            $elapsed = $createdAt->diffInSeconds($now, false); // signed difference

            if ($elapsed < $duration) {
                $remainingSeconds = max(0, $duration - $elapsed);
                $minutes = floor($remainingSeconds / 60);
                $seconds = $remainingSeconds % 60;

                //return back()->with('error', 'Please try again after ' . $minutes . ' minute(s) ' . $seconds . ' second(s).');
                return back()->with('error', 'Please try again after finished the countdown.');
            }
        }



        $permitted_chars = 'abcdefghijkl0123456789mnopqrstuvwxyz';
        $token = substr(str_shuffle($permitted_chars), 0, 7);
        $enableAt = Carbon::now(env('APP_TIMEZONE'))->addSeconds($duration);
        Session::put('reset_token_enable_at', $enableAt);

        DB::table('password_reset_tokens')->whereEmail($request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => md5($token),
            'created_at' => Carbon::now(),
        ]);

        $content = [
            'email' => $user->email,
            'token' => $token,
        ];

        if (env('MAIL_USERNAME') != null || env('MAIL_USERNAME') != '') {
            try {
                Mail::to($request->email)->send(new ResetUserPassword($content));
            } catch (\Exception $e) {
                //return $e->getMessage();
            }
        }

        //return redirect(route('password.forget.form'))->with([
        return redirect()->back()->with([
            'message' => 'We have emailed your password reset link! Check your mail. Please wait a while and after that if you don\'t get any mail try again please.',
        ]);
    }


    public function resetPasswordForm($token){
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        $reset = DB::table('password_reset_tokens')->where('token',md5($token))->first();
        if (!$reset) {
            return redirect(route('login'))->with('error' , 'Invlid Token!');
        }

        return view('auth.passwords.reset', get_defined_vars());
    }

    public function resetPassword(Request $request){
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        $request->validate([
            'email' => 'required|max:255|email',
            'password' => 'required|min:8|max:255|confirmed',
        ]);

        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return back()->with('error', 'Email Not Found!',);
        }

        $reset = DB::table('password_reset_tokens')->where('email', $request->email)->where('token',md5($request->token))->first();
        if (!$reset) {
            return back()->with('error', 'Invlid Token!',);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('password_reset_tokens')->whereEmail($request->email)->delete();

        return redirect(route('login'))->with([
            'message' => 'Your password has been changed.Login to continue.'
        ]);
    }


    public function registerForm(){
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        if(homepageData()->is_registration_enabled != 1){
            return redirect(route('home'));
        }

        return view('auth.register', get_defined_vars());
    }


    public function register(Request $request){
        if(Auth::user()){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        if(homepageData()->is_registration_enabled != 1){
            return redirect(route('home'));
        }

        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            //'phone' => 'required|regex:/^(?:\+?88)?01[13-9]\d{8}$/|unique:users,phone',
            //'phone' => 'required|unique:users,phone',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        //dd($request->all());

        $userType = 'user';
        $allPermissions = getPermissionList();
        $permissions = collect($allPermissions)->filter(function ($item) use ($userType) {
            return $userType === 'admin' || $item['for'] === 'all_user';
        });

        $permissions = $permissions
            ->pluck('permissions')
            ->flatten(1)
            ->pluck('key')
            ->toArray();
        
        DB::beginTransaction();
        try {
            $user = new User();
            $user->uid = uniqid();
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->otp = generateUniqueCode('User', 'otp');
            $user->device_token = uniqid();
            $user->ip_address = request()->ip();
            $user->status = 'Active';
            $user->default_language = 'en';
            $user->permissions = $permissions;
            $user->save();

            $userComany = new UserCompany();
            $userComany->user_id = $user->id;
            $userComany->company_name = $request->company_name;
            $userComany->phone_1 = $request->phone ?? null;
            $userComany->email_1 = $request->email;
            $userComany->currency_id = 11;
            $userComany->save();

            DB::commit();

            $content = [
                'user' => $user,
                'userComany' => $userComany,
            ];
            if (env('MAIL_USERNAME') != null || env('MAIL_USERNAME') != '') {
                try {
                    Mail::to($request->email)->send(new ActivateAccount($content));
                } catch (\Exception $e) {
                    \Log::error("Error during send registration mail: " . $e->getMessage());
                }
            }

            $duration = 60; //Second
            $enableAt = Carbon::now(env('APP_TIMEZONE'))->addSeconds($duration);
            Session::put('verify_token_enable_at', $enableAt);
            Session::put('verify_email', $enableAt);

            Auth::loginUsingId($user->id, true);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Error during registration: " . $e->getMessage());

            return redirect()->back()->withInput()->with([
                'message' => 'Something went wrong. Please try again.',
            ]);
        }

        return redirect()->route('account.verify')->with([
            'message' => 'We have emailed your account verification link! Check your mail. Please wait a while and after that if you don\'t get any mail try again please.'
        ]);
    }


    public function accountVerifyForm(Request $request){
        if (!Auth::check()) {
            return redirect(route('login'))->with([
                'message' => 'Verification link expired. Login again and verify your account.'
            ]);
        }

        if(Auth::user()->email_verified_at != null){
            if(Auth::user()->user_type == 'admin'){
                return redirect()->intended(route('admin.dashboard'));
            }else{
                return redirect()->intended(route('user.dashboard'));
            }
        }
        
        $user = Auth::user();

        return view('auth.account-activate');
    }


    public function accountVerifyCodeResend(){
        if(Auth::user() && Auth::user()->email_verified_at != null){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        if (!Auth::check()) {
            return redirect(route('login'))->with([
                'message' => 'Verification link expired. Login again and verify your account.'
            ]);
        }

        $enabledAt = Session::get('verify_token_enable_at');
        $now = Carbon::now(env('APP_TIMEZONE'));

        if ($enabledAt) {
            $enabledAt = Carbon::parse($enabledAt); // Ensure it's a Carbon instance

            if ($now->lessThanOrEqualTo($enabledAt)) {
                $remainingSeconds = max(0, $now->diffInSeconds($enabledAt, false)); // signed diff
                $minutes = floor($remainingSeconds / 60);
                $seconds = $remainingSeconds % 60;

                //return back()->with('error', 'Please try again after ' . $minutes . ' minute(s) ' . $seconds . ' second(s).');
                return back()->with('error', 'Please try again after finished the countdown.');
            }
        }

        $user = Auth::user();
        $content = [
                'user' => $user,
            ];
            if (env('MAIL_USERNAME') != null || env('MAIL_USERNAME') != '') {
                try {
                    Mail::to($user->email)->send(new ActivateAccount($content));
                } catch (\Exception $e) {
                    \Log::error("Error during resend registration mail: " . $e->getMessage());
                }
            }

            $duration = 60; // Seconds
            $enableAt = Carbon::now(env('APP_TIMEZONE'))->addSeconds($duration);
            Session::put('verify_token_enable_at', $enableAt);
            Session::put('verify_email', $enableAt);

            return redirect()->back()->with([
                'message' => 'We have emailed your account verification link! Check your mail. Please wait a while and after that if you don\'t get any mail try again please.'
            ]);
    }


    public function accountVerify(Request $request){
        if(Auth::user() && Auth::user()->email_verified_at != null){
            return redirect(Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard'));
        }

        if (!Auth::check()) {
            return redirect(route('login'))->with([
                'message' => 'Verification link expired. Login again and verify your account.'
            ]);
        }

        if(!isset($request->email) && !isset($request->otp)){
            abort(404);
        }
        
        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();
        if(!$user){
            abort(404);
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        return redirect()->route('user.dashboard')->with([
            'message' => 'Your account ativated successfully.'
        ]);
    }




    public function logout(Request $request)
    {
        Session::forget('bbf_popup_notice_read_status');
        Auth::logout();
        return redirect(route('login'));
    }
}
