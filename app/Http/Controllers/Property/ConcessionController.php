<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Illuminate\Http\Request;


class ConcessionController extends Controller
{
    /**
     * | Created On-15-11-2022 
     * | Created By-Mrinal Kumar
     * --------------------------------------------------------------------------------------
     * | Controller for Concession
     */

    // Initializing function for Repository
    protected $concession_repository;
    public function __construct(iConcessionRepository $concession_repository)
    {
        $this->Repository = $concession_repository;
    }


    //apply concession
    public function applyConcession(Request $request)
    {
        return $this->Repository->applyConcession($request);
    }

    //post Holding
    public function postHolding(Request $request)
    {
        return $this->Repository->postHolding($request);
    }

    // Inbox
    public function inbox()
    {
        return $this->Repository->inbox();
    }

    // Outbox
    public function outbox()
    {
        return $this->Repository->outbox();
    }

    // Get Concession Details by ID
    public function getDetailsById(Request $req)
    {
        $req->validate([
            'id' => 'required',
            'status' => 'required'
        ]);

        return $this->Repository->getDetailsById($req);
    }

    // Escalate application by application id
    public function escalateApplication(Request $req)
    {
        $req->validate([
            'id' => 'required'
        ]);
        return $this->Repository->escalateApplication($req);
    }
}
