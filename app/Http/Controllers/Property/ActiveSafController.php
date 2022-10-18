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
        $data = $this->Repository->outbox($request->key);
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

    public function postNextLevel(Request $request)
    {
        $data = $this->Repository->postNextLevel($request);
        return $data;
    }
    public function getPropIdByWardNoHodingNo(Request $request)
    {
        $data = $this->Repository->getPropIdByWardNoHodingNo($request);
        return $data;
    }

    public function setWorkFlowForwordBackword(Request $request)
    {
        $data = $this->Repository->setWorkFlowForwordBackword($request);
        return $data;
    }
}
