<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Exception;

class PropActiveSafsDoc extends Model
{
    use HasFactory;

    // Get Document by document id
    public function getSafDocument($id)
    {
        return PropActiveSafsDoc::where('id', $id)
            ->first();
    }


    //document verification
    public function safDocStatus($req)
    {
        try {
            $userId = auth()->user()->id;

            $docStatus = PropActiveSafsDoc::find($req->id);
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

            return responseMsg(true, "Successfully Done", '');
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
