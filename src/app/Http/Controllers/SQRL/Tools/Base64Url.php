<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools;
use App\Http\Controllers\Controller;

class Base64Url extends Controller
{
    public static function base64UrlEncode(string $string): string
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $string): string
    {
        return base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', 1));
    }
}