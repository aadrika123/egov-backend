<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iSafDemandRepo;
use Illuminate\Http\Request;

class SafDemandController extends Controller
{
    /**
     * | Created On-27-11-2022 
     * | Created By-Anshu Kumar
     * | Created for Get Demandable Data 
     */
    protected $_repo;

    public function __construct(iSafDemandRepo $repository)
    {
        $this->_repo = $repository;
    }

    /** 
     * | Get SAF Demand By SAF Id After Payment
     * | @param request $req
     */
    public function getDemandBySafId(Request $req)
    {
        $req->validate([
            'safId' => 'required|integer'
        ]);

        return $this->_repo->getDemandBySafId($req);
    }
}
