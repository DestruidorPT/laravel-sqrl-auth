<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use DestruidorPT\LaravelSQRLAuth\App\Sqrl_nonce;
use DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey;

use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\CodesSQRL;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\Base64Url;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\SignaturesSQRLValidator;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\SQRLRequest;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\SQRLResponse;

use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToManageDatabase\SqrlNonceController;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToManageDatabase\SqrlPubKeyController;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToAuth\AuthSQRLController;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToQuestion\QuestionSQRLController;

class SQRLController extends Controller
{
    /**
     * Checks if nonce is valid, if nonce is valid the next page url 
     * or user response to a question is returned.
     * 
     * @param string
     * 
     * @return Array
     */ 
    public static function checkIfisReady($nut) 
    {
        if(isset($nut) && !empty($nut)) {
            $sqrl_nonce = SqrlNonceController::getSQRLNonceIfValidByOriginalNonce($nut);
            
            if(!isset($sqrl_nonce)) {
                return ['isReady'=>false, 'msg' => 'Time out, reload nonce!'];
            }
            if($sqrl_nonce->ip_address !== \Request::ip()) {
                return ['isReady'=>false,  'msg' => 'IP Doesnt Match!'];
            }
            if($sqrl_nonce->verified != 1) {
                return ['isReady'=>false,  'msg' => 'Not Ready!'];
            }
            if(isset($sqrl_nonce->sqrl_pubkey_id) && isset($sqrl_nonce->sqrl_pubkey) && $sqrl_nonce->sqrl_pubkey->disabled == 1) {
                return ['isReady'=>false,  'msg' => 'SQRL is disable for this user!'];
            }
            if($sqrl_nonce->type === "auth") {
                return ['isReady'=>true,  'msg' => 'Can be authenticated!', 'nextPage' => Base64Url::base64UrlDecode($sqrl_nonce->url)];
            } else if($sqrl_nonce->type === "question") {
                if(isset($sqrl_nonce->btn_answer)) {
                    $question = SqrlNonceController::getTheDataFromQuestion($sqrl_nonce);
                    if($sqrl_nonce->btn_answer == 0) {
                        return [
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "OK" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => isset($sqrl_nonce->url) && !empty($sqrl_nonce->url)?Base64Url::base64UrlDecode($sqrl_nonce->url):''
                        ];
                    } else if($sqrl_nonce->btn_answer == 1) {
                        return [
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "'.$question["btn1"].'" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => isset($question["url1"]) && !empty($question["url1"])?$question["url1"]:''
                        ];
                    }  else if($sqrl_nonce->btn_answer == 2) {
                        return [
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "'.$question["btn2"].'" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => isset($question["url2"]) && !empty($question["url2"])?$question["url2"]:''
                        ];
                    } else {
                        return [
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is '.$sqrl_nonce->btn_answer.'!',
                            'nextPage' => ''
                        ];
                    }
                }
                return ['isReady'=>true, 'btn' => $sqrl_nonce->btn_answer,  'msg' => 'Button is invalid!', 'nextPage' => ''];
            }
        }
        return null;
    }

