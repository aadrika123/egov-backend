<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
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
        $request->validate([
            'id' => 'required|integer'
        ]);

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

    // Get Property Transactions
    public function getPropTransactions(Request $req)
    {
        return $this->Repository->getPropTransactions($req);
    }

    // Get Property by Holding No
    public function getPropByHoldingNo(Request $req)
    {
        $req->validate([
            'wardId' => 'required|integer',
            'holdingNo' => 'required'
        ]);
        return $this->Repository->getPropByHoldingNo($req);
    }

    // Site Verification
    public function siteVerification(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer',
            'verificationStatus' => 'required|bool'
        ]);
        return $this->Repository->siteVerification($req);
    }

    // Geo Tagging
    public function geoTagging(Request $req)
    {
        $req->validate([
            "safId" => "required|integer"
        ]);
        return $this->Repository->geoTagging($req);
    }
}
