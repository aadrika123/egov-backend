<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iConcessionRepository
{

    public function applyConcession(Request $request);                      // apply concession
    public function postHolding(Request $request);                          // post Holding
    public function inbox();                                                // Concession Inbox
    public function outbox();                                               // Concession Outbox List
    public function getDetailsById($req);                                   // Get Concession Details By ID
    public function escalateApplication($req);                              // Escalate the application
    public function specialInbox();                                         // Get escalated application inbox
    public function postNextLevel($req);                                    // post next level application
    public function approvalRejection($req);                                // Approve Reject Application
    public function backTocitizen($req);                                    // Back to citizen of the applications

    public function concessionList();
    public function concessionByid($req);
    public function concessionDocList($req);
    public function concessionDocUpload($req);
    public function concessionDocStatus($req);
}
