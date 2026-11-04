<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\CustomerModel;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends ApiController
{
    protected function setIconProfile()
    {
        $directory = public_path('assets/img/avatar/');
        if (!file_exists($directory)) {
            return 'default.png'; // Fallback if directory doesn't exist yet
        }
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

        if (empty($icons)) return 'default.png';
        return $icons[array_rand($icons)];
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            
            // Check if user is banned
            if ($user->is_banned) {
                Auth::logout();
                return $this->sendError('Akun Anda telah dinonaktifkan/dibanned. Alasan: ' . ($user->banned_reason ?? 'Pelanggaran ketentuan layanan.'), [
                    'is_banned' => true,
                    'banned_reason' => $user->banned_reason
                ], 403);
            }

            // Check if seller's store is banned
            if ($user->role_id == 3) {
                $toko = Toko::where('user_id', $user->id)->first();
                if ($toko && $toko->is_banned) {
                    Auth::logout();
                    return $this->sendError('Toko Anda telah dinonaktifkan/dibanned. Alasan: ' . ($toko->banned_reason ?? 'Pelanggaran ketentuan layanan.'), [
                        'is_banned' => true,
                        'banned_reason' => $toko->banned_reason
                    ], 403);
                }
            }

            // Revoke old tokens if any (optional clean up)
            // Create Sanctum Token
            $token = $user->createToken('API_TOKEN')->plainTextToken;

            $roleName = match((int)$user->role_id) {
                1 => 'admin',
                3 => 'seller',
                default => 'customer',
            };

            $data = [
                'user' => $user,
                'token' => $token,
                'role' => $roleName,
            ];

            if ($user->role_id == 2) {
                $data['customer'] = CustomerModel::with('tier')->where('user_id', $user->id)->first();
            } elseif ($user->role_id == 3) {
                $data['toko'] = Toko::with('badges')->where('user_id', $user->id)->first();
            }

            return $this->sendResponse($data, 'Berhasil login');
        }

        return $this->sendError('Email atau password salah', [], 401);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:10',
            'no_whatsapp' => 'nullable|string|max:20',
            'ref' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        $email = $request->email;
        $newUser = User::create([
            'email' => $email,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'profile_picture' => $this->setIconProfile(),
            'role_id' => 2
        ]);

        $refCode = $request->input('ref');
        $referrer = null;
        if (!empty($refCode)) {
            $referrer = CustomerModel::where('kode_referral', strtoupper(trim($refCode)))->first();
        }

        CustomerModel::create([
            'user_id' => $newUser->id,
            'nama_customer' => $request->name,
            'nomor_telepon' => $request->no_whatsapp,
            'direferensikan_oleh' => $referrer?->id,
        ]);

        // Auto login after register and return token
        $token = $newUser->createToken('API_TOKEN')->plainTextToken;

        $data = [
            'user' => $newUser,
            'token' => $token,
            'role' => 'customer'
        ];

        return $this->sendResponse($data, 'Berhasil mendaftar', 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse([], 'Berhasil logout');
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->sendResponse([], 'Link reset password telah dikirim ke email');
        }

        return $this->sendError('Email tidak terdaftar atau gagal mengirim link reset password', [], 404);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:10|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors()->toArray(), 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse([], 'Password berhasil diubah');
        }

        return $this->sendError('Gagal mereset password. Pastikan token masih valid.', [], 400);
    }
}
