<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToManageDatabase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DestruidorPT\LaravelSQRLAuth\App\Sqrl_nounce;
use DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey;

use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\Base64Url;

class SqrlPubKeyController extends Controller
{
    public static function checkIfExistIdentityKey($public_key) 
    {
        return Sqrl_pubkey::where('public_key', '=', Base64Url::base64UrlEncode($public_key))->first();
    }

    public static function createSqrlPubKey($public_key, $suk, $vuk) 
    {
        return Sqrl_pubkey::create([
            "public_key" => Base64Url::base64UrlEncode($public_key),
            "suk" => Base64Url::base64UrlEncode($suk),
            "vuk" => Base64Url::base64UrlEncode($vuk)
        ]);
    }

    public static function lockIdentityPubKey($public_key) 
    {
        $sqrl_pubkey = Sqrl_pubkey::where('public_key', '=', Base64Url::base64UrlEncode($public_key))->first();
        return $sqrl_pubkey->update(["disabled" => 1]);
    }
    public static function unlockIdentityPubKey($public_key) 
    {
        $sqrl_pubkey = Sqrl_pubkey::where('public_key', '=', Base64Url::base64UrlEncode($public_key))->first();
        return $sqrl_pubkey->update(["disabled" => 0]);
    }

    public static function updateIdentityKey($old_public_key, $new_public_key, $new_suk, $new_vuk) 
    {
        $sqrl_pubkey = Sqrl_pubkey::where('public_key', '=', Base64Url::base64UrlEncode($old_public_key))->first();
        return $pubkeys->update([
            "public_key" => Base64Url::base64UrlEncode($new_public_key),
            "suk" => Base64Url::base64UrlEncode($new_suk),
            "vuk" => Base64Url::base64UrlEncode($new_vuk)
        ]);
    }

}