    /**
     * Checks if user can do a normal login or if is only allowed
     * to login by SQRL Authentication
     * 
     * @param int user_id
     * 
     * @return boolean
     */ 
    public static function checkIfUserCanAuthByNormalLogin($user_id)
    {
        if(isset($user_id) && $user_id > 0){
            $sqrl_pubkey = Sqrl_pubkey::where('user_id', '=', $user_id)->where("disabled", "=", 0)->orderBy("created_at", "desc")->first();
            if(isset($sqrl_pubkey) && $sqrl_pubkey->sqrl_only_allowed == 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if user can do a recover password
     * 
     * @param int user_id
     * 
     * @return boolean
     */ 
    public static function checkIfUserCanUseRecoverPassword($user_id)
    {
        if(isset($user_id) && $user_id > 0){
            $sqrl_pubkey = Sqrl_pubkey::where('user_id', '=', $user_id)->where("disabled", "=", 0)->orderBy("created_at", "desc")->first();
            if(isset($sqrl_pubkey) && $sqrl_pubkey->hardlock == 1) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Checks if user have SQRL enable
     * 
     * @param int user_id
     * 
     * @return boolean
     */ 
    public static function checkIfUserCanAuthBySQRL($user_id)
    {
        if(isset($user_id)){
            $sqrl_pubkey = Sqrl_pubkey::where('user_id', '=', $user_id)->where("disabled", "=", 0)->orderBy("created_at", "desc")->first();
            if(isset($sqrl_pubkey)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check and get the user id if is authenticated, but if is new user the function return PubKey 
     * and if is not authenticated or nonce is not valid then return 404 
     * 
     * @param string original_nonce
     * 
     * @return int user_id
     * @return Sqrl_pubkey
     */ 
    public static function getUserByOriginalNonceIfCanBeAuthenticated($orig_nonce) 
    {
        $sqrl_nonce = SqrlNonceController::getSQRLNonceIfValidByOriginalNonce($orig_nonce);
        if(isset($sqrl_nonce) 
            && $sqrl_nonce->verified == 1 
                && $sqrl_nonce->ip_address === \Request::ip() 
                    && isset($sqrl_nonce->sqrl_pubkey_id) 
                        && isset($sqrl_nonce->sqrl_pubkey) 
                            && $sqrl_nonce->sqrl_pubkey->disabled == 0) {
            $sqrl_pubkey = $sqrl_nonce->sqrl_pubkey;
            if(isset($sqrl_pubkey->user_id)) {
                return $sqrl_pubkey->user_id;
            } else if(isset($sqrl_pubkey)) {
                return $sqrl_pubkey;
            }
        }
        return null;
    }

    /**
     * Generate a nonce for authentication purpose
     * 
     * @return array
     */ 
    public static function getNewAuthNonce() 
    {
        $sqrl_nonce = SqrlNonceController::gerateUniqueSQRLNonceForAuth("", env('SQRL_URL_LOGIN', 'https://sqrl.test/login'));
        $sqrl_nonce->url = Base64Url::base64UrlEncode(env('SQRL_URL_LOGIN', 'https://sqrl.test/login')."?nut=".$sqrl_nonce->nonce);
        $sqrl_nonce->save();
        $route = env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce;
        $url_login_sqrl = "sqrl://".env('SQRL_KEY_DOMAIN', 'sqrl.test').$route;
        return [
            'nonce' => $sqrl_nonce->nonce,
            'check_state_on' => $route,
            'url_login_sqrl' => $url_login_sqrl,
            'encoded_url_login_sqrl' => Base64Url::base64UrlEncode($url_login_sqrl."&can=".$sqrl_nonce->can)
        ];
    }

    /**
     * Generate a nonce for question purpose
     * 
     * @param string $url
     * @param string $can
     * @param string $question
     * @param string $btn1
     * @param string $url1
     * @param string $btn2
     * @param string $url2
     * 
     * @return array
     */ 
    public static function getNewQuestionNonce($url, $can, $question, $btn1 = null, $url1 = null, $btn2 = null, $url2 = null) 
    {
        $sqrl_nonce = SqrlNonceController::gerateUniqueSQRLNonceForQuestion($url, $can, $question, $btn1, $url1, $btn2, $url2);
        $route = env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce;
        $url_question_sqrl = "sqrl://".env('SQRL_KEY_DOMAIN', 'sqrl.test').$route;
        return [
            'nonce' => $sqrl_nonce->nonce,
            'check_state_on' => $route,
            'url_question_sqrl' => $url_question_sqrl,
            'encoded_url_question_sqrl' => Base64Url::base64UrlEncode($url_question_sqrl."&can=".$sqrl_nonce->can)
        ];
    }
}
