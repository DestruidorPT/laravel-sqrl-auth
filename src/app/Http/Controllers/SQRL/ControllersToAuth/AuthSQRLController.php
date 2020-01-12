<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\ControllersToAuth;

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

class AuthSQRLController extends Controller
{
    public static function processRequestSQRL($client_decode, $server_decode, $sqrl_nonce, $old_nonce, $tif, &$log_register)
    {
        $log_register .= ("SQRL-Start processing request SQRL\n");
        $response = "";
        switch($client_decode["cmd"]) {
            case "query":
                $log_register .= ("SQRL-Processing query!\n");
                $response = AuthSQRLController::processSQRLforQuery($client_decode, $server_decode, $sqrl_nonce, $tif, $log_register);
                break;
            case "ident":
                $log_register .= ("SQRL-Processing ident!\n");
                $response = AuthSQRLController::processSQRLforIdent($client_decode, $server_decode, $sqrl_nonce, $tif, $log_register);
                break;
            case "disable":
                $log_register .= ("SQRL-Processing disable!\n");
                $response = AuthSQRLController::processSQRLforDisable($client_decode, $server_decode, $sqrl_nonce, $tif, $log_register);
                break;
            case "enable":
                $log_register .= ("SQRL-Processing enable!\n");
                $response = AuthSQRLController::processSQRLforEnable($client_decode, $server_decode, $sqrl_nonce, $tif, $log_register);
                break;
            case "remove":
                $log_register .= ("SQRL-Processing remove!\n");
                $response = AuthSQRLController::processSQRLforRemove($client_decode, $server_decode, $sqrl_nonce, $tif, $log_register);
                break;
            default:
                $log_register .= ("SQRL-Cmd unknown!\n");
                $response = SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("FUNCTION_NOT_SUPPORTED"), $log_register);
                break;
        }
        $log_register .= ("SQRL-End processing request SQRL\n");
        return $response;
    }

    public static function processSQRLforQuery($client_decode, $server_decode, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(isset($sqrl_pubkey)) {
            $sqrl_nonce->sqrl_pubkey_id = $sqrl_pubkey->id;
            $sqrl_nonce->save();
            $suk = ((in_array("suk", $client_decode["opt"]) || $sqrl_pubkey->disabled == 1)?$sqrl_pubkey->suk:"");
            $sqrl_pubkey = AuthSQRLController::processSQRLExtraOptions($client_decode["opt"], $sqrl_pubkey, $log_register);
            if($sqrl_pubkey->disabled == 1) {
                return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("SQRL_DISABLED"), $log_register);
            }
            return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, 
                                                $tif+CodesSQRL::getTifCode("ID_MATCH"), 
                                                    env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce,
                                                        "", "", 0, $suk, "", $log_register);
        }

        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, $tif, env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, "", "", 0, "", "", $log_register);
    }

    public static function processSQRLforIdent($client_decode, $server_decode, $sqrl_nonce, $tif, &$log_register)
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
            $tif |= CodesSQRL::getTifCode("ID_MATCH");
        }

        if(!isset($sqrl_nonce->sqrl_pubkey_id) || $sqrl_nonce->sqrl_pubkey_id != $sqrl_pubkey->id) {
            $sqrl_nonce->sqrl_pubkey_id = $sqrl_pubkey->id;
        }
        
        $suk = ((in_array("suk", $client_decode["opt"]) || $sqrl_pubkey->disabled == 1)?$sqrl_pubkey->suk:"");
        if($sqrl_pubkey->disabled == 1) {
            return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("SQRL_DISABLED"), $log_register);
        }
        
        $sqrl_nonce->verified = 1;
        $sqrl_nonce->save();

        $suk = (in_array("suk", $client_decode["opt"])?$sqrl_pubkey->suk:"");
        $url = (in_array("cps", $client_decode["opt"])?Base64Url::base64UrlDecode($sqrl_nonce->url):"");
        $sqrl_pubkey = AuthSQRLController::processSQRLExtraOptions($client_decode["opt"], $sqrl_pubkey, $log_register);
        
        
        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, 
                                            $tif, 
                                                env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, 
                                                    $url,
                                                        "", 0, $suk, "", $log_register);
    }

    public static function processSQRLforDisable($client_decode, $server_decode, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(!isset($sqrl_pubkey)) {
            return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
        }
        $tif |= CodesSQRL::getTifCode("ID_MATCH");
        
        $suk = ((in_array("suk", $client_decode["opt"]) || $sqrl_pubkey->disabled == 1)?$sqrl_pubkey->suk:"");
        if($sqrl_pubkey->disabled == 1) {
            return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("SQRL_DISABLED"), $log_register);
        }


        $sqrl_pubkey->disabled = 1;
        $sqrl_pubkey->save();
        $sqrl_pubkey = AuthSQRLController::processSQRLExtraOptions($client_decode["opt"], $sqrl_pubkey, $log_register);

        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, $tif, env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, "", "", 0, "", "", $log_register);
    }

    public static function processSQRLforEnable($client_decode, $server_decode, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(!isset($sqrl_pubkey)) {
            return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
        }
        $tif |= CodesSQRL::getTifCode("ID_MATCH");

        $suk = (in_array("suk", $client_decode["opt"])?$sqrl_pubkey->suk:"");
        
        $sqrl_pubkey->disabled = 0;
        $sqrl_pubkey->save();
        $sqrl_pubkey = AuthSQRLController::processSQRLExtraOptions($client_decode["opt"], $sqrl_pubkey, $log_register);

        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, $tif, env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, "", "", 0, "", "", $log_register);
    }

    public static function processSQRLforRemove($client_decode, $server_decode, $sqrl_nonce, $tif, &$log_register)
    {
        $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($client_decode["idk"]);
        if(!isset($sqrl_pubkey)) {
            return SQRLResponse::createResponseSQRLforProblem($client_decode, $server_decode, $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
        }
        $tif |= CodesSQRL::getTifCode("ID_MATCH");

        $suk = (in_array("suk", $client_decode["opt"])?$sqrl_pubkey->suk:"");
        $url = (in_array("cps", $client_decode["opt"])?Base64Url::base64UrlDecode($sqrl_nonce->url):"");

        $sqrl_pubkey->delete();
        
        return SQRLResponse::createResponseSQRL($sqrl_nonce->nonce, 
                                            $tif, 
                                                env('SQRL_ROUTE_TO_SQRL_AUTH', '/api/sqrl')."?nut=".$sqrl_nonce->nonce, 
                                                    $url,
                                                        "", 0, $suk, "", $log_register);
    }

    public static function processSQRLExtraOptions($opts, $sqrl_pubkey, &$log_register)
    {
        if(isset($opts) && isset($sqrl_pubkey)) {
            $update = false;
            if(in_array("sqrlonly", $opts) && $sqrl_pubkey->sqrl_only_allowed != 1) {
                $log_register .= ("SQRL-Ativated SQRL Auth Only Allowed!".$sqrl_pubkey->sqrl_only_allowed."\n");
                $sqrl_pubkey->sqrl_only_allowed = 1;
                $update = true;
            } else if(!in_array("sqrlonly", $opts) && $sqrl_pubkey->sqrl_only_allowed != 0) {
                $log_register .= ("SQRL-Removing SQRL Auth Only Allowed!".$sqrl_pubkey->sqrl_only_allowed."\n");
                $sqrl_pubkey->sqrl_only_allowed = 0;
                $update = true;
            }
            if(in_array("hardlock", $opts) && $sqrl_pubkey->hardlock != 1) {
                $log_register .= ("SQRL-Ativated SQRL Hardlock!".$sqrl_pubkey->hardlock."\n");
                $sqrl_pubkey->hardlock = 1;
                $update = true;
            } else if(!in_array("hardlock", $opts) && $sqrl_pubkey->hardlock != 0) {
                $log_register .= ("SQRL-Removing SQRL Hardlock!".$sqrl_pubkey->hardlock."\n");
                $sqrl_pubkey->hardlock = 0;
                $update = true;
            }
            if($update) {
                $sqrl_pubkey->save();
            }
        }
        return $sqrl_pubkey;
    }
}
