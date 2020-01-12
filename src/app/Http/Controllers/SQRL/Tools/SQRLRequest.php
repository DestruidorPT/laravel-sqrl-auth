<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SQRLRequest extends Controller
{
    public static function getDecodeAllData($data_encoded, &$log_register) 
    {
        $data_decoded = [];

        $log_register .= ("SQRL-Dados Decode: {\n");
        if(isset($data_encoded["client"]) && !empty($data_encoded["client"])) {
            $data_decoded["client"] = SQRLRequest::getClientDecode($data_encoded["client"], $log_register);
        }
        if(isset($data_encoded["server"]) && !empty($data_encoded["server"])) {
            $data_decoded["server"] = SQRLRequest::getServerDecode($data_encoded["server"], $log_register);
        }
        if(isset($data_encoded["ids"]) && !empty($data_encoded["ids"])) {
            $data_decoded["ids"] = Base64Url::base64UrlDecode($data_encoded["ids"]);
            $log_register .= ("\tids: ".$data_decoded["ids"]."\n");
        }
        if(isset($data_encoded["urs"]) && !empty($data_encoded["urs"])) {
            $data_decoded["urs"] = Base64Url::base64UrlDecode($data_encoded["urs"]);
            $log_register .= ("\turs: ".$data_decoded["urs"]."\n");
        }
        if(isset($data_encoded["pids"]) && !empty($data_encoded["pids"])) {
            $data_decoded["pids"] = Base64Url::base64UrlDecode($data_encoded["pids"]);
            $log_register .= ("\tpids: ".$data_decoded["pids"]."\n");
        }
        $log_register .= ("}\n");
        
        return $data_decoded;
    }

    public static function getClientDecode($clientInput, &$log_register)
    {
        $log_register .= ("\tclient: {\n");
        $inputAsArray = explode("\n", Base64Url::base64UrlDecode($clientInput));
        $return = array();
        foreach (array_filter($inputAsArray) as $individualInputs) {
            if (strpos($individualInputs, '=') === false) {
                continue;
            }
            list($key,$val) = explode("=", $individualInputs);
            $val = trim($val);//strip off the \r
            switch (trim($key)){
                case 'ver':
                    $return['ver']=$val;
                    break;
                case 'cmd':
                    $return['cmd']=$val;
                    break;
                case 'btn':
                    $return['btn']=$val;
                    break;
                case 'idk':
                    $return['idk']=Base64Url::base64UrlDecode($val);
                    break;
                case 'ins':
                    $return['ins']=Base64Url::base64UrlDecode($val);
                    break;
                case 'pidk':
                    $return['pidk']=Base64Url::base64UrlDecode($val);
                    break;
                case 'vuk':
                    $return['vuk']=Base64Url::base64UrlDecode($val);
                    break;
                case 'suk':
                    $return['suk']=Base64Url::base64UrlDecode($val);
                    break;
                case 'opt':
                    $return['opt'] = explode('~',$val);
                    break;
            }
            $log_register .= ("\t\t".$key.": ".(is_array($return[$key])?implode(", ",$return[$key]):$return[$key])."\n");
        }
        $log_register .= ("\t}\n");
        return $return;
    }
    
    public static function getServerDecode($serverData, &$log_register)
    {
        $log_register .= ("\tserver: {\n");
        $decoded = Base64Url::base64UrlDecode($serverData);
        if (substr($decoded,0,7)==='sqrl://' || substr($decoded,0,6)==='qrl://') {
            $log_register .= ("\t\t".$decoded."\n");
            $log_register .= ("\t}\n");
            return $decoded;
        }
        $serverValues = explode("\r\n", $decoded);
        $parsedResult = array();
        foreach ($serverValues as $value) {
            $splitStop = strpos($value, '=');
            $key = substr($value, 0, $splitStop);
            $val = substr($value, $splitStop+1);
            $parsedResult[$key]=$val;
            $log_register .= ("\t\t".$key.": ".(is_array($parsedResult[$key])?implode(", ",$parsedResult[$key]):$parsedResult[$key])."\n");
        }
        $log_register .= ("\t}\n");
        return $parsedResult;
    }
}