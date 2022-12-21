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
    public function getDetailsById($req);                                   // Get Concession Details By ID
    public function postNextLevel($req);                                    // post next level application
    public function approvalRejection($req);                                // Approve Reject Application
    public function backTocitizen($req);                                    // Back to citizen of the applications

    public function concessionDocUpload($req);
}
