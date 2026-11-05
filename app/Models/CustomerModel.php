<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerModel extends Model
{
    use HasFactory;

    protected $table = "tbl_customer";
    protected $fillable = [
        'user_id',
        'nama_customer',
        'nomor_telepon',
        'id_tier_saat_ini',
        'total_belanja_akumulasi',
        'kode_referral',
        'direferensikan_oleh',
        'jumlah_referral_sukses',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (empty($customer->kode_referral)) {
                $customer->kode_referral = 'REF-' . strtoupper(\Illuminate\Support\Str::random(6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CustomerTier::class, 'id_tier_saat_ini', 'id_tier');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'direferensikan_oleh', 'id');
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerModel::class, 'direferensikan_oleh', 'id');
    }

    public function voucherKlaims(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VoucherKlaim::class, 'id_customer', 'id');
    }

    public function pembelian(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pembelian::class, 'id_customer', 'id');
    }

    public function tierLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerTierLog::class, 'id_customer', 'id');
    }

    public function progressKeTierBerikutnya(): array
    {
        $tierSaatIni = $this->tier;
        if (!$tierSaatIni) {
            $tierSaatIni = CustomerTier::orderBy('urutan', 'asc')->first();
        }

        $totalBelanja = (float) $this->total_belanja_akumulasi;
        $currentUrutan = $tierSaatIni ? $tierSaatIni->urutan : 0;

        $tierBerikutnya = CustomerTier::where('urutan', '>', $currentUrutan)
            ->orderBy('urutan', 'asc')
            ->first();

        if (!$tierBerikutnya) {
            return [
                'tier_saat_ini' => $tierSaatIni,
                'tier_berikutnya' => null,
                'sisa_nominal' => 0.0,
                'persentase_progress' => 100.0,
            ];
        }

        $minCurrent = $tierSaatIni ? (float) $tierSaatIni->minimal_belanja : 0.0;
        $minNext = (float) $tierBerikutnya->minimal_belanja;

        $sisaNominal = max(0.0, $minNext - $totalBelanja);
        $span = $minNext - $minCurrent;

        if ($span > 0) {
            $gained = max(0.0, $totalBelanja - $minCurrent);
            $progress = min(100.0, max(0.0, ($gained / $span) * 100.0));
        } else {
            $progress = 100.0;
        }

        return [
            'tier_saat_ini' => $tierSaatIni,
            'tier_berikutnya' => $tierBerikutnya,
            'sisa_nominal' => round($sisaNominal, 2),
            'persentase_progress' => round($progress, 2),
        ];
    }
}
