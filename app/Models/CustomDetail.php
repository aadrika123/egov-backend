<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Exception;

class CustomDetail extends Model
{
    use HasFactory;

    public function getCustomDetails()
    {
        try {
            $customDetails = CustomDetail::select('id', 'document', 'remarks', 'type', 'created_at as date')
                ->orderBy("id", 'desc')
                ->get();
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

            if ($file = $request->file('document')) {
                $filename = time() .  '.' . $file->getClientOriginalExtension();
                $path = public_path('custom');
                $file->move($path, $filename);
                $filepath = public_path('custom' . '/' . $filename);
            }

            $customDetails = new CustomDetail;
            if ($customFor == 'Concession') {

                if ($request->remarks && $request->document) {

                    $customDetails->ref_id = $request->id;
                    $customDetails->ref_type = 'Concession';
                    $customDetails->document = $filepath;
                    $customDetails->remarks = $request->remarks;
                    $customDetails->type = "both";
                } elseif ($request->document) {

                    $customDetails->ref_id = $request->id;
                    $customDetails->ref_type = 'Concession';
                    $customDetails->document = $filepath;
                    $customDetails->type = "file";
                } elseif ($request->remarks) {

                    $customDetails->ref_id = $request->id;
                    $customDetails->ref_type = 'Concession';
                    $customDetails->remarks = $request->remarks;
                    $customDetails->type = "text";
                }
                $customDetails->save();
            }
            return responseMsg(true, "Successfully Saved", $customDetails);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
