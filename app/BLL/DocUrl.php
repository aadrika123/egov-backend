<?php

namespace App\BLL;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * | Created By: Mrinal Kumar
 * | Created On: 12-09-2023
 * | Status : Closed
    Not In Use
 */

class DocUrl
{
    /**
     * | This function is to get the document url from the DMS for multiple documents
     */
    public function getDocUrl($documents)
    {
        $dmsUrl = Config::get('module-constants.DMS_URL');

        $apiUrl = "$dmsUrl/backend/document/view-by-reference";
        $data = collect();

        foreach ($documents as $document) {
            $postData = [
                'referenceNo' => $document->reference_no,
            ];
            if ($document->reference_no) {
                $response = Http::withHeaders([
                    "token" => "8Ufn6Jio6Obv9V7VXeP7gbzHSyRJcKluQOGorAD58qA1IQKYE0",
                ])->post($apiUrl, $postData);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $key['id'] =  $document->id;
                    $key['doc_code'] =  $document->doc_code;
                    $key['verify_status'] =  $document->verify_status;
                    $key['owner_name'] =  $document->owner_name;
                    $key['remarks'] =  $document->remarks;
                    $key['owner_dtl_id'] =  $document->owner_dtl_id ?? null;
                    $key['doc_path'] = $responseData['data']['fullPath'];
                    $data->push($key);
                }
            }
        }
        return $data;
    }

    /**
     * | This function is to get the document url from the DMS for single documents
     */
    public function getSingleDocUrl($document)
    {
        $dmsUrl = Config::get('module-constants.DMS_URL');
        $apiUrl = "$dmsUrl/backend/document/view-by-reference";
        $key = collect();

        if ($document) {
            $postData = [
                'referenceNo' => $document->reference_no,
            ];
            $response = Http::withHeaders([
                "token" => "8Ufn6Jio6Obv9V7VXeP7gbzHSyRJcKluQOGorAD58qA1IQKYE0",
            ])->post($apiUrl, $postData);

            if ($response->successful()) {
                $responseData = $response->json();
                $key['id'] =  $document->id ?? null;
                $key['doc_id'] =  $document->doc_id ?? null;
                $key['doc_code'] =  $document->doc_code;
                $key['verify_status'] =  $document->verify_status;
                $key['owner_name'] =  $document->owner_name;
                $key['remarks'] =  $document->remarks ?? null;
                $key['doc_path'] = $responseData['data']['fullPath'];
                // $data->push($key);
            }
        }
        return $key;
    }
}
