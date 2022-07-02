<?php

namespace App\Repository\Ulbs;

use App\Repository\Ulbs\UlbRepository;
use Illuminate\Http\Request;
use App\Models\UlbMaster;
use Exception;

/**
 * Repository for Save Edit and View Ulbs
 * Parent Controller -App\Controllers\UlbController
 * -------------------------------------------------------------------------------------------------
 * Created On-02-07-2022 
 * Created By-Anshu Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */

class EloquentUlbRepository implements UlbRepository
{
    /**
     * Storing Ulbs in database
     * ----------------------------------------------------------------------------------------------
     * @param Illuminate\Http\Request
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public function store(Request $request)
    {
        //Validation
        $request->validate([
            'UlbName' => 'required|unique:ulb_masters'
        ]);

        try {
            // Store
            $ulb = new UlbMaster;
            $ulb->UlbName = $request->UlbName;
            $ulb->UlbType = $request->ulbType;
            $ulb->Description = $request->description;
            $ulb->IncorporationDate = $request->incorporationDate;
            $ulb->save();
            return response()->json(['Status' => 'Successfully Saved'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Edit Ulb Master
     * ---------------------------------------------------------------------------------
     * @return Illuminate\Http\Request
     * @return Illuminate\Http\Request $request
     * ---------------------------------------------------------------------------------
     * Checking Validation
     * Check if Ulb is same as previous
     *          (if true=change other fields)
     *          (if false)
     * Check if the ulb already existing or not
     *          if(Existing) exit;
     *          if(not existing)
     * Update
     */

    public function edit(Request $request, $id)
    {
        //Validation
        $request->validate([
            'UlbName' => 'required'
        ]);
        try {
            $ulb = UlbMaster::find($request->id);
            $stmt = $ulb->UlbName == $request->UlbName;
            // Changing other fields
            if ($stmt) {
                $ulb->Description = $request->description;
                $ulb->IncorporationDate = $request->incorporationDate;
                $ulb->save();
                return response()->json(['Status' => 'Successfully Saved'], 200);
            }
            if (!$stmt) {
                $stmt1 = UlbMaster::where('UlbName', '=', $request->UlbName)->first();
                // Checking already existing
                if ($stmt1) {
                    return response()->json(['Message' => 'UlbName is already Existing'], 400);
                }
                if (!$stmt1) {
                    $ulb->UlbName = $request->UlbName;
                    $ulb->UlbType = $request->ulbType;
                    $ulb->Description = $request->description;
                    $ulb->IncorporationDate = $request->incorporationDate;
                    $ulb->save();
                    return response()->json(['Status' => 'Successfully Saved'], 200);
                }
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * View Ulbs by IDs
     * @param $id
     * @return response
     */

    public function view($id)
    {
        $data = UlbMaster::find($id);
        if ($data) {
            return response()->json($data, 302);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
