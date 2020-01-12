<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToManageDatabase;

use Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

use DestruidorPT\LaravelSQRLAuth\App\Sqrl_nonce;
use DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey;

use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\Base64Url;


class SqrlNonceController extends Controller
{
    public static function gerateUniqueNonce($string) 
    {
        $nonce_salt = env('SQRL_NONCE_SALT', 'Default_Salt');
        $nonce = null;
        do {
            $nonce = hash_hmac('sha256', uniqid($string, true), $nonce_salt);
        } while(isset($nonce) && Sqrl_nonce::where('nonce', '=', $nonce)->count() > 0);

        return $nonce;
    }

    public static function gerateUniqueSQRLNonceForAuth($url, $can) 
    {
        $client_ip_address = Request::ip();
        $nonce = SqrlNonceController::gerateUniqueNonce($client_ip_address."auth");

        return Sqrl_nonce::create([
            "nonce" => $nonce,
            "ip_address" => $client_ip_address,
            "type" => "auth",
            "orig_nonce" => $nonce,
            "url" => Base64Url::base64UrlEncode($url),
            "can" => Base64Url::base64UrlEncode($can),
        ]);
    }

    public static function gerateUniqueSQRLNonceForQuestion($url, $can, $question, $btn1 = null, $url1 = null, $btn2 = null, $url2 = null) 
    {
        $client_ip_address = Request::ip();
        $nonce = SqrlNonceController::gerateUniqueNonce($client_ip_address."question");
        $question_encoded = Base64Url::base64UrlEncode($question);
        $btn1_encoded = (isset($btn1)?("~".Base64Url::base64UrlEncode($btn1.(isset($url1)?";".$url1:""))):"");
        $btn2_encoded = (isset($btn2)?("~".Base64Url::base64UrlEncode($btn2.(isset($url2)?";".$url2:""))):"");

        return Sqrl_nonce::create([
            "nonce" => $nonce,
            "ip_address" => $client_ip_address,
            "type" => "question",
            "orig_nonce" => $nonce,
            "question" => $question_encoded.$btn1_encoded.$btn2_encoded,
            "url" => Base64Url::base64UrlEncode($url),
            "can" => Base64Url::base64UrlEncode($can),
        ]);
    }

    public static function getTheDataFromQuestion($sqrl_nonce) 
    {
        if(isset($sqrl_nonce)) {
            $array_question = explode('~',$sqrl_nonce->question);
            $question = count($array_question) >= 1 ? Base64Url::base64UrlDecode($array_question[0]) : '';
            $help = "";
            $btn1 = "";
            $url1 = "";
            $btn2 = "";
            $url2 = "";
            if(count($array_question) >= 2) {
                $help = explode(';',Base64Url::base64UrlDecode($array_question[1]));
                $btn1 = count($help) >= 1 ? $help[0] : '';
                $url1 = count($help) >= 2 ? $help[1] : '';
                if(count($array_question) >= 3) {
                    $help = explode(';',Base64Url::base64UrlDecode($array_question[2]));
                    $btn2 = count($help) >= 1 ? $help[0] : '';
                    $url2 = count($help) >= 2 ? $help[1] : '';
                }
            }
            return [
                "question" => $question,
                "btn1" => $btn1,
                "url1" => $url1,
                "btn2" => $btn2,
                "url2" => $url2,
            ];
        }
        return null;
    }

    public static function getSQRLNonceIfValid($nonce) 
    {
        $sqrl_nonce = Sqrl_nonce::where('nonce', '=', $nonce)->first();
        if(!isset($sqrl_nonce) || $sqrl_nonce->created_at->diffInMinutes(Carbon::now()) > env('SQRL_NONCE_MAX_AGE_MINUTES', 5)) {
            if(isset($sqrl_nonce)) {
                $sqrl_nonce->delete();
            }
            return null;
        }
        return $sqrl_nonce;
    }

    public static function getSQRLNonceIfValidByOriginalNonce($orig_nonce) 
    {
        $sqrl_nonce = Sqrl_nonce::where('orig_nonce', '=', $orig_nonce)->first();
        if(!isset($sqrl_nonce) || $sqrl_nonce->created_at->diffInMinutes(Carbon::now()) > env('SQRL_NONCE_MAX_AGE_MINUTES', 5)) {
            if(isset($sqrl_nonce)) {
                $sqrl_nonce->delete();
            }
            return null;
        }
        return $sqrl_nonce;
    }

    public static function getSQRLNonceByOriginalNonce($orig_nonce) 
    {
        return Sqrl_nonce::where('orig_nonce', '=', $orig_nonce)->first();
    }

    public static function getSQRLNonceIfValidWithNewNonce($nonce) 
    {
        $sqrl_nonce = SqrlNonceController::getSQRLNonceIfValid($nonce);
        if(isset($sqrl_nonce)) {
            $sqrl_nonce->nonce = SqrlNonceController::gerateUniqueNonce(Request::ip().$sqrl_nonce->id.$sqrl_nonce->type);
            $sqrl_nonce->save();
        }

        return $sqrl_nonce;
    }

    
}
