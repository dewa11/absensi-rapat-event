<?php

declare(strict_types=1);

namespace app\helpers;

class CaptchaHelper
{
    public static function generateCode(string $sessionKey = 'captcha_code'): string
    {
        SessionHelper::start();
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        SessionHelper::set($sessionKey, $code);
        return $code;
    }

    public static function validate(string $input, string $sessionKey = 'captcha_code'): bool
    {
        $stored = (string) SessionHelper::get($sessionKey, '');
        SessionHelper::remove($sessionKey);
        return hash_equals($stored, trim($input));
    }

    public static function renderImage(string $code): array
    {
        if (function_exists('imagecreatetruecolor')) {
            return self::renderPng($code);
        }

        return self::renderSvg($code);
    }

    private static function renderPng(string $code): array
    {
        $width = 150;
        $height = 52;

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return self::renderSvg($code);
        }

        $bg = imagecolorallocate($image, 232, 240, 255);
        $royalBlue = imagecolorallocate($image, 36, 77, 187);
        $line = imagecolorallocate($image, 130, 155, 230);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 6; $i++) {
            imageline(
                $image,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $line
            );
        }

        imagestring($image, 5, 28, 18, $code, $royalBlue);

        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return [
            'mime' => 'image/png',
            'content' => $data,
        ];
    }

    private static function renderSvg(string $code): array
    {
        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="52" viewBox="0 0 150 52">'
            . '<rect width="150" height="52" fill="#e8f0ff"/>'
            . '<text x="75" y="34" text-anchor="middle" fill="#244dbb" font-size="26" font-family="Arial, sans-serif" letter-spacing="5">'
            . $safeCode
            . '</text>'
            . '</svg>';

        return [
            'mime' => 'image/svg+xml',
            'content' => $svg,
        ];
    }
}
