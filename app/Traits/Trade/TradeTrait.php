<?php

namespace App\Traits\Trade;

use Illuminate\Support\Facades\Config;
use App\Models\Workflows\WfActiveDocument;
use App\Models\Masters\RefRequiredDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/***
 * @Parent - App\Http\Request\AuthUserRequest
 * Author Name-Anshu Kumar
 * Created On- 27-06-2022
 * Creation Purpose- For Validating During User Registeration
 * Coding Tested By-
 */

trait TradeTrait
{
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data->application_no],
            ['displayString' => 'Licence For Years', 'key' => 'district', 'value' => $data->licence_for_years],
            ['displayString' => 'Holding No', 'key' => 'state', 'value' => $data->holding_no],
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $data->ward_no],
            ['displayString' => 'New Ward No', 'key' => 'newWardNo', 'value' => $data->new_ward_no],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $data->ownership_type],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $data->property_type],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data->application_type],
            ['displayString' => 'Firm Type', 'key' => 'firmType', 'value' => $data->firm_type],
            ['displayString' => 'Nature Of Business', 'key' => 'natureofBusiness', 'value' => $data->nature_of_bussiness],
            ['displayString' => 'K No.', 'key' => 'kNo', 'value' => $data->k_no],
            ['displayString' => 'Area', 'key' => 'area', 'value' => $data->area_in_sqft],
            ['displayString' => 'Account No', 'key' => 'accountNo', 'value' => $data->account_no],
            ['displayString' => 'Firm Name', 'key' => 'firmName', 'value' => $data->firm_name],
            ['displayString' => 'Cateogry Type', 'key' => 'categoryType', 'value' => $data->category_type],
            ['displayString' => 'Firm Establishment Date', 'key' => 'establishmentDate', 'value' => $data->establishment_date],
            ['displayString' => 'Address', 'key' => 'address', 'value' => $data->address],
            ['displayString' => 'Landmark', 'key' => 'landmark', 'value' => $data->landmark],
            ['displayString' => 'Applied Date', 'key' => 'applicationDate', 'value' => $data->application_date],
            ['displayString' => 'Valid Upto', 'key' => 'validUpto', 'value' => $data->valid_upto],
        ]);
    }

    public function generatePropertyDetails($data)
    {
        return new Collection([
            // ['displayString' => 'Khata No.', 'key' => 'khataNo', 'value' => $data->address],
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data->application_no],
            ['displayString' => 'Licence For Years', 'key' => 'district', 'value' => $data->licence_for_years],
            ['displayString' => 'Holding No', 'key' => 'state', 'value' => $data->holding_no],
            ['displayString' => 'Area', 'key' => 'area', 'value' => $data->area_in_sqft],
            ['displayString' => 'Account No', 'key' => 'accountNo', 'value' => $data->account_no],
            ['displayString' => 'Firm Name', 'key' => 'firmName', 'value' => $data->firm_name],
            ['displayString' => 'Street Name', 'key' => 'street_name', 'value' => $data->street_name],
        ]);
    }


    // public function generatepaymentDetails($data)
    // {
    //     return new Collection([
    //         ['displayString' => 'Transaction No', 'key' => 'tranNo', 'value' => $data->tran_no],
    //         ['displayString' => 'Payment Mode', 'key' => 'paymentMode', 'value' => $data->payment_mode],
    //         ['displayString' => 'Paid Amount', 'key' => 'paidAmount', 'value' => $data->paid_amount],
    //         ['displayString' => 'Payment For', 'key' => 'tranType', 'value' => $data->tran_type],
    //         ['displayString' => 'Trasaction Date', 'key' => 'created_at', 'value' => $data->created_at],
    //     ]);
    // }

    public function generatepaymentDetails($data)
    {
        return collect($data)->map(function ($val, $key) {
            return [
                $key + 1,
                $val['tran_type'],
                $val['tran_no'],
                $val['payment_mode'],
                $val['tran_date'],
                // $val['id'],

            ];
        });
    }
    public function generateOwnerDetails($ownerDetails)
    {
        return collect($ownerDetails)->map(function ($ownerDetail, $key) {
            return [
                $key + 1,
                $ownerDetail['owner_name'],
                $ownerDetail['gender'],
                $ownerDetail['dob'],
                $ownerDetail['guardian_name'],
                $ownerDetail['relation_type'],
                $ownerDetail['mobile_no'],
                $ownerDetail['aadhar_no'],
                $ownerDetail['pan_no'],
                $ownerDetail['email'],

            ];
        });
    }

    public function generateCardDetails($req, $ownerDetails)
    {
        $owners = collect($ownerDetails)->implode('owner_name', ',');
        return new Collection([
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $req->ward_no],
            ['displayString' => 'Owner Name', 'key' => 'ownerName', 'value' => $owners],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $req->property_type],
            ['displayString' => 'Assessment Type', 'key' => 'assessmentType', 'value' => $req->assessment_type],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $req->ownership_type],
            ['displayString' => 'Apply-Date', 'key' => 'applyDate', 'value' => $req->application_date],
            ['displayString' => 'Plot-Area(sqt)', 'key' => 'plotArea', 'value' => $req->area_of_plot],
        ]);
    }


    public function getApplTypeDocList($refApplication)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $applicationTypes = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID');
        $moduleId = Config::get('module-constants.TRADE_MODULE_ID');
        $applicationTypeId = $refApplication->application_type_id;
        $ownershipTypeId = $refApplication->ownership_type_id;
        $firmTypeId = $refApplication->firm_type_id;
        $categoryTypeId = $refApplication->category_type_id;

        $flip = flipConstants($applicationTypes);
        switch ($applicationTypeId) {
            case $flip['NEW LICENSE']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "New_Licences")->requirements;
                break;
            case $flip['RENEWAL']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "Reniwal_Licences")->requirements;
                break;
            case $flip['AMENDMENT']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "Amendment_Licences")->requirements;
                break;
            case $flip['SURRENDER']:
                $documentList = $this->vacantDocLists($mRefReqDocs, $moduleId, "Surenderd_Licences");     // Function (1.1)
                break;
        }
        switch ($ownershipTypeId) {
            case 3: # OWN PROPERTY
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "Owner_Premises")->requirements;
                break;
            case 2:# ON LEASE
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "On_Rent")->requirements;
                break;
            case 1: #ON RENT
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "On_Rent")->requirements;
                break;
        }
        switch ($firmTypeId) {
            case 1: # PROPRIETORSHIP
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "NOC_Individual")->requirements;
                break;
            case 2: # PARTNERSHIP
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "NOC_Parter")->requirements;
                break;
            case 3:# PVT. LTD.
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "NOC_Pvt_Ltd_Com")->requirements;
                break;
            case 4: #PUBLIC LTD.
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "NOC_Pvt_Ltd_Com")->requirements;
                break;
                
        }
        switch ($categoryTypeId) {
            case 2: # Dangerous Trade
                $documentList .= $mRefReqDocs->getDocsByDocCode($moduleId, "NOC")->requirements;
                break;
        }
        $documentList = $this->filterDocument($documentList,$refApplication);
        return $documentList;
    }
    /**
     * | Filter Document(1.2)
     */
    public function filterDocument($documentList, $refApplication, $ownerId = null)
    {
        $mWfActiveDocument = new WfActiveDocument();
        $applicationId = $refApplication->id;
        $workflowId = $refApplication->workflow_id;
        $moduleId = Config::get('module-constants.TRADE_MODULE_ID');
        $uploadedDocs = $mWfActiveDocument->getDocByRefIds($applicationId, $workflowId, $moduleId);
        $explodeDocs = collect(explode('#', $documentList))->filter();

        $filteredDocs = $explodeDocs->map(function ($explodeDoc) use ($uploadedDocs, $ownerId) {
            $document = explode(',', $explodeDoc);
            $key = array_shift($document);
            $docName =  array_shift($document);
            $docName = str_replace("{","",str_replace("}","",$docName));
            $documents = collect();
            collect($document)->map(function ($item) use ($uploadedDocs, $documents, $ownerId,$docName) {

                $uploadedDoc = $uploadedDocs->where('doc_code', $docName)
                    ->where('owner_dtl_id', $ownerId)
                    ->first();
                if ($uploadedDoc) {
                    $response = [
                        "uploadedDocId" => $uploadedDoc->id ?? "",
                        "documentCode" => $item,
                        "ownerId" => $uploadedDoc->owner_dtl_id ?? "",
                        "docPath" => $uploadedDoc->doc_path ?? "",
                        "verifyStatus" => $uploadedDoc->verify_status ?? "",
                        "remarks" => $uploadedDoc->remarks ?? "",
                    ];
                    $documents->push($response);
                }
            });
            $reqDoc['docType'] = $key;
            $reqDoc['docName'] = $docName;
            $reqDoc['uploadedDoc'] = $documents->first();

            $reqDoc['masters'] = collect($document)->map(function ($doc) use ($uploadedDocs) {
                $uploadedDoc = $uploadedDocs->where('doc_code', $doc)->first();
                $strLower = strtolower($doc);
                $strReplace = str_replace('_', ' ', $strLower);
                $arr = [
                    "documentCode" => $doc,
                    "docVal" => ucwords($strReplace),
                    "uploadedDoc" => $uploadedDoc->doc_path ?? "",
                    "uploadedDocId" => $uploadedDoc->id ?? "",
                    "verifyStatus'" => $uploadedDoc->verify_status ?? "",
                    "remarks" => $uploadedDoc->remarks ?? "",
                ];
                return $arr;
            });
            return $reqDoc;
        });
        return collect($filteredDocs)->values()??[];
    }
    public function getOwnerDocLists($refOwners, $refApplication)
    {
        $documentList = $this->getOwnerDocs($refApplication);
        if (!empty($documentList)) 
        {
            $filteredDocs['ownerDetails'] = [
                'ownerId' => $refOwners['id'],
                'name' => $refOwners['owner_name'],
                'mobile' => $refOwners['mobile_no'],
                'guardian' => $refOwners['guardian_name'],
            ];
            $filteredDocs['documents']= $this->filterDocument($documentList, $refApplication, $refOwners['id']); 
                                               // function(1.2)
        } 
        else
        {
            $filteredDocs = [];
        }
        return $filteredDocs;
    }

    public function getOwnerDocs($refApplication)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $applicationTypes = Config::get('TradeConstant.APPLICATION-TYPE-BY-ID');
        $moduleId = Config::get('module-constants.TRADE_MODULE_ID');
        $applicationTypeId = $refApplication->application_type_id;
        $flip = flipConstants($applicationTypes);
        switch ($applicationTypeId) {
            case $flip['NEW LICENSE']:
                $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "New_Licences_Owneres")->requirements;
                break;
            default :  $documentList = collect([]);
        }
        return $documentList;
    }
}
