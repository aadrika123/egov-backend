<?php

namespace App\Repository\Water\Interfaces;

use Illuminate\Http\Request;

interface IWaterNewConnection
{
    public function applyApplication(Request $request);
    public function getCitizenApplication(Request $request);
    public function handeRazorPay(Request $request);
    public function readTransectionAndApl(Request $request);
    public function paymentRecipt($transectionNo);
    public function documentUpload(Request $request);
    public function getUploadDocuments(Request $request);
    public function calWaterConCharge(Request $request);
}