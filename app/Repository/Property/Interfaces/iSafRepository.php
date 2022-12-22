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
    public function details($request);                      // Get SAF By ID
    public function approvalRejectionSaf($req);             // Approve or Reject The Application
    public function calculateSafBySafId($req);              // SAF Calculation by Existing SAF ID
    public function generateOrderId($req);                  // Generate Payment Order ID
    public function paymentSaf($req);                       // SAF Payment
    public function generatePaymentReceipt($req);           // Generate Payment Receipt
    public function getPropByHoldingNo($req);               // Get Property Details by Holding no
    public function siteVerification($req);                 // Site Verification for Agency TC and Ulb TC
    public function geoTagging($req);                       // Geo Tagging By Level
    public function safDocStatus($req);                     // Document Verification
    public function getTcVerifications($req);               // Get Tc Verifications
}
