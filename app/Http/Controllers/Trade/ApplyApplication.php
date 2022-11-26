<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Trade\ITrade;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\Trade\addRecorde;
use App\Http\Requests\Trade\paymentCounter;
use App\Http\Requests\Trade\reqPaybleAmount;
use App\Http\Requests\Trade\reqInbox;
use App\Http\Requests\Trade\requpdateBasicDtl;

class ApplyApplication extends Controller
{

    /**
     * | Created On-01-10-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Trade Module
     */

    // Initializing function for Repository
    private $Repository;
    public function __construct(ITrade $TradeRepository)
    {
        // $this->middleware(function ($request, $next) use($TradeRepository) {          
        //     $virtualRole = User::first();
        //     $user = auth()->user() ?? $virtualRole;
        //     $TradeRepository->__construct($user);
        //     $this->Repository = $TradeRepository ;
        //     return $next($request);
        // });
        $this->Repository = $TradeRepository ;
    }
    public function applyApplication(addRecorde $request)
    {        
        return $this->Repository->addRecord($request);
    }
    public function paybleAmount(reqPaybleAmount $request)
    {      
        return $this->Repository->getPaybleAmount($request);
    }
    public function validateHoldingNo(Request $request)
    {
        return $this->Repository->isvalidateHolding($request);
    }
    public function paymentRecipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        return $this->Repository->readPaymentRecipt($id,$transectionId);
    }
    public function updateLicenseBo(requpdateBasicDtl $request)
    {
        return $this->Repository->updateLicenseBo($request);
    }
    public function updateBasicDtl(requpdateBasicDtl $request)
    {
        return $this->Repository->updateBasicDtl($request);
    }
    public function documentUpload(Request $request)
    {
        return $this->Repository->documentUpload($request);
    }
    public function documentVirify(Request $request)
    {
        return $this->Repository->documentVirify($request);
    }
    public function getLicenceDtl(Request $request)
    {
        return $this->Repository->readLicenceDtl($request->id);
    }
    public function getDenialDetails(Request $request)
    {
        return $this->Repository->readDenialdtlbyNoticno($request);
    }
    public function searchLicence(Request $request)
    {
        return $this->Repository->searchLicenceByNo($request);
    }
    public function readApplication(Request $request)
    {
        return $this->Repository->readApplication($request);
    }
    public function inbox(reqInbox $request)
    {
        return $this->Repository->inbox($request);
    }
    public function outbox(Request $request)
    {
        return $this->Repository->outbox($request);
    }
    public function postNextLevel(Request $request)
    {
        return $this->Repository->postNextLevel($request);
    }
    public function addIndependentComment(Request $request)
    {
        return $this->Repository->addIndependentComment($request);
    }
    public function readIndipendentComment(Request $request)
    {
        return $this->Repository->readIndipendentComment($request);
    }
    public function paymentCounter(paymentCounter $request)
    {
        return $this->Repository->paymentCounter($request);
    }
    public function provisionalCertificate(Request $request)
    {
        return $this->Repository->provisionalCertificate($request->id);
    }
    public function licenceCertificate(Request $request)
    {
        return $this->Repository->licenceCertificate($request->id);
    }
    public function applyDenail(Request $request)
    {
        return $this->Repository->addDenail($request);
    }
    public function denialInbox(Request $request)
    {
        return $this->Repository->denialInbox($request);
    }
    public function denialview(Request $request)
    {
        $id = $request->id;
        $mailID = $request->mailID;
        return $this->Repository->denialView($id,$mailID,$request);
    }
    public function reports(Request $request)
    {
        return $this->Repository->reports($request);
    }
}