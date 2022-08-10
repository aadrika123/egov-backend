<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\SAF\EloquentSafRepository;

class ActiveSafController extends Controller
{
    /**
     * | Created On-08-08-2022 
     * | Created By-Anshu Kumar
     * --------------------------------------------------------------------------------------
     * | Controller regarding with SAF Module
     */

    // Initializing function for Repository
    protected $saf_repository;
    public function __construct(EloquentSafRepository $saf_repository)
    {
        $this->Repository = $saf_repository;
    }

    //  Function for applying SAF
    public function applySaf(Request $request)
    {
        return $this->Repository->applySaf($request);
    }
}
