<?php

namespace App\Http\Controllers;

use App\Models\Rules;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class verificationController extends Controller
{
    public function getRules($uploadId)
    {
        $rules = Rules::query()->where('upload_id', $uploadId)->get()->all();
        $response = new JsonResponse();
        if (empty($rules)) {
            $response->setStatusCode(404);
            $response->setContent("Upload Not found");
            return $response;
        }
        // @todo  check if valid until time is not crossed before sending back, maybe update the query
        $response->setStatusCode(200);
        $response->setContent(json_encode($rules));
        return $response;
    }

    public function satisfy($uploadId, Request $request)
    {
        $upload = Upload::query()->find($uploadId);
        $response = new JsonResponse();
        if ($upload === null) {
            $response->setStatusCode(404);
            $response->setContent("Rule not found");
            return $response;
        }

        $content = json_decode($request->getContent(), true);
        date_default_timezone_set("Europe/London");

        if ($upload->expires_at < date("Y-m-d H:i:s")) {
            $response->setStatusCode(400);
            $response->setContent("Share period expired");
            return $response;
        }

        $rules = Rules::query()->where('uploadId', $uploadId)->get()->all();

        if (empty($rules)) {
            $response->setStatusCode(500);
            $response->setContent("Rules not setup properly");
            return $response;
        }
        $count = count($rules);
        $issuedTo = "";
        try {
            foreach ($rules as $rule) {
                foreach ($content as $contentItem) {
                    if ($contentItem['ruleId'] === $rule->id) {
                        $payload = json_decode($contentItem['key']['payload'], true);
                        if ($issuedTo == "") {
                            $issuedTo = $payload['issuedTo'];
                        } else {
                            if ($payload['issuedTo'] != $issuedTo) {
                                throw new \Exception("Issued to different users");
                            }
                        }
                        $this->verify($rule, $contentItem);
                        $count--;
                    }
                }
            }
        } catch (\Exception $exception) {
            $response->setStatusCode(400);
            $response->setContent($exception->getMessage());
            return $response;
        }

        if ($count > 1) {
            $response->setStatusCode(400);
            $response->setContent("Keys required to unlock missing");
            return $response;
        }

        $path = $this->zipFolder($upload->file_path);

        if ($path === false) {
            $response->setStatusCode(500);
            $response->setContent("Something went wrong try again later");
            return $response;
        }

        $response->setStatusCode(200);
        $response->setContent(basename($path));
        return $response;
    }

    public function download(Request $request)
    {
        $filename = $request->get('filename');
        if ($filename === null) {
            return response('File not found', 404,);
        }
        $path = storage_path() . "/tmp/download/" . $filename;
        register_shutdown_function(function () use ($path) {
            unlink($path);
        });
        return response()->download($path);
    }

    private function zipFolder($filepath)
    {
        $zip = new ZipArchive();
        $storagePath = storage_path() . "/tmp/download";
        $timeName = time();
        $zipFileName = $storagePath . '/' . $timeName . '.zip';
        $filesArr = Storage::disk('local')->files($filepath);

        if ($zip->open(($zipFileName), ZipArchive::CREATE) === true) {
            foreach ($filesArr as $relativeName) {
                $filepath = storage_path() . "/app/" . $relativeName;
                $zip->addFile($filepath, basename($relativeName));
            }
            $zip->close();

            if ($zip->open($zipFileName) === true) {
                return $zipFileName;
            } else {
                return false;
            }
        }
    }

    private function verify(Rules $rule, array $payload)
    {
        $rulePayload = $payload['key']['payload'];
        $signature = $payload['key']['signature'];

        $payloadRuleArray = json_decode($rulePayload, true);
        $dbRuleArray = json_decode($rule->attributes, true);

        $payloadAttributes = $payloadRuleArray['attributes'];
        if (!empty(array_diff($dbRuleArray, $payloadAttributes))) {
            throw new \Exception("Invalid Keys: Missing attributes");
        }
        $apiResponse = Http::get($rule->authority . '/api/publicKey');
        if ($apiResponse->status() != 200) {
            throw new \Exception("Authority is not available");
        }

        $publicKey = $apiResponse->body();
        $result = openssl_verify($rulePayload, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);
        if ($result === 1) {
            return;
        }
        throw new \Exception("Signature Verification failed");
    }

    public function release($uploadId)
    {
        $upload = Upload::find($uploadId);
        $response = new JsonResponse();
        if ($upload === null) {
            $response->setStatusCode(404);
            $response->setContent("Upload not found");
            return $response;
        }
        $rules = Rules::query()->where('uploadId', $uploadId)->get()->all();

        foreach ($rules as $rule) {
            if ($rule->verified != 1) {
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
