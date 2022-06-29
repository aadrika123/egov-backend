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
        $api_master->Description = $request->description;
        $api_master->Category = $request->category;
        $api_master->EndPoint = $request->EndPoint;
        $api_master->Usage = $request->usage;
        $api_master->PreCondition = $request->preCondition;
        $api_master->RequestPayload = json_encode($request->requestPayload);
        $api_master->ResponsePayload = json_encode($request->responsePayload);
        $api_master->PostCondition = $request->postCondition;
        $api_master->Version = $request->version;
        $api_master->CreatedOn = $request->createdOn;
        $api_master->CreatedBy = $request->createdBy;
        $api_master->RevisionNo = $request->revisionNo;
        $api_master->Discontinued = $request->discontinued;
        $api_master->save();
        return response()->json(['status' => true, 'Message' => "Successfully Saved"], 200);
    }
}
