<?php

namespace DestruidorPT\LaravelSQRLAuth\App\Http\Controllers\SQRL\Tools;
 
class CodesSQRL
{
    public static function getTifCodes() 
    {
        //TIF Codes
        return [
           "ID_MATCH" => 0x01,
           "PREVIOUS_ID_MATCH" => 0x02,
           "IP_MATCH" => 0x04,
           "SQRL_DISABLED" => 0x08,
           "FUNCTION_NOT_SUPPORTED" => 0x10,
           "TRANSIENT_ERROR" => 0x20,
           "COMMAND_FAILED" => 0x40,
           "CLIENT_FAILURE" => 0x80,
           "BAD_ID_ASSOCIATION" => 0x100,
           "IDENTITY_SUPERSEDED" => 0x200,
        ];
    }
    
    public static function getTifCode(string $tifCodeName) 
    {
        //TIF Code
        return self::getTifCodes()[$tifCodeName];
    }
}