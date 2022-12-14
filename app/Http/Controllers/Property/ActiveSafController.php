<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSafsDoc;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;

class ActiveSafController extends Controller
{
    /**
     * | Created On-08-08-2022 
     * | Created By-Anshu Kumar
     * --------------------------------------------------------------------------------------
     * | Controller regarding with SAF Module
     */

    // Initializing function for Repository
    protected $saf_repository;
    public function __construct(iSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
    }

    // Get All master data in saf
    public function masterSaf()
    {
        return $this->Repository->masterSaf();
    }

    //  Function for applying SAF
    public function applySaf(Request $request)
    {
        // return $request->all();
        return $this->Repository->applySaf($request);
    }

    // Document Upload By Citizen Or JSK
    public function documentUpload(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer'
        ]);
        return $this->Repository->documentUpload($req);
    }

    // Verify Document By Dealing Assistant
    public function verifyDoc(Request $req)
    {
        $req->validate([
            "verifications" => "required"
        ]);
        return $this->Repository->verifyDoc($req);
    }

    // Inbox list
    public function inbox()
    {
        $data = $this->Repository->inbox();
        return $data;
    }
    public function outbox(Request $request)
    {
        $data = $this->Repository->outbox($request);
        return $data;
    }
    public function details(Request $request)
    {
        $data = $this->Repository->details($request);
        return $data;
    }

    // postEscalate
    public function postEscalate(Request $request)
    {
        $data = $this->Repository->postEscalate($request);
        return $data;
    }
    // SAF special Inbox
    public function specialInbox()
    {
        $data = $this->Repository->specialInbox();
        return $data;
    }

    // Post Independent Comment
    public function commentIndependent(Request $request)
    {
        return $this->Repository->commentIndependent($request);
    }

    // Forward to Next Level
    public function postNextLevel(Request $request)
    {
        $data = $this->Repository->postNextLevel($request);
        return $data;
    }

    // Saf Application Approval Or Reject
    public function approvalRejectionSaf(Request $req)
    {
        $req->validate([
            'workflowId' => 'required|integer',
            'roleId' => 'required|integer',
            'safId' => 'required|integer',
            'status' => 'required|integer'
        ]);

        return $this->Repository->approvalRejectionSaf($req);
    }

    // Back to Citizen
    public function backToCitizen(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer',
            'workflowId' => 'required|integer',
            'currentRoleId' => 'required|integer',
            'comment' => 'required|string'
        ]);
        return $this->Repository->backToCitizen($req);
    }

    // Calculate SAF by saf ID
    public function calculateSafBySafId(Request $req)
    {
        return $this->Repository->calculateSafBySafId($req);
    }

    // Generate Payment Order ID
    public function generateOrderId(Request $req)
    {
        $req->validate([
            'id' => 'required|integer',
            'amount' => 'required|numeric',
            'departmentId' => 'required|integer'
        ]);

        return $this->Repository->generateOrderId($req);
    }

    // SAF Payment 
    public function paymentSaf(Request $req)
    {
        return $this->Repository->paymentSaf($req);
    }

    // Generate Payment Receipt
    public function generatePaymentReceipt(Request $req)
    {
        $req->validate([
            'paymentId' => 'required'
        ]);

        return $this->Repository->generatePaymentReceipt($req);
    }

    // Get Property Transactions
    public function getPropTransactions(Request $req)
    {
        return $this->Repository->getPropTransactions($req);
    }

    // Get Property by Holding No
    public function getPropByHoldingNo(Request $req)
    {
        return $this->Repository->getPropByHoldingNo($req);
    }

    // Site Verification
    public function siteVerification(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer',
            'verificationStatus' => 'required|bool',
            'propertyType' => 'required|integer',
            'roadTypeId' => 'required|integer',
            'wardId' => 'required|integer'
        ]);
        return $this->Repository->siteVerification($req);
    }

    // Geo Tagging
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|integer",
            "imagePath.*" => "image|mimes:jpeg,jpg,png,gif|required"
        ]);
        return $this->Repository->geoTagging($req);
    }

    //document verification
    public function safDocStatus(Request $req)
    {
        return $this->Repository->safDocStatus($req);
    }

    // Get TC Verifications
    public function getTcVerifications(Request $req)
    {
        return $this->Repository->getTcVerifications($req);
    }
}
