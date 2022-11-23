<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Concrete\PropertyDeactivate;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use Illuminate\Http\Request;

class PropertyBifurcationController extends Controller
{
    /**
     * | Created On-23-11-2022 
     * | Created By-Sandeep Bara
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Bifurcation Procese)
     */

    private $Repository;
    private $Property;
    public function __construct(IPropertyBifurcation $Repository)
    {
        $this->Repository = $Repository ;
        $this->Property = new PropertyDeactivate();
    }
    public function readHoldigbyNo(Request $request)
    {
        return $this->Property->readHoldigbyNo($request);
    }
    public function addRecord(Request $request)
    {
        return $this->Repository->addRecord($request);
    }
}
