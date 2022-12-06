<?php

namespace App\Http\Controllers\Property;


use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ObjectionController extends Controller
{
    protected $objection;
    public function __construct(iObjectionRepository $objection)
    {
        $this->Repository = $objection;
    }

    //Objection for Clerical Mistake
    public function applyObjection(Request $request)
    {
        $request->validate([
            'propId' => 'required|integer'
        ]);
        return $this->Repository->applyObjection($request);
    }

    //objection type list
    public function objectionType(Request $request)
    {
        return $this->Repository->objectionType($request);
    }

    //ownerDetails
    public function ownerDetails(Request $request)
    {
        return $this->Repository->ownerDetails($request);
    }

    //assesment details
    public function assesmentDetails(Request $request)
    {
        return $this->Repository->assesmentDetails($request);
    }

    //inbox
    public function inbox(Request $request)
    {
        return $this->Repository->inbox($request);
    }

    //inbox
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }

    // Get Details by id
    public function getDetailsById(Request $req)
    {
        $req->validate([
            'id' => 'required|integer'
        ]);

        return $this->Repository->getDetailsById($req);
    }

    // Escalate application 
    public function postEscalate(Request $req)
    {
        $req->validate([
            'escalateStatus' => 'required|bool',
            'objectionId' => 'required|integer'
        ]);

        return $this->Repository->postEscalate($req);
    }

    // List of the Escalated Application
    public function specialInbox()
    {
        return $this->Repository->specialInbox();
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'objectionId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'comment' => 'required'
        ]);

        return $this->Repository->postNextLevel($req);
    }

    // Application Approval Rejection
    public function approvalRejection(Request $req)
    {
        $req->validate([
            "objectionId" => "required",
            "status" => "required"
        ]);
        return $this->Repository->approvalRejection($req);
    }

    // Application back To citizen
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'objectionId' => "required",
            "workflowId" => "required"
        ]);
        return $this->Repository->backToCitizen($req);
    }

    //objection list
    public function objectionList()
    {
        return $this->Repository->objectionList();
    }

    //objection list  by id
    public function objectionByid(Request $req)
    {
        return $this->Repository->objectionByid($req);
    }

    //get document status by id
    public function objectionDocList(Request $req)
    {
        return $this->Repository->objectionDocList($req);
    }

    //post document status
    public function objectionDocStatus(Request $req)
    {
        return $this->Repository->objectionDocStatus($req);
    }
}
