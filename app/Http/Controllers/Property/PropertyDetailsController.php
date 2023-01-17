<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropActiveSafsOwner;
use App\Repository\Property\Interfaces\iPropertyDetailsRepo;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class PropertyDetailsController extends Controller
{
    /**
     * | Created On-26-11-2022 
     * | Created By-Sam kerkettta
     * | Modified by-Anshu Kumar On-(17/01/2023)
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
    public function propertyListByKey(Request $request)
    {
        try {
            $request->validate([
                'filteredBy' => 'required',
                'applicationNo' => 'required'
            ]);

            $key = $request->filteredBy;
            $applicationNo = $request->applicationNo;
            switch ($key) {
                case ("saf"):
                    $mPropActiveSaf = new PropActiveSaf();
                    $mPropActiveSafOwners = new PropActiveSafsOwner();
                    $application = collect($mPropActiveSaf->getSafDtlsBySafNo($applicationNo));
                    $owners = collect($mPropActiveSafOwners->getOwnerDtlsBySafId($application['id']));
                    $details = $application->merge($owners);
                    break;
                case ("concession"):
                    $mPropConcessions = new PropActiveConcession();
                    $details = $mPropConcessions->getDtlsByConcessionNo($applicationNo);
                    break;
            }
            return responseMsgs(true, "Application Details", remove_null($details), "010501", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010501", "1.0", "", "POST", $request->deviceId ?? "");
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
