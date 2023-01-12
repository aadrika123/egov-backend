<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class PropActiveConcession extends Model
{
    use HasFactory;

    /**
     * | Get Concession Details
     */
    public function getDetailsById($id)
    {
        $details = PropActiveConcession::select(
            'prop_active_concessions.*',
            'prop_active_concessions.applicant_name as owner_name',
            's.*',
            'u.ward_name as ward_no',
            'p.property_type'
        )
            ->join('prop_properties as s', 's.id', '=', 'prop_active_concessions.property_id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 's.ward_mstr_id')
            ->join('ref_prop_types as p', 'p.id', '=', 's.prop_type_mstr_id')
            ->where('prop_active_concessions.id', $id)
            ->first();
        return $details;
    }

    /**
     * |-------------------------- details of all concession according id -----------------------------------------------
     * | @param request
     */
    public function allConcession($request)
    {
        $concession = PropActiveConcession::where('id', $request->id)
            ->get();
        return $concession;
    }

    //concession number generation
    public function concessionNo($id)
    {
        try {
            $count = PropActiveConcession::where('id', $id)
                ->select('id')
                ->get();
            $concessionNo = 'CON' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $concessionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function escalate($req)
    {
        $userId = auth()->user()->id;
        if ($req->escalateStatus == 1) {
            $concession = PropActiveConcession::find($req->id);
            $concession->is_escalate = 1;
            $concession->escalated_by = $userId;
            $concession->save();
            return "Successfully Escalated the application";
        }
        if ($req->escalateStatus == 0) {
            $concession = PropActiveConcession::find($req->id);
            $concession->is_escalate = 0;
            $concession->escalated_by = null;
            $concession->save();
            return "Successfully De-Escalated the application";
        }
    }
}
