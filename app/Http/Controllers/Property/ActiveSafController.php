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
    public function postIndependentComment(Request $request)
    {
        return $this->Repository->postIndependentComment($request);
    }

    // Forward to Next Level
    public function postNextLevel(Request $request)
    {
        $data = $this->Repository->postNextLevel($request);
        return $data;
    }

    // Saf Application Approval Or Reject
    public function safApprovalRejection(Request $req)
    {
        return $this->Repository->safApprovalRejection($req);
    }

    // Back to Citizen
    public function backToCitizen(Request $req)
    {
        return $this->Repository->backToCitizen($req);
    }

    // SAF Payment 
    public function safPayment(Request $req)
    {
        return $this->Repository->safPayment($req);
    }
}
