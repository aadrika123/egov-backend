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

interface ITrade
{
    public function __construct();
    public function applyApplication(Request $request);
    public function validate_holding_no(Request $request);
    public function searchLicenceByNo(Request $request);
    public function searchLicence(string $licence_no,$ulb_id);
    public function updateBasicDtl(Request $request);
    public function getLicenceDtl($id);
    public function paybleAmount(Request $request);
    public function paymentRecipt($id, $transectionId);
    public function getCotegoryList();
    public function getFirmTypeList();
    public function getownershipTypeList();
    public function gettradeitemsList();
    public function getAllApplicationType();
    public function inbox(Request $request);
    public function outbox(Request $request);
    public function postNextLevel(Request $request);
}