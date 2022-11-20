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
            'id' => 'required'
        ]);

        return $this->Repository->getDetailsById($req);
    }

    // Escalate application by application id
    public function escalateApplication(Request $req)
    {
        $req->validate([
            'id' => 'required',
            'escalateStatus' => 'required'
        ]);
        return $this->Repository->escalateApplication($req);
    }

    // special inbox list
    public function specialInbox()
    {
        return $this->Repository->specialInbox();
    }

    // Post Next Level Application
    public function postNextLevel(Request $req)
    {
        $req->validate([
            'concessionId' => 'required',
            'senderRoleId' => 'required',
            'receiverRoleId' => 'required',
            'comment' => 'required'
        ]);
        return $this->Repository->postNextLevel($req);
    }
}
