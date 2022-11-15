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
    public function addRecord(Request $request);
    public function paymentCounter(Request $request);
    public function isvalidateHolding(Request $request);
    public function searchLicenceByNo(Request $request);
    public function searchLicence(string $licence_no,$ulb_id);
    public function readApplication(Request $request);
    public function updateBasicDtl(Request $request);
    public function documentUpload(Request $request);
    public function documentVirify(Request $request);
    public function readLicenceDtl($id);
    public function readDenialdtlbyNoticno(Request $request);
    public function getPaybleAmount(Request $request);
    public function readPaymentRecipt($id, $transectionId);
    public function getCotegoryList();
    public function getFirmTypeList();
    public function getownershipTypeList();
    public function gettradeitemsList();
    public function getAllApplicationType();
    public function inbox(Request $request);
    public function outbox(Request $request);
    public function postNextLevel(Request $request);
    public function provisionalCertificate($id);
    public function licenceCertificate($id);
    public function addDenail(Request $request);
    public function denialInbox(Request $request);
    public function denialView($id,$mailID,Request $request);
    public function reports(Request $request);
}