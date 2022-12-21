<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PropHarvestingDoc extends Model
{
    use HasFactory;


    //citizen doc upload
    public function citizenDocUpload($harvestingDoc, $name, $docName)
    {
        $userId = auth()->user()->id;
        $harvestingDoc->doc_type = $docName;
        $harvestingDoc->relative_path = '/harvesting/' . $docName . '/';
        $harvestingDoc->doc_name = $name;
        $harvestingDoc->status = '1';
        $harvestingDoc->user_id = $userId;
        $harvestingDoc->date = Carbon::now();
        $harvestingDoc->created_at = Carbon::now();
        $harvestingDoc->save();
    }


    //update document
    public function updateDocument($req, $docName, $name)
    {
        PropHarvestingDoc::where('harvesting_id', $req->id)
            ->where('doc_type', $docName)
            ->update([
                'harvesting_id' => $req->id,
                'doc_type' => $docName,
                'relative_path' => ('/harvesting/' . $docName . '/'),
                'doc_name' => $name,
                'status' => 1,
                'verify_status' => 0,
                'remarks' => '',
                'updated_at' => Carbon::now()
            ]);
    }

    //doc verification
    public function docStatus($req)
    {
        $userId = auth()->user()->id;
        $docStatus = PropHarvestingDoc::find($req->id);
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
