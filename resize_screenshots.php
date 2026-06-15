<?php

/**
 * Script hỗ trợ resize ảnh chụp màn hình đúng chuẩn App Store (6.5" và 6.7")
 * Cách dùng: Đặt ảnh gốc vào cùng thư mục, đặt tên là 'source.png' và chạy: php resize_screenshots.php
 *
 * LƯU Ý QUAN TRỌNG:
 * - Để tránh ảnh bị méo, hãy đảm bảo ảnh gốc (source.png) có tỷ lệ khung hình (aspect ratio) tương tự với kích thước mục tiêu.
 * - Tốt nhất là chụp ảnh màn hình trực tiếp từ Simulator với đúng kích thước yêu cầu.
 */

$sourceFile = 'source.png'; // Tên tệp ảnh gốc của bạn
$outputDir = __DIR__ . '/app_store_assets';

if (!file_exists($sourceFile)) {
    die("Lỗi: Không tìm thấy tệp $sourceFile. Hãy chuẩn bị ảnh gốc.\n");
}

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Danh sách các kích thước Apple yêu cầu
$targets = [
    ['w' => 1242, 'h' => 2688, 'name' => 'iphone_6.5_inch_portrait.png'],  // iPhone 6.5 inch (dọc)
    ['w' => 2688, 'h' => 1242, 'name' => 'iphone_6.5_inch_landscape.png'], // iPhone 6.5 inch (ngang)
    ['w' => 1284, 'h' => 2778, 'name' => 'iphone_6.7_inch_portrait.png'],  // iPhone 6.7 inch (dọc)
    ['w' => 2778, 'h' => 1284, 'name' => 'iphone_6.7_inch_landscape.png'], // iPhone 6.7 inch (ngang)
];

foreach ($targets as $target) {
    $info = getimagesize($sourceFile);
    $mime = $info['mime'];

    // Tạo resource từ ảnh gốc (hỗ trợ PNG và JPG)
    $source = ($mime == 'image/png') ? imagecreatefrompng($sourceFile) : imagecreatefromjpeg($sourceFile);
    
    $width = imagesx($source);
    $height = imagesy($source);

    // Tạo ảnh mới với kích thước mục tiêu
    $dest = imagecreatetruecolor($target['w'], $target['h']);
    
    // Giữ độ trong suốt nếu là ảnh PNG
    if ($mime == 'image/png') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
    }

    // Tiến hành resize
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $target['w'], $target['h'], $width, $height);
    
    // Lưu file
    imagepng($dest, $outputDir . '/' . $target['name']);
    
    imagedestroy($source);
    imagedestroy($dest);
    echo "Đã tạo thành công: " . $target['name'] . " (" . $target['w'] . "x" . $target['h'] . ")\n";
}