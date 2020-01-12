<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools;

use App\Http\Controllers\Controller;
use DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\SqrlPubKeyController;

class SignaturesSQRLValidator extends Controller
{
    public static function validateSignatures($data_received, $decode_request)
    {
        $data_to_validate = $data_received['client'].$data_received['server'];
        if (!SignaturesSQRLValidator::validateSignature($data_to_validate, $decode_request["client"]['idk'], $decode_request['ids'])) {
            return false;
        }
        if(isset($decode_request['urs']) && isset($decode_request["client"]['vuk'])) {
            if (isset($decode_request["client"]['pidk'])) {
                $sqrl_pubkey = SqrlPubKeyController::checkIfExistIdentityKey($decode_request["client"]["pidk"]);
                if(isset($sqrl_pubkey) && !SignaturesSQRLValidator::validateSignature($data_to_validate, $sqrl_pubkey->vuk, $decode_request['urs'])) {
                    return false;
                }
            } else if(!isset($decode_request["client"]['pidk']) && !SignaturesSQRLValidator::validateSignature($data_to_validate, $decode_request["client"]['vuk'], $urs_decode)) {
                return false;
            } 
        }
        if (isset($decode_request['pids']) && isset($decode_request["client"]['pidk']) && !SignaturesSQRLValidator::validateSignature($data_to_validate, $decode_request["client"]['pidk'], $decode_request['pids'])) {
            return false;
        } 
        return true;
    }

    public static function validateSignature(string $orig, string $pk, string $sig)
    {
        $msg_orig = sodium_crypto_sign_open($sig.$orig, $pk);
        return $msg_orig !== false;
    }
}
