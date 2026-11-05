<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index($id)
    {
        $currentUser = Auth::user();
        if ($currentUser && method_exists($currentUser, 'isSeller') && $currentUser->isSeller()) {
            return redirect('/seller/profil');
        }
        if ($currentUser && method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()) {
            return redirect()->route('admin.setting_website');
        }

        $user = User::find($id);

        if ($user && $user->role_id != 1) {
            CustomerModel::firstOrCreate([
                'user_id' => $user->id
            ]);
            $user->load('customer');
        }

        return view('customer.index', compact('user'));
    }

    public function update_profile(Request $request)
    {
        $customer = Auth::user();
        if (!$customer) {
            return redirect()->back()->with('error', 'User not authenticated.');
        }

        $nomorTeleponCustomer = CustomerModel::firstOrCreate([
            'user_id' => $customer->id
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'nomor_telepon' => 'required|string|min:9|max:20'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->with('error', 'Profile Anda gagal di ubah.');
        }

        if (
            $request->input('name') == $customer->name
            && $request->input('nomor_telepon') == $nomorTeleponCustomer->nomor_telepon
        ) {
            Session::flash('warning', 'Anda tidak memperbarui apapun.');
            return redirect('profile_customer/' . $customer->id);
        }

        $customer->update(['name' => $request->input('name')]);
        $nomorTeleponCustomer->update(['nomor_telepon' => $request->input('nomor_telepon')]);

        Session::flash('success', 'Profile Anda berhasil di update.');
        return redirect('profile_customer/' . $customer->id);
    }
}
