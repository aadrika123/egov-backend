<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterConsumerActiveRequest extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Save request details 
     */
    public function saveRequestDetails($req, $consumerDetails, $refRequest, $applicationNo)
    {
        $mWaterConsumerActiveRequest = new WaterConsumerActiveRequest();
        $mWaterConsumerActiveRequest->consumer_id               = $consumerDetails->id;
        $mWaterConsumerActiveRequest->apply_date                = Carbon::now();
        $mWaterConsumerActiveRequest->citizen_id                = $refRequest['citizenId'] ?? null;
        $mWaterConsumerActiveRequest->created_at                = Carbon::now();
        $mWaterConsumerActiveRequest->emp_details_id            = $refRequest['empId'] ?? null;
        $mWaterConsumerActiveRequest->ward_mstr_id              = $consumerDetails->ward_mstr_id;
        $mWaterConsumerActiveRequest->reason                    = $req['reason'] ?? null;
        $mWaterConsumerActiveRequest->amount                    = $refRequest['amount'];
        $mWaterConsumerActiveRequest->remarks                   = $req['remarks'];
        $mWaterConsumerActiveRequest->corresponding_address     = $req['address'] ?? null; // added by alok
        $mWaterConsumerActiveRequest->apply_from                = $refRequest['applyFrom'];
        $mWaterConsumerActiveRequest->initiator                 = $refRequest['initiatorRoleId'];
        $mWaterConsumerActiveRequest->current_role              = $refRequest['initiatorRoleId'];
        $mWaterConsumerActiveRequest->workflow_id               = $refRequest['ulbWorkflowId'];
        $mWaterConsumerActiveRequest->ulb_id                    = $refRequest['ulbId'];
        $mWaterConsumerActiveRequest->finisher                  = $refRequest['finisherRoleId'];
        $mWaterConsumerActiveRequest->user_type                 = $refRequest['userType'];
        $mWaterConsumerActiveRequest->application_no            = $applicationNo;
        $mWaterConsumerActiveRequest->charge_catagory_id        = $refRequest['chargeCategoryId'];
        $mWaterConsumerActiveRequest->corresponding_mobile_no   = $req->mobileNo ?? null;
        $mWaterConsumerActiveRequest->save();
        return [
            "id" => $mWaterConsumerActiveRequest->id
        ];
    }

    /**
     * | Get Active appication by consumer Id
     */
    public function getRequestByConId($consumerId)
    {
        return WaterConsumerActiveRequest::where('consumer_id', $consumerId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Get active request
     */
    public function getActiveRequest($applicationId)
    {
        return WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1);
    }
    /**
     * | Get Active appication by consumer Id
     */
    public function getRequestByAppId($applicationId)
    {
        return WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Get application by ID
     */
    public function getRequestById($id)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.*',
            'water_consumer_charge_categories.amount',
            'water_consumer_charge_categories.charge_category AS charge_category_name'
        )
            ->join('water_consumer_charge_categories', 'water_consumer_charge_categories.id', 'water_consumer_active_requests.charge_catagory_id')
            ->where('water_consumer_active_requests.status', 1)
            ->where('water_consumer_active_requests.id', $id);
    }

    /**
     * | Get active request by request id 
     */
    public function getActiveReqById($id)
    {
        return WaterConsumerActiveRequest::where('id', $id)
            ->where('status', 1);
    }




    /**
     * | Update the payment status and the current role for payment 
     * | After the payment is done the data are update in active table
     */
    public function updateDataForPayment($applicationId, $req)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1)
            ->update($req);
    }

    /**
     * | Get the Application according to user details 
     */
    public function getApplicationByUser($userId)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            DB::raw('REPLACE(water_consumer_charges.charge_category, \'_\', \' \') as charge_category'),
            "water_consumer_active_requests.corresponding_address",
            "water_consumer_active_requests.corresponding_mobile_no",
            "water_consumers.consumer_no",
            "water_consumer_active_requests.ward_mstr_id",
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.payment_status",
            "ulb_ward_masters.ward_name",
            "ulb_name"
        )
            ->join('ulb_masters', 'ulb_masters.id', 'water_consumer_active_requests.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_consumer_active_requests.ward_mstr_id')
            ->join('water_consumer_charges', 'water_consumer_charges.related_id', 'water_consumer_active_requests.id')
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->where('water_consumer_active_requests.citizen_id', $userId)
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }
    // get consumer Details
    public function getApplicationByUserV1($applicationId)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.consumer_id',
            'wf_roles.role_name as current_role_name',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            DB::raw('REPLACE(water_consumer_charges.charge_category, \'_\', \' \') as charge_category'),
            'water_consumer_active_requests.corresponding_address',
            'water_consumer_active_requests.corresponding_mobile_no',
            'water_consumers.consumer_no',
            'water_consumers.address',
            'water_owner_type_mstrs.owner_type as owner_char_type',
            'water_param_pipeline_types.pipeline_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'water_consumers.holding_no',
            'water_connection_type_mstrs.connection_type',
            'water_consumers.area_sqft',
            'water_consumers.category',
            'water_consumer_active_requests.ward_mstr_id',
            'water_consumer_active_requests.apply_date',
            'water_consumer_active_requests.payment_status',
            'water_consumer_active_requests.doc_verify_status',
            'water_consumer_active_requests.doc_upload_status',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            DB::raw('(
                SELECT string_agg(applicant_name, \',\') 
                FROM water_consumer_owners 
                WHERE water_consumer_owners.consumer_id = water_consumer_active_requests.consumer_id
            ) as applicantName'),
            DB::raw('(
                SELECT string_agg(mobile_no::VARCHAR, \',\') 
                FROM water_consumer_owners 
                WHERE water_consumer_owners.consumer_id = water_consumer_active_requests.consumer_id
            ) as mobileNo'),
            DB::raw('(
                SELECT string_agg(guardian_name, \',\') 
                FROM water_consumer_owners 
                WHERE water_consumer_owners.consumer_id = water_consumer_active_requests.consumer_id
            ) as guardianName')
        )
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_consumer_active_requests.ulb_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumer_active_requests.ward_mstr_id')
            ->join('water_consumer_charges', 'water_consumer_charges.related_id', '=', 'water_consumer_active_requests.id')
            ->leftJoin('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_consumers.owner_type_id')
            ->join('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_consumers.pipeline_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_consumers.property_type_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->join('wf_roles', 'wf_roles.id', 'water_consumer_active_requests.current_role')
            ->where('water_consumer_active_requests.id', $applicationId)
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    // public function getPropertyByConsumerId($consumerId)
    // {
    //     return WaterConsumerActiveRequest::select(
    //         'water_connection_through_mstrs.connection_through',
    //         'water_consumers.holding_no',
    //         'water_connection_type_mstrs.connection_type',
    //         'water_consumers.area_sqft',
    //         'water_consumers.category',
           
    //     )
    //     ->leftJoin('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
    //     ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_consumers.connection_type_id')
    //     ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
    //     ->join('wf_roles', 'wf_roles.id', '=', 'water_consumer_active_requests.current_role')
    //     ->where('water_consumers.id', $consumerId)
    //     ->where('water_consumer_active_requests.status', 1)
    //     ->orderByDesc('water_consumer_active_requests.id');
    // }
    
    //written by prity pandey
    public function getConsumerByApplication($applicationId)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.workflow_id",
            "water_consumers.*"
        )
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->where('water_consumer_active_requests.id', $applicationId)
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    public function getApplicationById($applicationId)
    {
        return  WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', 1);
    }

    public function updateParkedstatus($status, $applicationId)
    {
        $mWaterApplication = WaterConsumerActiveRequest::find($applicationId);
        switch ($status) {
            case (true):
                $mWaterApplication->parked = $status;
                break;

            case (false):
                $mWaterApplication->parked = $status;
                break;
        }
        $mWaterApplication->save();
    }

    public function deactivateUploadStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_upload_status' => false
            ]);
    }

    public function activateUploadStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_upload_status' => true
            ]);
    }

    public function searchApplication()
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.reason',
            'water_consumer_active_requests.remarks',
            'water_consumer_active_requests.amount',
            'water_consumer_active_requests.application_no',
            "water_consumer_active_requests.apply_date",
            "water_consumer_active_requests.workflow_id",
            "water_consumers.id as consumer_id",
            "water_consumers.consumer_no",
            "water_consumers.address",
            "water_consumer_owners.applicant_name",
            "water_consumer_owners.mobile_no",
        )
            ->leftjoin('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->leftjoin('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_consumers.id')
            ->where('water_consumer_active_requests.status', 1)
            ->orderByDesc('water_consumer_active_requests.id');
    }

    public function updateAppliVerifyStatus($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'doc_verify_status' => true
            ]);
    }

    public function getConserDtls()
    {
        return $this->belongsTo(WaterConsumer::class, "consumer_id", "id", "id")->first();
    }

    public function waterConsumer()
    {
        return $this->hasOne(WaterConsumer::class, 'id', 'consumer_id');
    }


    //eoc








    ///////////////////////////////////////////////////////////////////////////////
    public function saveWaterConsumerActive($req, $consumerId, $meteReq, $refRequest, $applicationNo)
    {
        $mWaterConsumeActive = new WaterConsumerActiveRequest();
        $mWaterConsumeActive->id;
        $mWaterConsumeActive->ulb_id                   = $meteReq['ulbId'];
        $mWaterConsumeActive->application_no           = $applicationNo;
        $mWaterConsumeActive->consumer_id              = $consumerId;
        $mWaterConsumeActive->emp_details_id           = $refRequest['empId'] ?? null;
        $mWaterConsumeActive->citizen_id               = $refRequest["citizenId"] ?? null;
        $mWaterConsumeActive->apply_from               = $refRequest['applyFrom'];
        $mWaterConsumeActive->apply_date               = $meteReq['applydate'];
        $mWaterConsumeActive->amount                   = $meteReq['amount'];
        $mWaterConsumeActive->reason                   = $req->reason;
        $mWaterConsumeActive->remarks                  = $req->remarks;
        $mWaterConsumeActive->doc_verify_status        = $req->doc_verify_status;
        // $mWaterConsumeActive->payment_status           = $req->payment_status;
        $mWaterConsumeActive->charge_catagory_id       = $meteReq['chargeCategoryID'];
        $mWaterConsumeActive->corresponding_mobile_no  = $req->mobileNo;
        $mWaterConsumeActive->corresponding_address    = $req->address;
        $mWaterConsumeActive->ward_mstr_id             = $meteReq['wardmstrId'];
        $mWaterConsumeActive->initiator                = $refRequest['initiatorRoleId'];
        $mWaterConsumeActive->finisher                 = $refRequest['finisherRoleId'];
        $mWaterConsumeActive->user_type                = $refRequest['userType'];
        $mWaterConsumeActive->workflow_id              = $meteReq['ulbWorkflowId'];
        $mWaterConsumeActive->current_role             = $refRequest['initiatorRoleId'];
        $mWaterConsumeActive->save();
        return $mWaterConsumeActive;
    }

    /**
     * |------------------- Get the Application details by applicationNo -------------------|
     * | @param applicationNo
     * | @param connectionTypes 
     * | @return 
     */
    public function getDetailsByParameters($req, $applicationNo)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.application_no',
            'water_consumer_active_requests.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_consumers.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_consumers.guardian_name,',') as guardianName"),
        )
            ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumer_active_requests.ward_mstr_id')
            ->where('water_consumer_active_requests.status', true)
            ->where('water_consumer_active_requests.charge_category_id', 2)
            ->where('water_consumer_active_requests.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_consumer_active_requests.ulb_id', authUser($req)->ulb_id)
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumer_active_requests.id',
                'water_applicants.application_id',
                'water_consumer_active_requests.application_no',
                'water_consumer_active_requests.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            );
    }

    public function getDetailsByAppNoWaterDisc($req, $applicationNo)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.application_no',
            'water_consumer_charge_categories.charge_category',
            DB::raw("DATE(water_consumer_active_requests.apply_date) as apply_date"),
            'wf_roles.role_name as current_role',
            'water_consumer_active_requests.corresponding_mobile_no',
            'water_consumers.holding_no',
            'water_consumers.ward_mstr_id',
            'water_consumer_owners.city as address',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardianName"),
            DB::raw("CASE
                    WHEN water_consumer_active_requests.payment_status = 1 THEN 'Paid'
                    WHEN water_consumer_active_requests.payment_status = 0 THEN 'Unpaid'
                    ELSE 'UnKnown'
                    END AS payment_status")
        )

            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', 'water_consumer_active_requests.consumer_id')
            ->join('water_consumer_charge_categories', 'water_consumer_charge_categories.id', 'water_consumer_active_requests.charge_catagory_id')
            ->join('wf_roles', 'wf_roles.id', 'water_consumer_active_requests.current_role')
            ->join('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')

            ->where('water_consumer_active_requests.charge_catagory_id', 2)
            ->where('water_consumer_active_requests.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_consumer_active_requests.ulb_id', authUser($req)->ulb_id)
            ->orderby('water_consumer_active_requests.id', 'Desc')
            ->groupBy(
                'water_consumer_active_requests.application_no',
                'water_consumer_active_requests.apply_date',
                'wf_roles.role_name',
                'water_consumer_active_requests.corresponding_mobile_no',
                'water_consumer_owners.applicant_name',
                'water_consumer_owners.mobile_no',
                'water_consumer_active_requests.payment_status',
                'water_consumer_active_requests.id',
                'water_consumer_charge_categories.charge_category',
                'water_consumers.holding_no',
                'water_consumer_owners.city',
                'water_consumers.ward_mstr_id',
            );
    }

    public function updateUploadStatus($applicationId, $status)
    {
        return  WaterConsumerActiveRequest::where('id', $applicationId)
            ->where('status', true)
            ->update([
                "doc_upload_status" => $status
            ]);
    }

    public function getDetailsByApplicationNo($applicationNo)
    {
        return WaterConsumerActiveRequest::select(
            'water_consumers.id',
            'water_consumer_active_requests.id as applicationId',
            'water_consumer_active_requests.application_no',
            'water_consumers.ward_id',
            'water_consumers.address',
            'water_consumers.saf_no',
            'water_consumers.payment_status',
            'water_consumers.property_no as holding_no',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardianName")
        )
            ->join('water_consumers', 'water_consumers.id', 'water_consumer_active_requests.consumer_id')
            ->leftjoin('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumer_active_requests.consumer_id')
            ->leftjoin('water_consumers', 'water_consumers.id', '=', 'water_consumers.apply_connection_id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_id')
            // ->where('water_consumers.status', true)
            ->where('water_consumer_active_requests.application_no', 'LIKE', '%' . $applicationNo . '%')
            ->where('water_consumers.ulb_id', 2)
            ->whereIn('water_consumers.status', [1, 4])
            ->orderby('water_consumer_active_requests.id', 'DESC')
            ->groupBy(
                'water_consumers.id',
                'water_consumer_active_requests.id',
                'water_consumer_active_requests.application_no',
                'water_consumers.ward_id',
                'water_consumers.address',
                'water_consumers.saf_no',
                'water_consumers.payment_status',
                'water_consumers.property_no',
                'ulb_ward_masters.ward_name'
            );
    }

    public function getMeterDetailsByConsumerIdV2($consumerId)
    {
        return WaterConsumerMeter::select(
            'subquery.initial_reading as ref_initial_reading',
            'subquery.created_at as ref_created_at', // Include created_at from subquery
            DB::raw("concat(relative_path, '/', meter_doc) as doc_path"),
            'water_consumer_meters.*'
        )
            ->leftJoinSub(
                DB::connection('pgsql_water')
                    ->table('water_consumer_initial_meters')
                    ->select('consumer_id', 'initial_reading', DB::raw('DATE(created_at) as created_at')) // Format created_at in the subquery
                    ->where('consumer_id', '=', $consumerId)
                    ->orderBy('id', 'desc')
                    ->skip(1) // Skip the most recent record
                    ->take(1), // Take the second latest record
                'subquery',
                function ($join) {
                    $join->on('subquery.consumer_id', '=', 'water_consumer_meters.consumer_id');
                }
            )
            ->where('water_consumer_meters.consumer_id', $consumerId)
            ->where('water_consumer_meters.status', 1)
            ->orderByDesc('water_consumer_meters.id');
    }


    public function fullWaterDetails($request)
    {
        return  WaterConsumerActiveRequest::select(
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.consumer_id',
            'water_consumer_active_requests.id as applicationId',
            'water_consumer_active_requests.ward_mstr_id',
            'water_consumer_active_requests.ulb_id',
            'water_consumers.consumer_no',
            'water_consumer_active_requests.status',
            'water_consumers.user_type',
            'water_consumer_active_requests.apply_date',
            'water_consumer_active_requests.charge_catagory_id',
            'water_consumers.address',
            'water_consumers.category',
            'water_consumer_active_requests.application_no',
            'water_consumers.pin',
            'water_consumer_meters.meter_no as oldMeterNo',
            'water_consumer_active_requests.current_role',
            'water_consumer_active_requests.workflow_id',
            'water_consumer_active_requests.last_role_id',
            'water_consumer_active_requests.doc_upload_status',
            'water_property_type_mstrs.property_type',
            // 'water_param_pipeline_types.pipeline_type',
            // 'zone_masters.zone_name',
            'ulb_masters.ulb_name',
            'water_connection_type_mstrs.connection_type',
            'wf_roles.role_name AS current_role_name',
            'water_connection_type_mstrs.connection_type',
            'ulb_ward_masters.ward_name',
            'water_consumer_charge_categories.charge_category',
            // 'water_consumer_active_requests.new_name',
            // 'water_consumer_active_requests.meter_number as newMeterNo',
            // 'water_consumer_active_requests.property_type as newPropertyType',
            // 'water_consumer_active_requests.category as newCategory',
            'water_consumer_meters.initial_reading',
            'water_consumer_meters.final_meter_reading',
            'water_consumer_initial_meters.initial_reading as finalReading',
        )
            ->distinct()
            ->leftjoin('wf_roles', 'wf_roles.id', '=', 'water_consumer_active_requests.current_role')
            ->leftjoin('ulb_masters', 'ulb_masters.id', '=', 'water_consumer_active_requests.ulb_id')
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', 'water_consumer_active_requests.ward_mstr_id')
            ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->leftjoin('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', 'water_consumers.property_type_id')
            ->join('water_consumer_charge_categories', 'water_consumer_charge_categories.id', 'water_consumer_active_requests.charge_catagory_id')
            ->leftJoin('water_consumer_initial_meters', function ($join) {
                $join->on('water_consumer_initial_meters.consumer_id', '=', 'water_consumers.id')
                    ->where('water_consumer_initial_meters.status', 1);
            })
            ->leftJoin('water_consumer_meters', function ($join) {
                $join->on('water_consumer_meters.consumer_id', '=', 'water_consumers.id')
                    ->where('water_consumer_meters.status', 1);
            })
            ->where('water_consumer_active_requests.id', $request->applicationId)
            ->where('water_consumer_active_requests.status', true);
    }

    public function updateJeVarifications($applicationId)
    {
        WaterConsumerActiveRequest::where('id', $applicationId)
            ->update([
                'je_doc_upload_status' => true,
                'is_field_verified' => true,
            ]);
    }

    /**
     * | Deactivate the doc Upload Status 
     */
    public function updateVerifystatus($metaReqs, $status)
    {
        return  WaterConsumerActiveRequest::where('id', $metaReqs['refTableIdValue'])
            ->where('status', true)
            ->update([
                "verify_status" => $status,
                "emp_details_id" => $metaReqs['user_id']
            ]);
    }
    /**
     * | Deactivate the doc Upload Status 
     */
    public function updateVerifyComplainRequest($metaReqs, $userId)
    {
        return  WaterConsumerActiveRequest::where('id', $metaReqs->applicationId)
            ->where('status', true)
            ->update([
                "verify_status" => 2,
                "emp_details_id" => $userId,
            ]);
    }

    /* 
    * | THis fun which is used to get the consumer details by application id
    * | used in water disconnection workflow
    * | creadted by : alok
    */
    public function getConsumerAllDetails($applicationId)
    {
        return WaterConsumerActiveRequest::select(
                'water_consumer_active_requests.*', 
                'water_consumers.holding_no',
                'water_consumers.consumer_no',
                'water_consumer_owners.applicant_name',
                'water_owner_type_mstrs.owner_type',
                'water_consumers.address',
                'water_param_pipeline_types.pipeline_type',
                'water_property_type_mstrs.property_type',
                'water_connection_through_mstrs.connection_through',
                'water_connection_type_mstrs.connection_type',
                'water_consumers.area_sqft',
                'water_consumers.category',
                'water_consumers.flat_count',


            )
            ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_consumers.owner_type_id')
            ->join('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_consumers.pipeline_type_id')
            ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_consumers.property_type_id')
            ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
            ->where('water_consumer_active_requests.id', $applicationId)
            ->where('water_consumer_active_requests.status', true)
            ->first();
    }

    public static function rejectedDocuments()
    {
        return self::select(
                'wf_active_documents.id AS doc_id',
                'water_consumer_active_requests.id',
                'water_consumer_active_requests.application_no',
                'water_consumer_active_requests.ward_mstr_id',
                'water_consumer_active_requests.apply_date as AppliedDate',
                'water_consumer_active_requests.apply_from as applyBy',
                'water_consumer_active_requests.current_role as btcBy',
                'wf_roles.role_name as btcBy',
                DB::raw('DATE(wf_active_documents.updated_at) as btcDate'),
                'wf_active_documents.remarks as btcReason',
                'water_consumers.consumer_no', 
                'water_consumers.holding_no', 
                'wccc.charge_category',
                'water_consumer_owners.applicant_name',
                'water_consumer_owners.mobile_no',
                'water_consumer_owners.city As address'
            )
            ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'water_consumer_active_requests.id')    
            ->join('water_consumer_charge_categories AS wccc', 'wccc.id', 'water_consumer_active_requests.charge_catagory_id') 
            ->join('wf_roles', 'wf_roles.id', 'water_consumer_active_requests.current_role') 
            ->where('wf_active_documents.verify_status', 2)
            ->where('wf_active_documents.workflow_id', 193)
            ->where('water_consumer_active_requests.parked', true);
    }
    

    public static function getRejectedAppDetails($consumerId)
    {
        return self::select(
            
            'wf_active_documents.id AS doc_id',
            'water_consumer_active_requests.id',
            'water_consumer_active_requests.application_no',
            'water_consumers.consumer_no',            
            'wf_active_documents.doc_code',
            'wf_active_documents.remarks',
            'wccc.charge_category',
            'water_consumer_owners.applicant_name',
            'water_consumers.address',
            'water_consumers.area_sqft',
            'water_consumers.category',
            'water_owner_type_mstrs.owner_type',
            'water_param_pipeline_types.pipeline_type',
            'water_property_type_mstrs.property_type',
            'water_connection_through_mstrs.connection_through',
            'water_connection_type_mstrs.connection_type',
            'water_consumers.area_sqft',
        
        )
        ->join('water_consumers', 'water_consumers.id', '=', 'water_consumer_active_requests.consumer_id')
        ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
        ->join('wf_active_documents', 'wf_active_documents.active_id', 'water_consumer_active_requests.id')    
        ->join('water_consumer_charge_categories AS wccc', 'wccc.id', 'water_consumer_active_requests.charge_catagory_id')        
        ->join('water_owner_type_mstrs', 'water_owner_type_mstrs.id', '=', 'water_consumers.owner_type_id')
        ->join('water_param_pipeline_types', 'water_param_pipeline_types.id', '=', 'water_consumers.pipeline_type_id')
        ->join('water_property_type_mstrs', 'water_property_type_mstrs.id', '=', 'water_consumers.property_type_id')
        ->join('water_connection_through_mstrs', 'water_connection_through_mstrs.id', '=', 'water_consumers.connection_type_id')
        ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', '=', 'water_consumers.connection_type_id')
        ->where('wf_active_documents.verify_status', 2)
        ->where('wf_active_documents.workflow_id', 193)
        ->where('wf_active_documents.active_id', $consumerId)
        ->get();
    }

    
}