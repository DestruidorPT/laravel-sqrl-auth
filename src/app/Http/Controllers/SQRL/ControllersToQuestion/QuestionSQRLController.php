<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToQuestion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use DestruidorPT\LaravelSQRLAuth\App\Sqrl_nonce;
use DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey;

use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\CodesSQRL;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\SQRLResponse;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools\Base64Url;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToManageDatabase\SqrlPubKeyController;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToAuth\AuthSQRLController;

class QuestionSQRLController extends Controller
{
    public static function processRequestSQRL($client_decode, $server_decode, $sqrl_nonce, $old_nonce, $tif, &$log_register)
    {
        $log_register .= ("SQRL-Start processing request SQRL\n");
        $response = "";
        switch($client_decode["cmd"]) {
            case "query":
                $log_register .= ("SQRL-Processing query!\n");
                $response = QuestionSQRLController::processSQRLforQuery($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, $log_register);
                break;
            case "ident":
                $log_register .= ("SQRL-Processing ident!\n");
                $response = QuestionSQRLController::processSQRLforIdent($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, $log_register);
                break;
            case "disable":
                $log_register .= ("SQRL-Processing disable!\n");
                $response = AuthSQRLController::processSQRLforDisable($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, $log_register);
                break;
            case "enable":
                $log_register .= ("SQRL-Processing enable!\n");
                $response = AuthSQRLController::processSQRLforEnable($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, $log_register);
                break;
            case "remove":
                $log_register .= ("SQRL-Processing remove!\n");
                $response = AuthSQRLController::processSQRLforRemove($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, $log_register);
                break;
            default:
                $log_register .= ("SQRL-Cmd unknown!\n");
                $response = SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("FUNCTION_NOT_SUPPORTED"), $log_register);
                break;
        }
        $log_register .= ("SQRL-End processing request SQRL\n");
        return $response;
    }

    public static function processSQRLforQuery($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(isset($sqrl_pubkey)) {
            $sqrl_nonce->sqrl_pubkey_id = $sqrl_pubkey->id;
            $sqrl_nonce->save();
            $suk = (in_array("suk", $client_decode["opt"])?$sqrl_pubkey->suk:"");
            return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, 
                                                $tif+CodesSQRL::getTifCode("ID_MATCH"), 
                                                    env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce,
                                                        "", "", 0, $suk, $sqrl_nonce->question, $log_register);
        }

        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, $tif, env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, "", "", 0, "", "", $log_register);
    }

    public static function processSQRLforIdent($client_decode, $server_decode, $old_nonce, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(!isset($sqrl_pubkey)) {
            if(!isset($client_decode["suk"]) && !isset($client_decode["vuk"])) {
                return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
            }
            $sqrl_pubkey = SqrlPubKeyController::createSqrlPubKey($client_decode["idk"], $client_decode["suk"], $client_decode["vuk"]);
            if(!isset($sqrl_pubkey)) {
                return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("TRANSIENT_ERROR"), $log_register);
            }
        } else {
            $tif += CodesSQRL::getTifCode("ID_MATCH");
        }
        if(!isset($sqrl_nonce->sqrl_pubkey_id) || $sqrl_nonce->sqrl_pubkey_id != $sqrl_pubkey->id) {
            $sqrl_nonce->sqrl_pubkey_id = $sqrl_pubkey->id;
        }
        $sqrl_nonce->verified = 1;
        $sqrl_nonce->btn_answer = ((isset($client_decode["btn"]) && !empty($client_decode["btn"]))?$client_decode["btn"]:null);
        $sqrl_nonce->save();

        $suk = (in_array("suk", $client_decode["opt"])?$sqrl_pubkey->suk:"");
        $url = (in_array("cps", $client_decode["opt"])?Base64Url::base64UrlDecode($sqrl_nonce->url):"");
        
        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, 
                                            $tif, 
                                                env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, 
                                                    $url,
                                                        "", 0, $suk, "", $log_register);
    }
}
