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
            ['displayString' => 'Owner Name', 'key' => 'ownerName', 'value' => $req->applicant_name],
            ['displayString' => 'Consumer NO', 'key' => 'ownerName', 'value' => $req->consumer_no],
            ['displayString' => 'Application Type', 'key' => 'applicationType', 'value' => $req->application_type],
            ['displayString' => 'Ownership Type', 'key' => 'ownershipType', 'value' => $req->owner_type],
            ['displayString' => 'Apply-Date', 'key' => 'applyDate', 'value' => $req->apply_date],
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
            ['displayString' => 'Ward No',                  'key' => 'wardNo',              'value' => $data->ward_no],
            ['displayString' => 'Connection Type',          'key' => 'connectionType',      'value' => $data->connection_type],
            ['displayString' => 'Property Type',            'key' => 'propertyType',        'value' => $data->property_type],
            ['displayString' => 'Connection Through',       'key' => 'connectionThrough',   'value' => $data->connection_through],
            ['displayString' => 'Cateogry',                 'key' => 'Category',            'value' => $data->category],
            ['displayString' => 'Flat Count',               'key' => 'flatCount',           'value' => $data->flat_count],
            ['displayString' => 'Pipeline Type',            'key' => 'PipelineType',        'value' => $data->pipeline_type],
            ['displayString' => 'Apply From',               'key' => 'applyFrom',           'value' => $data->apply_from],
            ['displayString' => 'Applied Date',             'key' => 'applicationDate',     'value' => $data->apply_date],
        ]);
    }
    public function generatePropertyDetails($data)
    {
        return new Collection([
            ['displayString' => 'Holding No',               'key' => 'state',               'value' => $data->holding_no??""],
            ['displayString' => 'Ward No',                  'key' => 'wardNo',              'value' => $data->ward_no],
            ['displayString' => 'Area In Sqft.',            'key' => 'AreaInSqft',          'value' => $data->area_sqft],
            ['displayString' => 'Consumer Address',         'key' => 'Address',             'value' => $data->address??""],
            ['displayString' => 'Landmark',                 'key' => 'Landmark',            'value' => $data->landmark],
            ['displayString' => 'Pin',                      'key' => 'Pin',                 'value' => $data->pin],
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
                $ownerDetail['applicant_name'],
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