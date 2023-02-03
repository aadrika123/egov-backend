<?php

namespace App\Http\Controllers\water;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Water\NewConnectionController;
use App\Repository\Water\Interfaces\iNewConnection;
use App\Repository\Water\Interfaces\IWaterNewConnection;
use Exception;
use Illuminate\Http\Request;

class WaterApplication extends Controller
{
    private $Repository;
    private $_NewConnectionController;
    public function __construct(IWaterNewConnection $Repository, iNewConnection $newConnection)
    {
        $this->Repository = $Repository;
        $this->_NewConnectionController = new NewConnectionController($newConnection);
    }

    public function applyApplication(Request $request)
    {
        return $this->Repository->applyApplication($request);
    }
    public function getCitizenApplication(Request $request)
    {
        try {
            $returnValue = $this->Repository->getCitizenApplication($request);
            $refReturn = collect($returnValue)->map(function ($value) {

                $refData['applicationId'] = $value['id'];
                $metaReq = new Request($refData);
                $documentList =  $this->_NewConnectionController->getDocToUpload($metaReq);
                $refDoc = collect($documentList)['original']['data']['documentsList'];

                $checkDocument = collect($refDoc)->map(function ($value, $key) {
                    if ($value['isMadatory'] == 1) {
                        $doc = collect($value['uploadDoc'])->first();
                        if (is_null($doc)) {
                            return false;
                        }
                        return true;
                    }
                    return true;
                });
                
                if ($checkDocument->contains(false)) {
                    $value['upload_status'] = false;
                    return $value;
                }
                $value['upload_status'] = true;
                return $value;
            });
            return responseMsg(true, "", remove_null($refReturn));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    public function handeRazorPay(Request $request)
    {
        return $this->Repository->handeRazorPay($request);
    }
    public function readTransectionAndApl(Request $request)
    {
        return $this->Repository->readTransectionAndApl($request);
    }
    public function paymentRecipt(Request $request)
    {
        $request->validate([
            'transectionNo' => 'required'
        ]);
        return $this->Repository->paymentRecipt($request->transectionNo);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    public function getUploadDocuments(Request $request)
    {
        return $this->Repository->getUploadDocuments($request);
    }
    public function calWaterConCharge(Request $request)
    {
        return $this->Repository->calWaterConCharge($request);
    }
}
