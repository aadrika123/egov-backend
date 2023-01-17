<?php

namespace App\Models;

use App\Repository\Property\Concrete\PropertyBifurcation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Exception;

class CustomDetail extends Model
{
    use HasFactory;
    private $_bifuraction;

    public function __construct()
    {
        $this->_bifuraction = new PropertyBifurcation();
    }

    public function getCustomDetails($request)
    {
        try {
            $customDetails = CustomDetail::select(
                'id',
                'ref_id',
                'ref_type',
                'relative_path',
                'doc_name as docUrl',
                'remarks',
                'type',
                'created_at as date',
                'ref_type as customFor'
            )
                ->orderBy("id", 'desc')
                ->where('ref_id', $request->id)
                ->where('ref_type', $request->customFor)
                ->get();
            $customDetails = $customDetails->map(function ($val) {
                $path = $this->_bifuraction->readDocumentPath($val->relative_path . $val->docUrl);
                $val->docUrl = $path;
                return $val;
            });
            return responseMsg(true, "Successfully Retrieved", $customDetails);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    //post custom details
    public function postCustomDetails($request)
    {
        try {
            $customFor = $request->customFor;
            $customDetails = new CustomDetail;
            $filename = NULL;

            if ($file = $request->file('document')) {
                $filename = time() .  '.' . $file->getClientOriginalExtension();
                $path = storage_path('app/public/custom');
                $file->move($path, $filename);
            }

            $customDetails = new CustomDetail;
            if ($customFor == 'Concession') {
                $customDetails->ref_type = 'Concession';
                $this->saveCustomDetail($request, $filename, $customDetails);
                $customDetails->save();
            }

            if ($customFor == 'SAF') {
                $customDetails->ref_type = 'SAF';
                $this->saveCustomDetail($request, $filename, $customDetails);
                $customDetails->save();
            }

            if ($customFor == 'Objection') {
                $customDetails->ref_type = 'Objection';
                $this->saveCustomDetail($request, $filename, $customDetails);
                $customDetails->save();
            }

            if ($customFor == 'Harvesting') {
                $customDetails->ref_type = 'Harvesting';
                $this->saveCustomDetail($request, $filename, $customDetails);
                $customDetails->save();
            }

            if ($customFor == 'TRADE') {
                $customDetails->ref_type = 'TRADE';
                $this->saveCustomDetail($request, $filename, $customDetails);
                $customDetails->save();
            }

            return responseMsg(true, "Successfully Saved", $customDetails);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    // save custom details
    public function saveCustomDetail($request, $filename, $customDetails)
    {
        if ($request->remarks && $request->document) {

            $customDetails->ref_id = $request->id;
            $customDetails->doc_name = $filename;
            $customDetails->remarks = $request->remarks;
            $customDetails->relative_path = '/custom/';
            $customDetails->type = "both";
        } elseif ($request->document) {

            $customDetails->ref_id = $request->id;
            $customDetails->doc_name = $filename;
            $customDetails->relative_path = '/custom/';
            $customDetails->type = "file";
        } elseif ($request->remarks) {

            $customDetails->ref_id = $request->id;
            $customDetails->remarks = $request->remarks;
            $customDetails->type = "text";
        }
    }
}
