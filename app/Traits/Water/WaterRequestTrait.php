<?php

namespace App\Traits\Water;

use Carbon\Carbon;
use Illuminate\Support\Collection;
trait WaterRequestTrait
{

    public function generateCardDetails($req, $ownerDetails)
    {
        $owners = collect($ownerDetails)->implode('owner_name', ','); 
        $data = new Collection([
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $req->ward_no],
            ['displayString' => 'Holding No', 'key' => 'holdingNo', 'value' => $req->holding_no],
            ['displayString' => 'Owner Name', 'key' => 'ownerName', 'value' => $owners],
            ['displayString' => 'Consumer NO', 'key' => 'ownerName', 'value' => $req->consumer_no],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $req->application_type],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $req->ownership_type],
            ['displayString' => 'Apply-Date', 'key' => 'applyDate', 'value' => $req->application_date],
            ['displayString' => 'Reson', 'key' => 'area', 'value' => $req->reason],
        ]);
        if(trim($req->license_no))
        {
            $data->push(
                ['displayString' => 'License No', 'key' => 'LicenseNo', 'value' => $req->license_no]
            );
        }
        return $data;
    }

    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Application No', 'key' => 'applicationNo', 'value' => $data->application_no],
            ['displayString' => 'Reason', 'key' => 'Reason', 'value' => $data->reason],
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $data->ward_no],
            ['displayString' => 'Holding No', 'key' => 'state', 'value' => $data->consumerDetails->holding_no??""],
            ['displayString' => 'Consumer No', 'key' => 'newWardNo', 'value' => $data->consumerDetails->consumer_no??""],
            ['displayString' => 'Consumer Address', 'key' => 'ownershipType', 'value' => $data->consumerDetails->address??""],
            ['displayString' => 'Property Type', 'key' => 'propertyType', 'value' => $data->property_type],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $data->application_type],
            ['displayString' => 'Firm Type', 'key' => 'firmType', 'value' => $data->firm_type],
            ['displayString' => 'Nature Of Business', 'key' => 'natureofBusiness', 'value' => $data->nature_of_bussiness],
            ['displayString' => 'K No.', 'key' => 'kNo', 'value' => $data->k_no],
            ['displayString' => 'Area In Sqft.', 'key' => 'area', 'value' => $data->area_in_sqft],
            ['displayString' => 'Account No', 'key' => 'accountNo', 'value' => $data->account_no],
            ['displayString' => 'Firm Name', 'key' => 'firmName', 'value' => $data->firm_name],
            ['displayString' => 'Cateogry Type', 'key' => 'categoryType', 'value' => $data->category_type],
            ['displayString' => 'Firm Establishment Date', 'key' => 'establishmentDate', 'value' => $data->establishment_date],
            ['displayString' => 'Address', 'key' => 'address', 'value' => $data->address],
            ['displayString' => 'Landmark', 'key' => 'landmark', 'value' => $data->landmark],
            ['displayString' => 'Applied Date', 'key' => 'applicationDate', 'value' => $data->apply_date],
            ['displayString' => 'Valid Upto', 'key' => 'validUpto', 'value' => $data->valid_upto],
        ]);
    }

    public function generateConsumerDetails($data)
    {
        return new Collection([            
            ['displayString' => 'Consumer No', 'key' => 'applicationNo', 'value' => $data->consumer_no],
            ['displayString' => 'Application Date', 'key' => 'street_name', 'value' => $data->application_apply_date ? Carbon::parse($data->application_apply_date)->format("d-m-Y"):""],
            ['displayString' => 'Ward No', 'key' => 'district', 'value' => $data->ward_no],
            ['displayString' => 'Holding No', 'key' => 'state', 'value' => $data->holding_no],
            ['displayString' => 'category', 'key' => 'area', 'value' => $data->category],
            ['displayString' => 'Property Type', 'key' => 'accountNo', 'value' => $data->property_type_id],
            ['displayString' => 'Pipelien Type', 'key' => 'firmName', 'value' => $data->pipelien_type??""],
            ['displayString' => 'Address', 'key' => 'firmName', 'value' => $data->address??""],
            ['displayString' => 'Apply From', 'key' => 'firmName', 'value' => $data->apply_from??""],
            ['displayString' => 'Entery Type', 'key' => 'firmName', 'value' => $data->entry_type??""],
            ['displayString' => 'Approval Date', 'key' => 'firmName', 'value' => $data->approve_date?Carbon::parse($data->approve_date)->format("d-m-Y"):""],
        ]);
    }

    public function generateConsumerOwnersDetails($data)
    {
        return new Collection([            
            ['displayString' => 'Consumer No', 'key' => 'applicationNo', 'value' => $data->consumer_no],
            ['displayString' => 'Application Date', 'key' => 'street_name', 'value' => $data->application_apply_date ? Carbon::parse($data->application_apply_date)->format("d-m-Y"):""],
            ['displayString' => 'Ward No', 'key' => 'district', 'value' => $data->ward_no],
            ['displayString' => 'Holding No', 'key' => 'state', 'value' => $data->holding_no],
            ['displayString' => 'category', 'key' => 'area', 'value' => $data->category],
            ['displayString' => 'Property Type', 'key' => 'accountNo', 'value' => $data->property_type_id],
            ['displayString' => 'Pipelien Type', 'key' => 'firmName', 'value' => $data->pipelien_type??""],
            ['displayString' => 'Address', 'key' => 'firmName', 'value' => $data->address??""],
            ['displayString' => 'Apply From', 'key' => 'firmName', 'value' => $data->apply_from??""],
            ['displayString' => 'Entery Type', 'key' => 'firmName', 'value' => $data->entry_type??""],
            ['displayString' => 'Approval Date', 'key' => 'firmName', 'value' => $data->approve_date?Carbon::parse($data->approve_date)->format("d-m-Y"):""],
        ]);
    }

    public function generateOwnerDetails($ownerDetails)
    {
        return collect($ownerDetails)->map(function ($ownerDetail, $key) {
            return [
                $key + 1,
                $ownerDetail['owner_name'],
                // $ownerDetail['gender'],
                // $ownerDetail['dob'],
                $ownerDetail['guardian_name'],
                // $ownerDetail['relation_type'],
                $ownerDetail['mobile_no'],
                // $ownerDetail['aadhar_no'],
                // $ownerDetail['pan_no'],
                $ownerDetail['email'],
                // $ownerDetail['address'],

            ];
        });
    }



}