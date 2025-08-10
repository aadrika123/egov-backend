<?php

namespace App\Pipelines\CareTakers;

use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Trade\TradeLicence;
use App\Models\Water\WaterConsumer;
use Carbon\Carbon;
use Closure;
use Exception;

/**
 * | Created On-21-04-2023 
 * | Created By-Anshu Kumar
 * | Status - Closed
 * | ------------------------------
 * | PipeLine Class to Tag Property 
 */
class TagProperty
{
    private $_mActiveCitizenUnderCares;
    private $_mPropProperty;
    private $_currentDate;
    private $_mPropOwner;
    private $_propertyId;
    private $_mWaterConsumer;
    private $_mActiveCitizenUndercare;
    private $_mTradeLicense;

    /**
     * | Initializing Master Values
     */
    public function __construct()
    {
        $this->_mActiveCitizenUnderCares = new ActiveCitizenUndercare();
        $this->_mPropProperty = new PropProperty();
        $this->_currentDate = Carbon::now();
        $this->_mPropOwner = new PropOwner();
        $this->_mWaterConsumer = new WaterConsumer();
        $this->_mActiveCitizenUndercare    = new ActiveCitizenUndercare();
        $this->_mTradeLicense    = new TradeLicence();
    }

    /**
     * | Handle Class(1)
     */

    public function handle($request, Closure $next)
    {

        if (request()->input('moduleId') != 1) {
            return $next($request);
        }
        $userId = auth()->user()->id;
        $referenceNo = request()->input('referenceNo');
        $property = $this->_mPropProperty->getPropByPtnOrHolding($referenceNo);
        $this->_propertyId = $property->id;
        $this->isPropertyAlreadyTagged();           // function (1.1)
        $propOwner = $this->_mPropOwner->getfirstOwner($property->id);
        $underCareReq = [
            'property_id' => $property->id,
            'date_of_attachment' => $this->_currentDate,
            'mobile_no' => $propOwner->mobile_no,
            'citizen_id' => auth()->user()->id
        ];
        $this->_mActiveCitizenUnderCares->store($underCareReq);
        $water = $this->_mWaterConsumer->getWaterHolding($referenceNo);                // added by arshad
        if ($water) {

            $existingData = $this->_mActiveCitizenUndercare->getDetailsForUnderCare($userId, $water->id);
            if (!$existingData) {
                $underCareReq = [
                    'consumer_id' => $water->id,
                    'date_of_attachment' => $this->_currentDate,
                    'mobile_no' => $propOwner->mobile_no,
                    'citizen_id' => auth()->user()->id
                ];
                $this->_mActiveCitizenUnderCares->store($underCareReq);
            }
        }

        $trade = $this->_mTradeLicense->getTradeHolding($referenceNo);                // added by arshad
        if ($trade) {
            $existingData = $this->_mActiveCitizenUndercare->getDetailsForUnderCarev1($userId, $trade->id);
            if (!$existingData) {
                $underCareReq = [
                    'license_id' => $trade->license_no,
                    'date_of_attachment' => $this->_currentDate,
                    'mobile_no' => $propOwner->mobile_no,
                    'citizen_id' => 36
                ];
                $this->_mActiveCitizenUnderCares->store($underCareReq);
            }
        }


        return "Property Successfully Tagged";
    }

    /**
     * | Is The Property Already Tagged
     */
    public function isPropertyAlreadyTagged()
    {
        $taggedPropertyList = $this->_mActiveCitizenUnderCares->getTaggedProperties($this->_propertyId);
        $totalProperties = $taggedPropertyList->count('property_id');
        if ($totalProperties > 3)                                               // Check if the Property is already tagged 3 times of not
            throw new Exception("Property has already tagged 3 Times");

        $citizens = $taggedPropertyList->pluck('citizen_id');

        if ($citizens->contains(auth()->user()->id))                                // Check Is the Property already tagged by the citizen 
            throw new Exception("Property Already Tagged");
    }
}
