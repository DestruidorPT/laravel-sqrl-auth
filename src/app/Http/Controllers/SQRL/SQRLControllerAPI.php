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


use DestruidorPT\LaravelSQRLAuth\App\Http\Resources\SQRL\SqrlNonceResource;
use DestruidorPT\LaravelSQRLAuth\App\Http\Resources\SQRL\SqrlPubKeyResource;

class SQRLControllerAPI extends Controller
{
    /**
     * Checks if nonce is valid, if nonce is valid the next page url 
     * or user response to a question is returned.
     * 
     * @param $_GET["nut"]
     * 
     * @return ResponseJson
     */ 
    public static function checkIfisReady() 
    {
        if(isset($_GET["nut"]) && !empty($_GET["nut"])) {
            $sqrl_nonce = SqrlNonceController::getSQRLNonceIfValidByOriginalNonce($_GET["nut"]);
            
            if(!isset($sqrl_nonce)) {
                return response()->json(['isReady'=>false, 'msg' => 'Time out, reload nonce!'], 200);
            }
            if($sqrl_nonce->ip_address !== \Request::ip()) {
                return response()->json(['isReady'=>false,  'msg' => 'IP Doesnt Match!'], 200);
            }
            if($sqrl_nonce->verified != 1) {
                return response()->json(['isReady'=>false,  'msg' => 'Not Ready!'], 200);
            }
            if(isset($sqrl_nonce->sqrl_pubkey_id) && isset($sqrl_nonce->sqrl_pubkey) && $sqrl_nonce->sqrl_pubkey->disabled == 1) {
                return response()->json(['isReady'=>false,  'msg' => 'SQRL is disable for this user!'], 200);
            }
            if($sqrl_nonce->type === "auth") {
                return response()->json(['isReady'=>true,  'msg' => 'Can be authenticated!', 'nextPage' => Base64Url::base64UrlDecode($sqrl_nonce->url)], 200);
            } else if($sqrl_nonce->type === "question") {
                if(isset($sqrl_nonce->btn_answer)) {
                    $question = SqrlNonceController::getTheDataFromQuestion($sqrl_nonce);
                    if($sqrl_nonce->btn_answer == 0) {
                        return response()->json([
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "OK" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => (isset($sqrl_nonce->url) && !empty($sqrl_nonce->url)?Base64Url::base64UrlDecode($sqrl_nonce->url):'')
                        ], 200);
                    } else if($sqrl_nonce->btn_answer == 1) {
                        return response()->json([
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "'.$question["btn1"].'" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => (isset($question["url1"]) && !empty($question["url1"])?$question["url1"]:'')
                        ], 200);
                    }  else if($sqrl_nonce->btn_answer == 2) {
                        return response()->json([
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is "'.$question["btn2"].'" (button '.$sqrl_nonce->btn_answer.')!',
                            'nextPage' => (isset($question["url2"]) && !empty($question["url2"])?$question["url2"]:'')
                        ], 200);
                    } else {
                        return response()->json([
                            'isReady'=>true, 
                            'btn' => $sqrl_nonce->btn_answer,  
                            'msg' => 'The button selected is '.$sqrl_nonce->btn_answer.'!',
                            'nextPage' => ''
                        ], 200);
                    }
                }
                return ['isReady'=>true, 'btn' => 'Invalid',  'msg' => 'Button is invalid!'];
            }
        }
        return response()->json(['msg' => 'Not Found!'], 404);
    }

    /**
     * Checks if user have SQRL not disable
     * 
     * @param $_GET["nut"]
     * @param Request $request
     * 
     * @return ResponseJson
     */ 
    public static function sqrl(Request $request)
    {
        $log_register = "\n";
        $log_register .= ("------------".(Carbon::now())->toDateTimeString()."-------------\n");
        $request->request->add(['nut' => (isset($_GET["nut"])?$_GET["nut"]:null)]);
        $log_register .= ("SQRL-Nonce: ".(isset($_GET["nut"])?$_GET["nut"]:null)."\n");
        $log_register .= ("SQRL-Request: {\n\t".(str_replace ("&", "\n\t", $request->getContent()))."\n}\n");
        $validatedData = $request->validate([
            'client' => 'required|string',
            'server' => 'required|string',
            'ids' => 'required|string',
            'nut' => 'required|string|exists:sqrl_nonces,nonce',
            'urs' => 'string',
            'pids' => 'string'
        ]);
        $log_register .= ("SQRL-Dados Validos!\n");
        $decode_request = SQRLRequest::getDecodeAllData($validatedData, $log_register);

        $sqrl_nonce = SqrlNonceController::getSQRLNonceIfValidWithNewNonce($validatedData["nut"], $log_register);
        if(!isset($sqrl_nonce)) {
            $log_register .= ("SQRL-Nonce is invalid!\n");
            $response = SQRLResponse::createResponseSQRLforProblem($decode_request["client"], $decode_request["server"], $validatedData["nut"], CodesSQRL::getTifCode("CLIENT_FAILURE", $log_register));
            return SQRLResponse::createReponseAndAddHeaders($response, $log_register);
        }
        $log_register .= ("SQRL-Nonce is valid!\n");

        $tif = (in_array("noiptest", $decode_request["client"]["opt"])?0:CodesSQRL::getTifCode("IP_MATCH"));
        if($sqrl_nonce->ip_address !== \Request::ip() && !in_array("noiptest", $decode_request["client"]["opt"])) {
            $log_register .= ("SQRL-IP doesn't match!\n");
            $response = SQRLResponse::createResponseSQRLforProblem($decode_request["client"], $decode_request["server"], $sqrl_nonce->nonce, CodesSQRL::getTifCode("COMMAND_FAILED"), $log_register);
            return SQRLResponse::createReponseAndAddHeaders($response, $log_register);
        }
        $log_register .= ("SQRL-IP match!\n");

        if (SignaturesSQRLValidator::validateSignatures($validatedData, $decode_request) === false) {
            $log_register .= ("SQRL-IDS is invalid!\n");
            $response = SQRLResponse::createResponseSQRLforProblem($decode_request["client"], $decode_request["server"], $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
            return SQRLResponse::createReponseAndAddHeaders($response, $log_register);
        }
        $log_register .= ("SQRL-IDS is valid!\n");

        if(!isset($decode_request["client"]["cmd"]) || empty($decode_request["client"]["cmd"])) {
            $log_register .= ("SQRL-request is invalid!\n");
            $response = SQRLResponse::createResponseSQRLforProblem($decode_request["client"], $decode_request["server"], $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("CLIENT_FAILURE"), $log_register);
            return SQRLResponse::createReponseAndAddHeaders($response, $log_register);
        }

        $log_register .= ("SQRL-Nonce type:".$sqrl_nonce->type."!\n");
        if($sqrl_nonce->type === "auth") {
            $response = AuthSQRLController::processRequestSQRL($decode_request["client"], $decode_request["server"], $sqrl_nonce, $validatedData["nut"], $tif, $log_register);
        } else if($sqrl_nonce->type === "question") {
            $response = QuestionSQRLController::processRequestSQRL($decode_request["client"], $decode_request["server"], $sqrl_nonce, $validatedData["nut"], $tif, $log_register);
        } else {
            $log_register .= ("SQRL-Nonce type not supported!\n");
            $response = SQRLResponse::createResponseSQRLforProblem($decode_request["client"], $decode_request["server"], $sqrl_nonce->nonce, $tif+CodesSQRL::getTifCode("COMMAND_FAILED"), $log_register);
        }

        return SQRLResponse::createReponseAndAddHeaders($response, $log_register);
    }

}
