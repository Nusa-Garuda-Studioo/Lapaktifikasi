<?php

$files = [
    "public/assets/img/heroatas.png",
    "public/assets/img/brand_ambassador.jpg",
    "public/assets/img/smk_pelita_ambassadors.jpg",
    "public/assets/img/logo_1786452942.png",
    "public/assets/img/favicon.png",
    "public/assets/img/favicon_1786452697.png",
    "public/assets/img/visi_kami.svg",
    "public/assets/img/misi_utama.svg",
    "public/assets/img/visi_logo_1789642529.png",
    "public/assets/img/misi_logo_1789642529.png",
    "public/assets/img/mitra_industri/1786451691_Logo SMK Plus Pelita Nusantara (3).png",
    "public/assets/img/mitra_industri/1786451710_Logo YPNB transparan Stroke (2).png",
    "public/assets/img/mitra_industri/1786451731_Logo Divisi  RPL (2).png",
    "public/assets/img/mitra_industri/1786451765_IMG_6489.PNG",
    "public/assets/img/mitra_industri/1786451795_ChatGPT Image Jun 13, 2026, 01_09_55 PM (2).png",
    "public/assets/img/mitra_industri/1786454191_Black White Illustrated Flat Minimalist Coffee House Logo (250 x 70 piksel).png",
];

echo "PATH | SIZE_KB | WIDTH x HEIGHT | MIME\n";
echo str_repeat("-", 80) . "\n";

foreach ($files as $f) {
    if (file_exists($f)) {
        $size = filesize($f);
        $dim = @getimagesize($f);
        $w = $dim ? $dim[0] : "SVG/NA";
        $h = $dim ? $dim[1] : "SVG/NA";
        $mime = $dim ? $dim["mime"] : (function_exists('mime_content_type') ? mime_content_type($f) : 'image/svg+xml');
        printf("%s | %d KB | %s x %s | %s\n", $f, round($size/1024), $w, $h, $mime);
    } else {
        echo "$f | NOT FOUND\n";
    }
}
