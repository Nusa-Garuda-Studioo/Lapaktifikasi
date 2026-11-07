<?php

/**
 * Image Optimization & Responsive Generation Script for Lapaktifikasi Landing Page
 * Performance Targets:
 * - Each image < 150 KB
 * - Hero mobile < 100 KB
 * - Total landing transfer < 1 MB
 * - Backup original files to storage/app/image_backups/
 */

set_time_limit(300);
ini_set('memory_limit', '512M');

$baseDir = __DIR__;
$backupDir = $baseDir . '/storage/app/image_backups';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
if (!is_dir($backupDir . '/mitra_industri')) {
    mkdir($backupDir . '/mitra_industri', 0755, true);
}

$hasGd = extension_loaded('gd');
$hasWebp = function_exists('imagewebp');
$hasAvif = function_exists('imageavif');

echo "GD Extension: " . ($hasGd ? "YES" : "NO") . "\n";
echo "WebP Support: " . ($hasWebp ? "YES" : "NO") . "\n";
echo "AVIF Support: " . ($hasAvif ? "YES" : "NO") . "\n";
echo str_repeat("=", 70) . "\n";

$auditResults = [];

function backupFile($src, $dest) {
    if (file_exists($src) && !file_exists($dest)) {
        copy($src, $dest);
        echo "Backed up: $src -> $dest\n";
    }
}

function resizeAndSave($srcPath, $destPath, $targetWidth, $targetHeight = null, $format = 'webp', $quality = 82) {
    if (!file_exists($srcPath)) {
        return false;
    }

    $info = getimagesize($srcPath);
    if (!$info) return false;

    $origW = $info[0];
    $origH = $info[1];
    $mime = $info['mime'];

    // Calculate dimensions keeping aspect ratio
    if ($targetHeight === null) {
        $ratio = $targetWidth / $origW;
        $targetHeight = (int)round($origH * $ratio);
    } elseif ($targetWidth === null) {
        $ratio = $targetHeight / $origH;
        $targetWidth = (int)round($origW * $ratio);
    } else {
        // Fit within bounding box
        $ratio = min($targetWidth / $origW, $targetHeight / $origH);
        $targetWidth = (int)round($origW * $ratio);
        $targetHeight = (int)round($origH * $ratio);
    }

    // Load source image
    switch ($mime) {
        case 'image/png':
            $srcImg = imagecreatefrompng($srcPath);
            break;
        case 'image/jpeg':
            $srcImg = imagecreatefromjpeg($srcPath);
            break;
        case 'image/webp':
            $srcImg = imagecreatefromwebp($srcPath);
            break;
        default:
            return false;
    }

    if (!$srcImg) return false;

    // Create target image canvas
    $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);

    // Handle alpha transparency for PNG / WebP / AVIF
    imagealphablending($dstImg, false);
    imagesavealpha($dstImg, true);
    $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
    imagefilledrectangle($dstImg, 0, 0, $targetWidth, $targetHeight, $transparent);

    // High quality resample
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $origW, $origH);

    // Save to requested format
    $success = false;
    if ($format === 'webp' && function_exists('imagewebp')) {
        $success = imagewebp($dstImg, $destPath, $quality);
    } elseif ($format === 'avif' && function_exists('imageavif')) {
        $success = imageavif($dstImg, $destPath, $quality);
    } elseif ($format === 'png') {
        // compression 0-9
        $pngQuality = 9;
        $success = imagepng($dstImg, $destPath, $pngQuality);
    } elseif ($format === 'jpg' || $format === 'jpeg') {
        $success = imagejpeg($dstImg, $destPath, $quality);
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);

    return $success;
}

