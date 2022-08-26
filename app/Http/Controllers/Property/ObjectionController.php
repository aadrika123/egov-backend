<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\EloquentObjection;
use Illuminate\Http\Request;

class ObjectionController extends Controller
{
    /**
     * | Created On-24-08-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Property Objection 
     */

    // Initializing function for Repository
    protected $objection;
    public function __construct(EloquentObjection $objection)
    {
        $this->Repository = $objection;
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
    public function propObjectionOutbox(Request $request)
    {
        $data =$this->Repository->propObjectionOutbox($request->key);
        return $data;
    }
    public function specialObjectionInbox(Request $request)
    { 
        $data =$this->Repository->specialObjectionInbox($request->key);
        return $data;
    }
}
