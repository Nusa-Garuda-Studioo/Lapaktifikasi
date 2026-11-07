<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan composite indexes untuk mempercepat TTFB dan query bottleneck.
     */
    public function up(): void
    {
        // 1. Index untuk auto-release reservasi kedaluwarsa pada tbl_stok_akun
        if (Schema::hasTable('tbl_stok_akun')) {
            Schema::table('tbl_stok_akun', function (Blueprint $table) {
                $table->index(['status', 'reserved_expired_at'], 'idx_stok_status_reserved_exp');
            });
        }

        // 2. Index untuk filter riwayat customer dan auto-expire pembelian pada tbl_pembelian
        if (Schema::hasTable('tbl_pembelian')) {
            Schema::table('tbl_pembelian', function (Blueprint $table) {
                $table->index(['id_customer', 'status', 'created_at'], 'idx_pembelian_cust_status_created');
                $table->index(['status', 'reserved_until'], 'idx_pembelian_status_reserved_until');
            });
        }

        // 3. Index untuk filter katalog aktif dan produk toko pada tbl_produk
        if (Schema::hasTable('tbl_produk')) {
            Schema::table('tbl_produk', function (Blueprint $table) {
                $table->index(['status', 'tipe_produk'], 'idx_produk_status_tipe');
                $table->index(['id_toko', 'status'], 'idx_produk_toko_status');
            });
        }

        // 4. Index untuk pagination ulasan toko dan agregasi distribusi rating pada tbl_review
        if (Schema::hasTable('tbl_review')) {
            Schema::table('tbl_review', function (Blueprint $table) {
                $table->index(['id_toko', 'created_at'], 'idx_review_toko_created');
                $table->index(['id_toko', 'rating'], 'idx_review_toko_rating');
            });
        }

        // 5. Index untuk listing direktori toko aktif pada tbl_toko
        if (Schema::hasTable('tbl_toko')) {
            Schema::table('tbl_toko', function (Blueprint $table) {
                $table->index(['status', 'is_banned'], 'idx_toko_status_banned');
            });
        }

        // 6. Index untuk pagination mutasi saldo seller pada tbl_mutasi_saldo
        if (Schema::hasTable('tbl_mutasi_saldo')) {
            Schema::table('tbl_mutasi_saldo', function (Blueprint $table) {
                $table->index(['id_toko', 'created_at'], 'idx_mutasi_saldo_toko_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_stok_akun')) {
            Schema::table('tbl_stok_akun', function (Blueprint $table) {
                $table->dropIndex('idx_stok_status_reserved_exp');
            });
        }

        if (Schema::hasTable('tbl_pembelian')) {
            Schema::table('tbl_pembelian', function (Blueprint $table) {
                $table->dropIndex('idx_pembelian_cust_status_created');
                $table->dropIndex('idx_pembelian_status_reserved_until');
            });
        }

        if (Schema::hasTable('tbl_produk')) {
            Schema::table('tbl_produk', function (Blueprint $table) {
                $table->dropIndex('idx_produk_status_tipe');
                $table->dropIndex('idx_produk_toko_status');
            });
        }

        if (Schema::hasTable('tbl_review')) {
            Schema::table('tbl_review', function (Blueprint $table) {
                $table->dropIndex('idx_review_toko_created');
                $table->dropIndex('idx_review_toko_rating');
            });
        }

        if (Schema::hasTable('tbl_toko')) {
            Schema::table('tbl_toko', function (Blueprint $table) {
                $table->dropIndex('idx_toko_status_banned');
            });
        }

        if (Schema::hasTable('tbl_mutasi_saldo')) {
            Schema::table('tbl_mutasi_saldo', function (Blueprint $table) {
                $table->dropIndex('idx_mutasi_saldo_toko_created');
            });
        }
    }
};