// ----------------------------------------------------
// 1. HERO IMAGE (ELEMEN LCP)
// ----------------------------------------------------
$heroOrig = $baseDir . '/public/assets/img/heroatas.png';
if (file_exists($heroOrig)) {
    backupFile($heroOrig, $backupDir . '/heroatas.png');
    $origSize = filesize($heroOrig);

    // Responsive breakpoints:
    // Display is max 460x575 px (4:5)
    // Desktop 2x: 920w (920 x 1150)
    // Tablet 2x: 720w (720 x 900)
    // Mobile 2x: 640w (640 x 800)
    // Mobile 1x / low: 360w (360 x 450)
    $variants = [
        ['heroatas-920.webp', 920, 1150, 'webp', 82],
        ['heroatas-720.webp', 720, 900, 'webp', 82],
        ['heroatas-640.webp', 640, 800, 'webp', 80],
        ['heroatas-360.webp', 360, 450, 'webp', 80],
        ['heroatas.webp', 920, 1150, 'webp', 82], // default desktop webp
        ['heroatas-fallback.png', 920, 1150, 'png', 9], // optimized fallback png
    ];

    if ($hasAvif) {
        $variants[] = ['heroatas-920.avif', 920, 1150, 'avif', 75];
        $variants[] = ['heroatas-640.avif', 640, 800, 'avif', 75];
        $variants[] = ['heroatas-360.avif', 360, 450, 'avif', 75];
    }

    foreach ($variants as $v) {
        $dest = $baseDir . '/public/assets/img/' . $v[0];
        resizeAndSave($heroOrig, $dest, $v[1], $v[2], $v[3], $v[4]);
        if (file_exists($dest)) {
            $destSize = filesize($dest);
            echo "Hero variant generated: {$v[0]} ({$v[1]}x{$v[2]}) -> " . round($destSize / 1024, 1) . " KB\n";
        }
    }
}

// ----------------------------------------------------
// 2. MITRA INDUSTRI LOGOS
// ----------------------------------------------------
$mitraDir = $baseDir . '/public/assets/img/mitra_industri';
$mitraFiles = glob($mitraDir . '/*.{png,PNG,jpg,jpeg}', GLOB_BRACE);

foreach ($mitraFiles as $mFile) {
    $baseName = basename($mFile);
    backupFile($mFile, $backupDir . '/mitra_industri/' . $baseName);

    // Max display in CSS: max-width 140px, max-height 50px
    // 2x Retina: max-width 280px, max-height 100px
    $nameWithoutExt = pathinfo($baseName, PATHINFO_FILENAME);
    $webpDest = $mitraDir . '/' . $nameWithoutExt . '.webp';
    $pngDest = $mitraDir . '/' . $nameWithoutExt . '_opt.png';

    // Resize within 280x100 bounding box
    resizeAndSave($mFile, $webpDest, 280, 100, 'webp', 85);
    resizeAndSave($mFile, $pngDest, 280, 100, 'png', 9);

    // Also overwrite the original file with optimized version to immediately speed up any code using original path!
    resizeAndSave($mFile, $mFile, 280, 100, strtolower(pathinfo($baseName, PATHINFO_EXTENSION)) === 'jpg' ? 'jpg' : 'png', 85);

    echo "Mitra optimized: $baseName (" . round(filesize($mFile)/1024, 1) . " KB) | WebP: " . (file_exists($webpDest) ? round(filesize($webpDest)/1024, 1) . " KB" : "N/A") . "\n";
}

// ----------------------------------------------------
// 3. VISI & MISI LOGOS
// ----------------------------------------------------
$visiMisi = [
    'visi_logo_1789642529.png' => [320, 320],
    'misi_logo_1789642529.png' => [300, 350],
];
foreach ($visiMisi as $vmFile => $dim) {
    $src = $baseDir . '/public/assets/img/' . $vmFile;
    if (file_exists($src)) {
        backupFile($src, $backupDir . '/' . $vmFile);
        $destWebp = $baseDir . '/public/assets/img/' . pathinfo($vmFile, PATHINFO_FILENAME) . '.webp';
        resizeAndSave($src, $destWebp, $dim[0], $dim[1], 'webp', 85);
        resizeAndSave($src, $src, $dim[0], $dim[1], 'png', 9);
        echo "Visi/Misi optimized: $vmFile (" . round(filesize($src)/1024, 1) . " KB) | WebP: " . (file_exists($destWebp) ? round(filesize($destWebp)/1024, 1) . " KB" : "N/A") . "\n";
    }
}

