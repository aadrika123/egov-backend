<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\Controller;
use App\Repository\Trade\ITrade;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

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
    public function applyApplication(Request $request)
    {        
        return $this->Repository->createApplication($request);
    }
    public function paybleAmount(Request $request)
    {      
        return $this->Repository->paybleAmount($request);
    }
    public function validate_holding_no(Request $request)
    {
        return $this->Repository->validate_holding_no($request);
    }
    public function paymentRecipt(Request $request)
    {
        $id = $request->id;
        $transectionId =  $request->transectionId;
        return $this->Repository->paymentRecipt($id,$transectionId);
    }
    public function updateBasicDtl(Request $request)
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
        return $this->Repository->getLicenceDtl($request->id);
    }
    public function getDenialDetails(Request $request)
    {
        return $this->Repository->getDenialDetails($request);
    }
    public function searchLicence(Request $request)
    {
        return $this->Repository->searchLicenceByNo($request);
    }
    public function readApplication(Request $request)
    {
        return $this->Repository->readApplication($request);
    }
    public function inbox(Request $request)
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
    public function paymentCounter(Request $request)
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
        return $this->Repository->applyDenail($request);
    }
    public function denialInbox(Request $request)
    {
        return $this->Repository->denialInbox($request);
    }
    public function denialview(Request $request)
    {
        $id = $request->id;
        $mailID = $request->mailID;
        return $this->Repository->denialview($id,$mailID,$request);
    }
    public function reports(Request $request)
    {
        return $this->Repository->reports($request);
    }
}