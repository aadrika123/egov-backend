<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-10-08-2022 
 * | Created By-Anshu Kumar
 * ------------------------------------------------------------------------------------------
 * | Interface for Eloquent Saf Repository
 */
interface iSafRepository
{
    public function masterSaf();                            // Get all master data while applying Saf
    public function applySaf(Request $request);             // Apply For SAF
    public function inbox();                                // Saf Inbox
    public function outbox();                               // Saf Outbox
    public function details(Request $request);              // Get SAF By ID
    public function postEscalate($request);                 // Adding SAF application to special Category 
    public function specialInbox();                         // Special Inbox applications
    public function postIndependentComment($request);       // Comment For the SAF Application
    public function postNextLevel($request);                // Forward Or Backward to next Level
    public function safApprovalRejection($req);             // Approve or Reject The Application
    public function backToCitizen($req);                    // Back To Citizen
}