// ----------------------------------------------------
// 4. FAVICON OPTIMIZATION
// ----------------------------------------------------
$favFile = $baseDir . '/public/assets/img/favicon_1786452697.png';
if (file_exists($favFile)) {
    backupFile($favFile, $backupDir . '/favicon_1786452697.png');
    // Favicon should be 64x64 or 128x128
    resizeAndSave($favFile, $favFile, 128, 128, 'png', 9);
    echo "Favicon optimized: " . round(filesize($favFile)/1024, 1) . " KB\n";
}

// ----------------------------------------------------
// 5. AMBASSADORS / BRAND IMAGES
// ----------------------------------------------------
$ambassadors = [
    'brand_ambassador.jpg' => [800, null],
    'smk_pelita_ambassadors.jpg' => [800, null],
];
foreach ($ambassadors as $aFile => $dims) {
    $src = $baseDir . '/public/assets/img/' . $aFile;
    if (file_exists($src)) {
        backupFile($src, $backupDir . '/' . $aFile);
        $destWebp = $baseDir . '/public/assets/img/' . pathinfo($aFile, PATHINFO_FILENAME) . '.webp';
        resizeAndSave($src, $destWebp, $dims[0], $dims[1], 'webp', 82);
        resizeAndSave($src, $src, $dims[0], $dims[1], 'jpg', 82);
        echo "Ambassador optimized: $aFile (" . round(filesize($src)/1024, 1) . " KB) | WebP: " . (file_exists($destWebp) ? round(filesize($destWebp)/1024, 1) . " KB" : "N/A") . "\n";
    }
}

// ----------------------------------------------------
// 6. CATEGORY ICONS (DOWNLOAD & LOCAL OPTIMIZE)
// ----------------------------------------------------
$catDir = $baseDir . '/public/assets/img/categories';
if (!is_dir($catDir)) {
    mkdir($catDir, 0755, true);
}

$categories = [
    'source-code' => 'https://cdn-icons-png.flaticon.com/512/1005/1005141.png',
    'spotify-netflix' => 'https://cdn-icons-png.flaticon.com/512/3670/3670157.png',
    'desain-grafis' => 'https://cdn-icons-png.flaticon.com/512/2970/2970785.png',
    'dokumen' => 'https://cdn-icons-png.flaticon.com/512/2991/2991106.png',
    'ebook' => 'https://cdn-icons-png.flaticon.com/512/2232/2232688.png',
    'creator' => 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png',
];

foreach ($categories as $slug => $url) {
    $tmpFile = $catDir . '/' . $slug . '_tmp.png';
    $destWebp = $catDir . '/' . $slug . '.webp';
    $destPng = $catDir . '/' . $slug . '.png';

    // Download icon if not exists
    if (!file_exists($destWebp) || !file_exists($destPng)) {
        $content = @file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']
        ]));

        if ($content) {
            file_put_contents($tmpFile, $content);
            resizeAndSave($tmpFile, $destWebp, 72, 72, 'webp', 85);
            resizeAndSave($tmpFile, $destPng, 72, 72, 'png', 9);
            @unlink($tmpFile);
            echo "Category icon created: $slug (WebP: " . round(filesize($destWebp)/1024, 1) . " KB, PNG: " . round(filesize($destPng)/1024, 1) . " KB)\n";
        } else {
            echo "Failed to download category icon $slug from $url\n";
        }
    } else {
        echo "Category icon already exists: $slug\n";
    }
}

echo "\nOPTIMIZATION COMPLETE!\n";
