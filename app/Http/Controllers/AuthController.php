<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerModel;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{

    protected $messages = [
        'email.required' => 'Email harus diisi.',
        'email.email' => 'Email harus valid.',
        'email.unique' => 'Email sudah terdaftar, silakan gunakan email lain atau login.',
        'password.required' => 'Password harus diisi.',
        'password.min' => 'Password harus memiliki setidaknya :min karakter.',
    ];

    protected function setSessionFlash($detectMessage, $message)
    {
        Session::flash($detectMessage, $message);
    }

    protected function setSessionHelper($param1, $param2, $param3, $param4)
    {
        $initSession = session([
            'id' => $param1,
            'profile_picture' => $param2,
            'nama_lengkap' => $param3,
            'email' => $param4
        ]);
        return $initSession;
    }

    protected function setIconProfile()
    {
        $directory = public_path('assets/img/avatar/');
        $contents = scandir($directory);
        $contents = array_diff($contents, array('..', '.'));
        $icons = [];

        foreach ($contents as $item) {
            if ($item == 'avatar-admin.png') {
                continue;
            } else {
                $icons[] = $item;
            }
        }

        $randomIcon = $icons[array_rand($icons)];
        return $randomIcon;
    }

    protected function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public function index(Request $request)
    {
        if ($request->query('ref')) {
            session(['ref' => strtoupper(trim($request->query('ref')))]);
        }
        return view('auth.login');
    }

    public function proses_login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $userFromDatabase = User::where('email', $email)->first();

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);
        $remember = $request->has('remember') ? true : false;

        if ($validator->fails()) {
            return redirect('/login')
                ->withErrors($validator)
                ->withInput();
        } else if (Auth::attempt([
            'email' => $email,
            'password' => $password
        ], $remember)) {

            $request->session()->regenerate();
            
            // Check if banned
            if (Auth::user()->is_banned) {
                $reason = Auth::user()->banned_reason;
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('banned.page')->with('banned_reason', $reason);
            }

            $this->setSessionHelper(
                $userFromDatabase->id,
                $userFromDatabase->profile_picture,
                $userFromDatabase->name,
                $userFromDatabase->email
            );
            $this->setSessionFlash('success', 'Selamat datang di Lapaktifikasi.');
            if ($userFromDatabase->role_id == 1) {
                return redirect('/dashboard');
            } elseif ($userFromDatabase->role_id == 3) {
                return redirect('/seller/dashboard');
            } else {
                return redirect()->intended(route('premium.katalog'));
            }
        } else {
            $this->setSessionFlash('error', 'Proses login gagal. Pastikan dengan memasukkan identitas dengan benar.');
            return redirect('/login')->withErrors($validator)
                ->withInput();
        }
    }

    public function pendaftaran(Request $request)
    {
        $ref = $request->query('ref');
        if ($ref) {
            session(['ref' => strtoupper(trim($ref))]);
        }
        return view('auth.pendaftaran', compact('ref'));
    }

    public function proses_pendaftaran(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $rules = [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:10',
        ];

        $validator = Validator::make($request->all(), $rules, $this->messages);

        if ($validator->fails()) {
            return redirect('/pendaftaran')
                ->withErrors($validator)
                ->withInput();
        } else {

            $newUser = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'profile_picture' => $this->setIconProfile(),
                'role_id' => 2
            ]);

            $refCode = strtoupper(trim((string) ($request->input('ref') ?? session('ref'))));
            $referrer = null;
            if (!empty($refCode)) {
                $referrer = CustomerModel::where('kode_referral', $refCode)->first();
            }

            CustomerModel::create([
                'user_id' => $newUser->id,
                'direferensikan_oleh' => $referrer?->id,
            ]);

            session()->forget('ref');

            $this->setSessionFlash('success', 'Berhasil mendaftar. Silakan login terlebih dahulu.');
            return redirect('/login')->withInput();
        }
    }

    public function redirect(Request $request)
    {
        if ($request->query('ref')) {
            session(['ref' => strtoupper(trim($request->query('ref')))]);
        }
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {

        $randomString = $this->generateRandomString(15);
        $userFromGoogle = Socialite::driver('google')->user();
        $userFromDatabase = User::where('email', $userFromGoogle->getEmail())->first();

        if (!$userFromDatabase) {

            $newUser = User::create([
                'email' => $userFromGoogle->getEmail(),
                'name' => $userFromGoogle->getName(),
                'password' => Hash::make($randomString),
                'profile_picture' => $this->setIconProfile(),
                'role_id' => 2
            ]);

            $refCode = session('ref');
            $referrer = null;
            if (!empty($refCode)) {
                $referrer = CustomerModel::where('kode_referral', strtoupper(trim($refCode)))->first();
            }

            CustomerModel::create([
                'user_id' => $newUser->id,
                'direferensikan_oleh' => $referrer?->id,
            ]);

            session()->forget('ref');

            Auth::login($newUser);
            session()->regenerate();
            $this->setSessionHelper(
                $newUser->id,
                $newUser->profile_picture,
                $newUser->name,
                $newUser->email
            );
            $this->setSessionFlash('success', 'Berhasil mendaftar. Selamat datang di Lapaktifikasi.');
            if ($newUser->role_id == 1) {
                return redirect('/dashboard');
            } elseif ($newUser->role_id == 3) {
                return redirect('/seller/dashboard');
            } else {
                return redirect('profile_customer/' . $newUser->id);
            }
        } else {

            // Check if banned
            if ($userFromDatabase->is_banned) {
                return redirect()->route('banned.page')->with('banned_reason', $userFromDatabase->banned_reason);
            }

            Auth::login($userFromDatabase);
            session()->regenerate();
            $this->setSessionHelper(
                $userFromDatabase->id,
                $userFromDatabase->profile_picture,
                $userFromDatabase->name,
                $userFromDatabase->email
            );
            $this->setSessionFlash('success', 'Selamat datang di Lapaktifikasi.');
            if ($userFromDatabase->role_id == 1) {
                return redirect('/dashboard');
            } elseif ($userFromDatabase->role_id == 3) {
                return redirect('/seller/dashboard');
            } else {
                return redirect('profile_customer/' . $userFromDatabase->id);
            }
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $this->setSessionFlash('logout', 'Anda telah berhasil logout.');

        return redirect('/');
    }
}
