<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\News;
use Illuminate\Support\Str;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for SEO crawlers
     */
    public function index(): Response
    {
        $urls = [];

        // 1. Static Pages
        $staticPages = [
            [
                'loc' => url('/'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => url('/katalog'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ],
            [
                'loc' => url('/news'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8'
            ],
            [
                'loc' => url('/daftar_toko'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ],
            [
                'loc' => url('/join-partner'),
                'lastmod' => now()->startOfWeek()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'loc' => url('/daftar-jadi-seller'),
                'lastmod' => now()->startOfWeek()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5'
            ],
            [
                'loc' => url('/syarat-ketentuan'),
                'lastmod' => now()->startOfMonth()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.4'
            ],
            [
                'loc' => url('/kebijakan-privasi'),
                'lastmod' => now()->startOfMonth()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.4'
            ],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $page;
        }

        // 2. Active Products (Shopee-Style Product URLs)
        try {
            $products = Produk::where('status', 'aktif')->with('toko')->get();
            foreach ($products as $product) {
                $storeSlug = $product->toko && !empty($product->toko->slug) 
                    ? $product->toko->slug 
                    : ($product->toko ? Str::slug($product->toko->nama_toko) : 'store');
                
                $productSlug = Str::slug($product->nama_produk) . '-' . $product->id_produk;
                $productUrl = url('/toko/' . $storeSlug . '/produk/' . $productSlug);

                $lastmod = $product->updated_at ? $product->updated_at->toAtomString() : now()->toAtomString();

                $itemData = [
                    'loc' => $productUrl,
                    'lastmod' => $lastmod,
                    'changefreq' => 'weekly',
                    'priority' => '0.9'
                ];

                if (!empty($product->gambar)) {
                    $itemData['image_loc'] = asset('assets/img/produk_premium/' . $product->gambar);
                    $itemData['image_title'] = $product->nama_produk;
                }

                $urls[] = $itemData;
            }
        } catch (\Throwable $e) {
            // Safe fallback if table issue
        }

        // 3. Active Stores / Toko
        try {
            $stores = Toko::where('status', 'aktif')->get();
            foreach ($stores as $store) {
                if (!empty($store->slug)) {
                    $urls[] = [
                        'loc' => url('/toko/' . $store->slug . '/produk'),
                        'lastmod' => $store->updated_at ? $store->updated_at->toAtomString() : now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7'
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // 4. Published News / Berita
        try {
            $newsList = News::published()->get();
            foreach ($newsList as $item) {
                if (!empty($item->slug)) {
                    $itemData = [
                        'loc' => url('/news/' . $item->slug),
                        'lastmod' => $item->updated_at ? $item->updated_at->toAtomString() : now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8'
                    ];

                    if (!empty($item->gambar_url)) {
                        $itemData['image_loc'] = $item->gambar_url;
                        $itemData['image_title'] = $item->judul;
                    }

                    $urls[] = $itemData;
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // Generate XML string
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<!-- LLMs Discovery Knowledge Base: ' . url('/llms.txt') . ' -->' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
                'xmlns:xhtml="http://www.w3.org/1999/xhtml" ' .
                'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
            }
            if (!empty($u['changefreq'])) {
                $xml .= "    <changefreq>" . $u['changefreq'] . "</changefreq>\n";
            }
            if (!empty($u['priority'])) {
                $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            }
            if (!empty($u['image_loc'])) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . htmlspecialchars($u['image_loc'], ENT_XML1, 'UTF-8') . "</image:loc>\n";
                if (!empty($u['image_title'])) {
                    $xml .= "      <image:title>" . htmlspecialchars($u['image_title'], ENT_XML1, 'UTF-8') . "</image:title>\n";
                }
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex' // Sitemap itself shouldn't be indexed as a webpage
        ]);
    }
}
