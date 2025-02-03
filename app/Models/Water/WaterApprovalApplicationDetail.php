<?php

namespace App\Models\Water;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WaterApprovalApplicationDetail extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * |------------------------- Get the Approved Applecaton Details ---------------------------|
     * | @param request
     */
    public function getApprovedApplications()
    {
        $approvedWater = WaterApprovalApplicationDetail::orderByDesc('id');
        return $approvedWater;
    }


    /**
     * |
     */
    public function getApplicationRelatedDetails()
    {
        return WaterApprovalApplicationDetail::join('ulb_masters', 'ulb_masters.id', '=', 'water_approval_application_details.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_approval_application_details.ward_id')
            ->orderByDesc('id');
    }


    /**
     * | Get
     */
    public function getApprovedApplicationById($applicationId)
    {
        return WaterApprovalApplicationDetail::select(
            'water_approval_application_details.id',
            'water_approval_application_details.application_no',
            'water_approval_application_details.ward_id',
            'water_approval_application_details.address',
            'water_approval_application_details.holding_no',
            'water_approval_application_details.saf_no',
            'ulb_ward_masters.ward_name',
            'ulb_masters.ulb_name',
            DB::raw("string_agg(water_approval_applicants.applicant_name,',') as applicantName"),
            DB::raw("string_agg(water_approval_applicants.mobile_no::VARCHAR,',') as mobileNo"),
            DB::raw("string_agg(water_approval_applicants.guardian_name,',') as guardianName"),
        )
            ->join('ulb_masters', 'ulb_masters.id', '=', 'water_approval_application_details.ulb_id')
            ->join('water_approval_applicants', 'water_approval_applicants.application_id', '=', 'water_approval_application_details.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_approval_application_details.ward_id')
            ->where('water_approval_application_details.status', true)
            ->where('water_approval_application_details.id', $applicationId)
            ->groupBy(
                'water_approval_application_details.saf_no',
                'water_approval_application_details.holding_no',
                'water_approval_application_details.address',
                'water_approval_application_details.id',
                'water_approval_applicants.application_id',
                'water_approval_application_details.application_no',
                'water_approval_application_details.ward_id',
                'water_approval_application_details.ulb_id',
                'ulb_ward_masters.ward_name',
                'ulb_masters.id',
                'ulb_masters.ulb_name'
            );
    }

    /**
     * | Get approved appliaction using the id 
     */
    public function getApproveApplication($applicationId)
    {
        return WaterApprovalApplicationDetail::where('id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();
    }

    public function getApplicationWithStatus($request)
    {
        $user = Auth()->user();
        $ulbId = $user->ulb_id ?? null;
        $perPage = $request->perPage ?: 10;
        $dateFrom = $request->dateFrom ?: Carbon::now()->format('Y-m-d');
        $dateUpto = $request->dateUpto ?: Carbon::now()->format('Y-m-d');

        $approved = WaterApprovalApplicationDetail::select(
            'water_approval_application_details.id',
            'water_approval_application_details.application_no',
            'water_approval_application_details.holding_no',
            'water_approval_application_details.connection_type_id',
            'water_approval_application_details.property_type_id',
            DB::raw("TO_CHAR(water_approval_application_details.apply_date, 'DD-MM-YYYY') as application_date"),
            'ulb_ward_masters.ward_name as ward_no',
            'water_approval_application_details.ulb_id',
            'water_connection_type_mstrs.connection_type',
            DB::raw("'Approve' as application_status"),
            DB::raw("STRING_AGG(water_approval_applicants.applicant_name, ', ') as applicant_names")                 // Aggregate multiple applicants
        )
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'water_approval_application_details.ward_id')
            ->join('water_approval_applicants', 'water_approval_applicants.application_id', 'water_approval_application_details.id')
            ->join('water_connection_type_mstrs', 'water_connection_type_mstrs.id', 'water_approval_application_details.connection_type_id')
            ->where('water_approval_application_details.ulb_id', $ulbId)
            ->whereBetween('apply_date', [$dateFrom, $dateUpto])
            ->groupBy(
                'water_approval_application_details.id',
                'water_approval_application_details.application_no',
                'water_approval_application_details.holding_no',
                'water_approval_application_details.connection_type_id',
                'water_approval_application_details.property_type_id',
                'water_approval_application_details.apply_date',
                'ulb_ward_masters.ward_name',
                'water_approval_application_details.ulb_id',
                'water_connection_type_mstrs.connection_type'
            );

        if ($request->wardId) {
            $approved->where('ulb_ward_masters.id', $request->wardId);
        }
        if ($request->connectionType) {
            $approved->where('water_approval_application_details.connection_type_id', $request->connectionType);
        }
        if ($request->propertyType) {
            $approved->where('water_approval_application_details.property_type_id', $request->propertyType);
        }

        $data = $approved->paginate($perPage);

        return [
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'data' => $data->items(),
            'total' => $data->total()
        ];
    }
}
