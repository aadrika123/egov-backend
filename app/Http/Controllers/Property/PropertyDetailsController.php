<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveSaf;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Exception;
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
    private $propertyDetails;
    public function __construct(iPropertyDetailsRepo $propertyDetails)
    {
        $this->propertyDetails = $propertyDetails;
    }

    // get details of the property filtering with the provided details
    public function getFilterProperty(Request $request)
    {
        try{
            $request->validate([
                'filteredBy' => 'required'
            ]);
        return $this->propertyDetails->getFilterProperty($request);
        }
        catch(Exception $e)
        {
            return responseMsg( false,$e->getMessage(),"");
        }
    }

    // get details of the diff operation in property
    public function getFilterSafs(Request $request)
    {
        return $this->propertyDetails->getFilterSafs($request);
    }

    // All saf no from Active Saf no
    /**
     | ----------flag
     */
    public function getListOfSaf()
    {
        $getSaf = new PropActiveSaf();
        return $getSaf->allNonHoldingSaf();
    }

    // All the listing of the Details of Applications According to the respective Id
    public function getUserDetails(Request $request)
    {
        return $this->propertyDetails->getUserDetails($request);
    }
}
