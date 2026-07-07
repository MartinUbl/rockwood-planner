<?php declare(strict_types=1);

$size = 64;
$image = imagecreatetruecolor($size, $size);
imagesavealpha($image, true);

$bg = imagecolorallocate($image, 16, 17, 20);
$panel = imagecolorallocate($image, 24, 26, 32);
$green = imagecolorallocate($image, 32, 201, 151);
$text = imagecolorallocate($image, 244, 247, 251);
$coral = imagecolorallocate($image, 255, 107, 107);

imagefilledrectangle($image, 0, 0, $size, $size, $bg);
imagefilledrectangle($image, 5, 5, 58, 58, $panel);

imagefilledrectangle($image, 14, 13, 26, 51, $green);
imagefilledrectangle($image, 14, 13, 42, 24, $green);
imagefilledrectangle($image, 14, 25, 42, 35, $green);
imagefilledrectangle($image, 39, 17, 50, 31, $green);
imagefilledrectangle($image, 27, 21, 39, 27, $bg);

imagesetthickness($image, 6);
imageline($image, 36, 43, 43, 50, $text);
imageline($image, 43, 50, 55, 34, $text);

imagesetthickness($image, 4);
imageline($image, 10, 55, 28, 55, $coral);

ob_start();
imagepng($image);
$png = (string) ob_get_clean();
imagedestroy($image);

$icoHeader = pack('vvv', 0, 1, 1);
$directory = pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), 6 + 16);
file_put_contents(__DIR__ . '/../www/favicon.ico', $icoHeader . $directory . $png);
