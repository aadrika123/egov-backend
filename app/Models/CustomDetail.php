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
            $customDetails = CustomDetail::select('id', 'document', 'remarks', 'type')

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
            $customDetails = new CustomDetail;


            if ($file = $request->file('document')) {

                $name = time() . '.' . 'pdf';

                $path = Storage::put('public' . '/' . $name, $file);
                $url = asset("/storage/$name");
                return response()->json(["status" => true, "data" => $url]);
            }
            $customDetails->document = $request->document;
            $customDetails->remarks = $request->remarks;
            $customDetails->save();


            // if($request->document){
            //     $customDetails = new CustomDetail;
            // $customDetails->document = $request->document;
            // }

            return responseMsg(true, "Successfully Saved", $customDetails);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
