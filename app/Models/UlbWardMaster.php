<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class UlbWardMaster extends Model
{
    use HasFactory;

    public function getAllWards()
    {
        $ulbId = auth()->user()->ulb_id;
        try {
            $wards = UlbWardMaster::where('ulb_id', $ulbId)
                ->select('id', 'ward_name as wardName')
                ->get();
            return responseMsg(true, "Successfully Retrieved", $wards);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
