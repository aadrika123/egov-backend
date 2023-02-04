<?php

namespace App\Traits\Trade;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Database\Eloquent\Collection;

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
                $val['id'],

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
}
