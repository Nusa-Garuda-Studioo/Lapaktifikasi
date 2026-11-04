<?php

namespace App\Http\Controllers;

use App\Models\ProdukModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ScreenshotsProdukModel;
use ZipArchive;

class PengaturanController extends Controller
{
    public function index()
    {
        return view('pengaturan.ganti_password');
    }

    public function proses_ganti_password(Request $request)
    {
        $rules = [
            'password_lama' => 'required',
            'password_baru' => 'required|min:10',
            'konfirmasi_password_baru' => 'required|same:password_baru'
        ];

        $messages = [
            'password_lama.required' => 'Password saat ini (lama) harus diisi.',
            'password_baru.required' => 'Password baru harus diisi.',
            'password_baru.min' => 'Password baru setidaknya minimal 10 karakter.',
            'konfirmasi_password_baru.required' => 'Konfirmasi password harus diisi.',
            'konfirmasi_password_baru.same' => 'Konfirmasi password tidak cocok dengan password baru.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect('/ganti_password')
                ->withErrors($validator)
                ->withInput();
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user && session()->has('id')) {
            $user = User::find(session('id'));
        }

        if (!$user) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (!Hash::check($request->input('password_lama'), $user->password)) {
            return redirect('/ganti_password')
                ->with('error', 'Password lama yang Anda masukkan salah.')
                ->withInput();
        }

        $password_baru = $request->input('password_baru');
        $user->update([
            'password' => Hash::make($password_baru),
            'must_change_password' => false
        ]);

        Session::flash('success', 'Password berhasil diubah.');
        return redirect('/ganti_password');
    }
}
