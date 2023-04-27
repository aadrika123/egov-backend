<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
if(!function_exists('WHATSAPPJHGOVT'))
{
    function WHATSAPPJHGOVT($mobileno,$templateid,array $message=[])
    {
        $bearerToken = Config::get("NoticeConstaint.WHATSAPP_TOKEN");
        $numberId = Config::get("NoticeConstaint.WHATSAPP_NUMBER_ID");
        $url = Config::get("NoticeConstaint.WHATSAPP_URL");
        $result = Http::withHeaders([
    
            "Authorization" => "Bearer $bearerToken",
            "contentType" => "application/json"

        ])->post($url.$numberId."/messages", [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => "+91$mobileno", //<--------------------- here
            "type" => "template",
            "template" => [
                "name" => "$templateid",
                "language" => [
                    "code" => "en_US"
                ],
                "components" => [
                    ($message?(
                    [
                        "type" => "header",
                        "parameters" => [
                            [
                                "type" => "document",
                                "document" => [$message]
                            ]
                        ]
                    ]
                    ):""),
                ]
            ]
        ]);
        $responseBody = json_decode($result->getBody(),true);
        if (isset($responseBody["error"]))
        {
            $response = ['response'=>false, 'status'=> 'failure', 'msg'=>$responseBody];
        }
        else
        {
            $response = ['response'=>true, 'status'=> 'success', 'msg'=>$responseBody];                
        }
        
        return $response;
    }
}

if(!function_exists('Whatsapp_Send'))
{
    function Whatsapp_Send($mobileno,$templateid,array $message=[])
    {
        $res=WHATSAPPJHGOVT("8002158818", $templateid,$message);
        return $res;
    }
}