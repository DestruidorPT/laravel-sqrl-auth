<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SQRLResponse extends Controller
{
    public static function createResponseSQRL($nonce, $tif, $query, $url = "", $can = "", $sin = 0, $suk = "", $ask = "", &$log_register)
    {
        $response = "ver=1\r\n"
                    ."nut=".$nonce."\r\n"
                    ."tif=".strtoupper(dechex($tif))."\r\n"
                    ."qry=".$query."\r\n"
                    .((isset($url) && !empty($url))?("url=".$url."\r\n"):"")
                    .((isset($can) && !empty($can))?("can=".$can."\r\n"):"")
                    .((isset($sin) && !empty($sin))?("sin=".$sin."\r\n"):"sin=0\r\n")
                    .((isset($suk) && !empty($suk))?("suk=".Base64Url::base64UrlEncode($suk)."\r\n"):"")
                    .((isset($ask) && !empty($ask))?("ask=".$ask."\r\n"):"");
        $response = rtrim($response,"\n");
        $log_register .= ("SQRL-Response:{\n\t".(str_replace ("\r\n", "\r\n\t", $response))."\n}\n");
        $response = Base64Url::base64UrlEncode($response);
        $log_register .= ("SQRL-Response Encoded: ".$response."\n");
        return $response;
    }
    
    public static function createResponseSQRLforProblem($client_decode, $server_decode, $nonce, $tif, &$log_register)
    {
        return SQRLResponse::createResponseSQRL($nonce, $tif, env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$nonce, "", "", 0, "", "", $log_register);
    }

    public static function createReponseAndAddHeaders($response, &$log_register)
    {
        $log_register .= ("\n------------------------------------------\n\n");
        Log::channel('LaravelSQRLAuth')->info($log_register);
        $log_register = "";
        $response = response($response, 200);
        return $response->header('Content-Length',strlen($response->getOriginalContent()))->header('Content-Type', 'application/x-www-form-urlencoded')->header('User-Agent', 'SQRL Server')->header('host', env('SQRL_KEY_DOMAIN', 'sqrl.test'));
    }
}