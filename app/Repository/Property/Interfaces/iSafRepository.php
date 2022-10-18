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
    public function applySaf(Request $request);             // Apply For SAF
    public function inbox();                                // Saf Inbox
    public function details(Request $request);              // Get SAF By ID
    public function postEscalate(Request $request);         // Adding SAF application to special Category 
    public function specialInbox();                         // Special Inbox applications
}
