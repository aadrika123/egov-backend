<?php

namespace App\Repository\Trade;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * | Created On-01-10-2022 
 * | Created By-Sandeep Bara
 * ------------------------------------------------------------------------------------------
 * | Interface for Eloquent Property Repository
 */

interface TradeRepository
{
    public function __construct(User $user);
    public function applyApplication(Request $request);
    public function searchLicence(string $licence_no);
}