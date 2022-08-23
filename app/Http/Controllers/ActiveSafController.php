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
    public function inbox(Request $request)
    {
        $data =$this->Repository->inbox($request->key);
        return $data;
    }
    public function outbox(Request $request)
    {
        $data =$this->Repository->outbox($request->key);
        return $data;
    }
    public function details(Request $request)
    {
        $data =$this->Repository->details($request->id);
        return $data;
    }

    public function special(Request $request)
    {
        $data =$this->Repository->special($request);
        return $data;
    }

    public function specialInbox(Request $request)
    {
        $data =$this->Repository->specialInbox($request->key);
        return $data;
    }

    public function postNextLevel(Request $request)
    {
        $data =$this->Repository->postNextLevel($request);        
        return $data;
    }
    public function propertyObjection(Request $request)
    {
        $data =$this->Repository->propertyObjection($request);        
        return $data;
    }
    public function propObjectionInbox(Request $request)
    {
        $data =$this->Repository->propObjectionInbox($request->key);
        return $data;
    }
}
