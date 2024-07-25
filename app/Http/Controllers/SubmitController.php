<?php

namespace App\Http\Controllers;

use App\Models\Rules;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function submitRules(Request $request)
    {
        $payload = json_decode($request->getContent(),true);
        $upload = new Upload();
        $upload->expires_at = $payload['expires_at'];
        $upload->file_path = "tmp/".Str::uuid();
        $upload->save();
        foreach($payload['rules'] as $incRule)
        {
            $rule = new Rules();
            $rule->authority = $incRule['authority'];
            $rule->attributes = json_encode($incRule['attributes']);
            $rule->verified = 0;
            $rule->upload_id = $upload->id;
            $rule->save();
        }
        $response = new Response();
        $response->setStatusCode(200);
        $response->setContent($upload->id);
        return $response;
    }

    public function uploadFiles(Request $request,$uploadId)
    {
        $upload = Upload::find($uploadId);
        $response = new Response();
        if($upload === null )
        {
            $response->setStatusCode(400);
            $response->setContent("Something went wrong, Invalid order ID");
            return $response;
        }
//	var_dump($upload->file_path);die;
        foreach($request->files as $file)
        {
            $originalName = $file->getClientOriginalName();
            $path = $upload->file_path."/".$originalName;
//	    var_dump($path);
            Storage::disk('local')->put($path, file_get_contents($file));
        }
//die;

        $response->setStatusCode(200);
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


