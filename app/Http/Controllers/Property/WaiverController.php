<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\MicroServices\DocUpload;
use App\Models\Property\PropActiveWaiver;
use App\Models\Property\Waiver as PropertyWaiver;
use App\Models\Waiver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaiverController extends Controller
{
    public function createwaiver(Request $request)
    {
        $validated = Validator::make($request->all(), [
            // "propertyId" => 'required',
            // "safId" =>'required',
            // "isRwhPenalty" => 'required',
            // "rwhPenaltyAmount" =>'required',
            // "isOnePercentPenalty" => 'required',
            // "onePercentPenaltyAmount" => 'required',
            // "isLateAssessmentPenalty" => 'required',
            // "isBill" => 'required',
            // "billAmount" => 'required',
            // "waiverDocument" => 'required'
        ]);
    
        if ($validated->fails()) {
            return validationError($validated);
        }
    
        try {
            $docUpload = new DocUpload();
            $path = "Uploads/Property";
            $refImageName = "WaiverDocuments";
            $document = $request->waiverDocuments;
    
            $imageName = $docUpload->upload($refImageName, $document, $path);
            
            $request->merge(["WaiverDocuments" => $path . $imageName]);
    
            $create = new PropActiveWaiver();
            $data = $create->addwaiver($request);
    
            return responseMsg(true, "Data Saved", $data);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
    
    
//     public function createwaiver(Request $request)
//     {
        
//         $validated = Validator::make($request->all(), [
//             // "propertyId" => 'required',
//             // "safId" =>'required',
//             // "isRwhPenalty" => 'required',
//             // "rwhPenaltyAmount" =>'required',
//             // "isOnePercentPenalty" => 'required',
//             // "onePercentPenaltyAmount" => 'required',
//             // "isLateAssessmentPenalty" => 'required',
//             // "isBill" => 'required',
//             // "billAmount" => 'required',
//             // "waiverDocument" => 'required'
            
//         ]);

//         if ($validated->fails()) {
//             return validationError($validated);
//         }

//         try {
//             $docUpload = new DocUpload();
//             $path = "Waiver/Documents";
//             $refImageName = "WaiverDocuments";
//             $document = $request->waiverDocuments;

//             $imageName = $docUpload->upload($refImageName, $document, $path);
//             $newrequest = collect($request->all());
//             $newrequest->pull("WaiverDocuments");
//             $newrequest = new Request($newrequest->toArray());
//             $newrequest->merge(["WaiverDocuments" => $path . $imageName]);
// 6
//             $create = new PropActiveWaiver();
//             // dd($request->all(),$newrequest->all());
//             $data = $create->addwaiver($newrequest);

//             return responseMsg(true, "Data Saved",$data);
//         } catch (Exception $e) {
//             return responseMsg(false, $e->getMessage(), "");
//         }
//     }
    
}


             

