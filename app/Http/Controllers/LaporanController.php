<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\User;
use App\Services\Laporan\LaporanService;
use App\Services\Telegram\NotificationRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LaporanController extends Controller
{
    protected LaporanService $laporanService;

    public function __construct(LaporanService $laporanService)
    {
        $this->laporanService = $laporanService;
    }

    // Customer: list reports and display form
    public function index()
    {
        $userId = Auth::id();
        $laporan = Laporan::with(['pesan'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('laporan.customer_index', compact('laporan'));
    }

    // Customer: store report
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'judul.required' => 'Judul laporan harus diisi.',
            'deskripsi.required' => 'Deskripsi laporan harus diisi.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Ensure public/assets/img/laporan directory exists
            $targetDir = public_path('assets/img/laporan');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            
            $file->move($targetDir, $filename);
            $gambarPath = $filename;
        }

        try {
            $this->laporanService->createTicket(Auth::user(), [
                'judul' => $request->input('judul'),
                'deskripsi' => $request->input('deskripsi'),
                'gambar' => $gambarPath,
                'sumber' => 'web',
            ]);

            Session::flash('success', 'Laporan Anda berhasil dikirim.');
            return redirect()->route('customer.laporan');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    // Show thread conversation
    public function show($id)
    {
        $laporan = Laporan::with(['user.customer', 'pesan.sender'])->findOrFail($id);
        $user = Auth::user();

        $this->authorizeTicketAccess($laporan, $user);

        $context = app(NotificationRouter::class)->resolveTicketContext($laporan);

        return view('laporan.show', compact('laporan', 'context'));
    }

    // Post reply in ticket thread
    public function store_pesan(Request $request, $id)
    {
        $laporan = Laporan::findOrFail($id);
        $user = Auth::user();

        $this->authorizeTicketAccess($laporan, $user);

        $validator = Validator::make($request->all(), [
            'pesan' => 'required|string|max:5000',
            'lampiran' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
        ], [
            'pesan.required' => 'Pesan balasan tidak boleh kosong.',
            'lampiran.mimes' => 'Format berkas lampiran harus JPG, PNG, GIF, atau PDF.',
            'lampiran.max' => 'Ukuran lampiran maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $targetDir = public_path('assets/img/laporan');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $file->move($targetDir, $filename);
            $lampiranPath = $filename;
        }

        $this->laporanService->addMessage(
            $laporan,
            $user,
            $request->input('pesan'),
            'web',
            $lampiranPath
        );

        Session::flash('success', 'Pesan balasan berhasil dikirim.');
        return redirect()->route('laporan.show', $laporan->id);
    }

    // Admin: list all reports
    public function admin_index()
    {
        $laporan = Laporan::with(['user.customer', 'pesan'])->orderBy('created_at', 'desc')->get();
        return view('laporan.admin_index', compact('laporan'));
    }

    // Admin: update report status
    public function update_status(Request $request, $id)
    {
        $laporan = Laporan::findOrFail($id);
        $status = $request->input('status');

        if (in_array($status, ['pending', 'proses', 'selesai'])) {
            $this->laporanService->updateStatus(
                $laporan,
                Auth::user(),
                $status,
                'web_admin',
                'Status diupdate via dashboard admin web'
            );

            Session::flash('success', 'Status laporan berhasil diupdate.');
        } else {
            Session::flash('error', 'Status laporan tidak valid.');
        }

        return redirect()->back();
    }

    /**
     * Otorisasi hak akses user ke tiket.
     */
    protected function authorizeTicketAccess(Laporan $laporan, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($laporan->user_id === $user->id) {
            return;
        }

        if ($laporan->assigned_to === $user->id) {
            return;
        }

        $context = app(NotificationRouter::class)->resolveTicketContext($laporan);
        $seller = $context['seller_user'] ?? null;
        if ($seller && $seller->id === $user->id) {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses ke tiket laporan ini.');
    }
}
