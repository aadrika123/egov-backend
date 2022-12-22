<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PropConcessionDocDtl extends Model
{
    use HasFactory;


    public function docVerify($req)
    {
        $userId = auth()->user()->id;
        $docStatus = PropConcessionDocDtl::find($req->id);
        $docStatus->remarks = $req->docRemarks;
        $docStatus->verified_by_emp_id = $userId;
        $docStatus->verified_on = Carbon::now();
        $docStatus->updated_at = Carbon::now();

        if ($req->docStatus == 'Verified') {
            $docStatus->verify_status = 1;
        }
        if ($req->docStatus == 'Rejected') {
            $docStatus->verify_status = 2;
        }
        $docStatus->save();
    }
}
