<?php

namespace App\Http\Controllers;

use App\Models\Rules;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class verificationController extends Controller
{
    public function getRules($uploadId){
        $rules = Rules::query()->where('uploadId', $uploadId)->get()->all();
        $response = new JsonResponse();
        if(empty($rules))
        {
            $response->setStatusCode(404);
            $response->setContent("Upload Not found");
            return $response;
        }
        // @todo  check if valid until time is not crossed before sending back, maybe update the query


        $response->setStatusCode(200);
        $response->setContent(json_encode($rules));
        return $response;
    }

    public function satisfy($ruleId,Request $request){
        $rule = Rules::query()->find($ruleId);
        $response = new JsonResponse();
        if($rule === null)
        {
            $response->setStatusCode(404);
            $response->setContent("Rule not found");
            return $response;
        }

        $apiResponse = Http::get($rule->authority.'/api/publicKey');
        if($apiResponse->status()!=200)
        {
            $response->setStatusCode(400);
            $response->setStatusCode("Authority's public key is not available");
            return $response;
        }

        $content = json_decode($request->getContent(),true);
        $publicKey = $apiResponse->body();
        $result = openssl_verify($content['payload'], base64_decode($content['signature']), $publicKey, OPENSSL_ALGO_SHA256);
        if($result === 0)
        {
            $response->setStatusCode(400);
            $response->setContent("Invalid Key from authority");
            return $response;
        }

        // @todo  check if valid until time is not crossed
        if($content['payload']['validUntil'])
        {

        }

        $required_attribute = json_decode($rule->attributes,true);
        foreach($required_attribute as $attribute)
        {
            if(!in_array($attribute,$content['payload']['attributes']))
            {
                $response->setStatusCode(400);
                $response->setContent("Required attribute missing from key: ".$attribute);
                return $response;
            }
        }

        $response->setStatusCode(200);
        $rule->verified = 1;
        $rule->granted_to = $content['payload']['issuedTo'];
        //successful validation
        return $response;
    }

    public function release($uploadId)
    {
        $upload = Upload::find($uploadId);
        $response = new JsonResponse();
        if($upload === null)
        {
            $response->setStatusCode(404);
            $response->setContent("Upload not found");
            return $response;
        }
        $rules = Rules::query()->where('uploadId', $uploadId)->get()->all();

        foreach ($rules as $rule)
        {
            if($rule->verified != 1)
            {
                $response->setStatusCode(400);
                $response->setContent("Required keys not submitted");
                return $response;
            }
        }
        $response->setStatusCode(200);
        // @todo  this should be a json with files or send multiple files here
        $response->setContent("{}");
        return $response;
    }
}
