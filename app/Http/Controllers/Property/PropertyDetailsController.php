<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Illuminate\Http\Request;

class PropertyDetailsController extends Controller
{
    /**
     * | Created On-26-11-2022 
     * | Created By-Sam kerkettta
     * --------------------------------------------------------------------------------------
     * | Controller regarding with Propery Module (Property Details)
     */
    // Construction 
    private $PropertyDetails;
    public function __construct(iPropertyDetailsRepo $PropertyDetails)
    {
        $this->PropertyDetails = $PropertyDetails ;
    }

    // get details of the property filtering with the provided details
    public function getFilterProperty(Request $request)
    {
        return $this->PropertyDetails->getFilterProperty($request);
    }
}
