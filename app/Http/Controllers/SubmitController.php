<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class SubmitController extends Controller
{
    public function verify(Request $request)
    {
        $response = new Response();
        $requestArray = json_decode($request->getContent(),true);
        $stringPayload = $requestArray['payload'];
        $signature = $requestArray['signature'];
        $apiResponse = Http::get('http://127.0.0.1:8001/api/publicKey');
        if($apiResponse->status()!=200)
        {
            $response->setStatusCode(400);
            return $response;
        }
        $publicKey = $apiResponse->body();
        $result = openssl_verify($stringPayload, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);
        if($result === 1)
        {
            $response->setStatusCode(200);
            //successful validation
            return $response;
        }

        $response->setStatusCode(400);
        return $response;


    }

    public function publicKey(Request $request)
    {
        $response = new Response();
        $file = file_get_contents(base_path() . "/publickey.crt");
        if ($file === null) {
            $response->setStatusCode(500);
        }
        $response->setStatusCode(200);
        $response->setContent($file);
        return $response;
    }
}


