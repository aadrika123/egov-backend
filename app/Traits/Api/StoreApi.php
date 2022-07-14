<?php

namespace App\Traits\Api;

/**
 * Trait for Saving and updating Api 
 * Created On-29-06-2022 
 * Created By-Anshu Kumar
 * 
 * Code Tested By-
 * Feedback-
 */
trait StoreApi
{
    public function saving($api_master, $request)
    {
        $api_master->description = $request->Description;
        $api_master->category = $request->Category;
        $api_master->end_point = $request->end_point;
        $api_master->usage = $request->Usage;
        $api_master->pre_condition = $request->PreCondition;
        $api_master->request_payload = json_encode($request->RequestPayload);
        $api_master->response_payload = json_encode($request->ResponsePayload);
        $api_master->post_condition = $request->PostCondition;
        $api_master->version = $request->Version;
        $api_master->created_on = $request->CreatedOn;
        $api_master->created_by = $request->CreatedBy;
        $api_master->revision_no = $request->RevisionNo;
        $api_master->discontinued = $request->Discontinued;
        $api_master->save();
        return response()->json(['status' => true, 'Message' => "Successfully Saved"], 200);
    }
}
