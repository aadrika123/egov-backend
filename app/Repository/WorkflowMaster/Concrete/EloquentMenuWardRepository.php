<?php

namespace App\Repository\WorkflowMaster\Concrete;

use App\Repository\WorkflowMaster\iWorkflowMasterRepository;
use Illuminate\Http\Request;
use App\Models\MUlbWard;
use Exception;

/**
 * Repository for Save Edit and View 
 * Parent Controller -App\Controllers\MenuUserController
 * -------------------------------------------------------------------------------------------------
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -------------------------------------------------------------------------------------------------
 * 
 */


class EloquentMenuWardRepository implements iWorkflowMasterRepository
{

    public function create(Request $request)
    {
        try {
            // create
            $device = new MUlbWard;
            $device->ulb_id = $request->UlbId;
            $device->ward_name = $request->WardName;
            $device->old_ward_name = $request->OldWardName;
            $device->user_id = $request->UserId;
            $device->status = $request->Status;
            $device->stamp_date_time = $request->StampDateTime;
            $device->save();
            return response()->json(['Status' => 'Successfully Saved'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * GetAll data
     */
    public function list()
    {
        $data = MUlbWard::orderByDesc('id')->get();
        return $data;
    }


    /**
     * Delete data
     */
    public function delete($id)
    {
        $data = MUlbWard::find($id);
        $data->delete();
        return response()->json('Successfully Deleted', 200);
    }


    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        try {
            $device = MUlbWard::find($request->Id);
            $device->ulb_id = $request->UlbId;
            $device->ward_name = $request->WardName;
            $device->old_ward_name = $request->OldWardName;
            $device->user_id = $request->UserId;
            $device->status = $request->Status;
            $device->stamp_date_time = $request->StampDateTime;
            $device->save();
            return response()->json(['Status' => 'Successfully Updated'], 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * list view by IDs
     */

    public function view($id)
    {
        $data = MUlbWard::find($id);
        if ($data) {
            return response()->json($data, 200);
        } else {
            return response()->json(['Message' => 'Data not found'], 404);
        }
    }
}
