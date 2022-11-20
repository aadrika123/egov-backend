<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iConcessionRepository;
use Illuminate\Http\Request;


class ConcessionController extends Controller
{
    /**
     * | Created On-15-11-2022 
     * | Created By-Mrinal Kumar
     * --------------------------------------------------------------------------------------
     * | Controller for Concession
     */

    // Initializing function for Repository
    protected $concession_repository;
    public function __construct(iConcessionRepository $concession_repository)
    {
        $this->Repository = $concession_repository;
    }


    //apply concession
    public function applyConcession(Request $request)
    {
        return $this->Repository->applyConcession($request);
    }

    //post Holding
    public function postHolding(Request $request)
    {
        $request->validate([
            'holdingNo' => 'required'
        ]);
        return $this->Repository->postHolding($request);
    }
}
