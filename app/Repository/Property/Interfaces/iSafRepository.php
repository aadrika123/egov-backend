<?php

namespace App\Repository\Property\Interfaces;

/**
 * | Created On-10-08-2022 
 * | Created By-Anshu Kumar
 * ------------------------------------------------------------------------------------------
 * | Interface for Eloquent Saf Repository
 */
interface iSafRepository
{
    public function masterSaf();                            // Get all master data while applying Saf
    public function applySaf($request);                     // Apply For SAF
    public function documentUpload($req);                   // Document Upload By Citizen or JSK
    public function verifyDoc($req);                        // Verify Document By Dealing Assistant
    public function inbox();                                // Saf Inbox
    public function outbox();                               // Saf Outbox
    public function details($request);                      // Get SAF By ID
    public function postEscalate($request);                 // Adding SAF application to special Category 
    public function specialInbox();                         // Special Inbox applications
    public function commentIndependent($request);           // Comment For the SAF Application
    public function postNextLevel($request);                // Forward Or Backward to next Level
    public function approvalRejectionSaf($req);             // Approve or Reject The Application
    public function backToCitizen($req);                    // Back To Citizen
    public function calculateSafBySafId($req);              // SAF Calculation by Existing SAF ID
    public function generateOrderId($req);                  // Generate Payment Order ID
    public function paymentSaf($req);                       // SAF Payment
    public function getPropTransactions($req);              // Get Property Transactions
    public function getPropByHoldingNo($req);               // Get Property Details by Holding no
    public function siteVerification($req);                 // Site Verification for Agency TC and Ulb TC
    public function geoTagging($req);                       // Geo Tagging By Level
    public function safDocumentUpload($request);
    public function getUploadDocuments($request);
}
