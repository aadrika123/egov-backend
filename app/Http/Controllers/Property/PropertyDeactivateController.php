<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Concrete\PropertyDeactivate;
use App\Repository\Property\Interfaces\IPropertyDeactivate;
use Illuminate\Http\Request;

class PropertyDeactivateController extends Controller
{
     /**
     * | Created On-19-11-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Deactivation)
     */
    private $Repository;
    public function __construct(IPropertyDeactivate $PropertyDeactivate)
    {
        $this->Repository = $PropertyDeactivate ;
    }
    public function readHoldigbyNo(Request $request)
    {
        return $this->Repository->readHoldigbyNo($request);
    }
    public function deactivatProperty(Request $request)
    {
        $propId = $request->id;
        return $this->Repository->deactivatProperty($propId,$request);
    }
    public function inbox(Request $request)
    {
        return $this->Repository->inbox($request);
    }
    public function postNextLevel(Request $request)
    {
        return $this->Repository->postNextLevel($request);
    }
    public function readDeactivationReq(Request $request)
    {
        return $this->Repository-> readDeactivationReq($request);
    }
}
