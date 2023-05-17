<?php

namespace App\Http\Controllers\property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use App\Models\Property\MCapitalValueRate;
use App\Models\Property\RefPropOccupancyFactor;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iCalculatorRepository;
use Exception;
use Illuminate\Support\Facades\Config;

class CalculatorController extends Controller
{
    protected $Repository;
    private $_reqs;
    private $_occupancyFactors;
    private $_roadTypes;
    private $_mCapitalValueRates;
    private $_effectiveRuleset2;
    private $_effectiveRuleset3;

    public function __construct(iCalculatorRepository $iCalculatorRepository)
    {
        $this->_roadTypes = Config::get('PropertyConstaint.ROAD_TYPES');
        $this->Repository = $iCalculatorRepository;
        $this->_mCapitalValueRates = new MCapitalValueRate();
        $this->_effectiveRuleset2 = Config::get('PropertyConstaint.EFFECTIVE_DATE_RULE2');
        $this->_effectiveRuleset3 = Config::get('PropertyConstaint.EFFECTIVE_DATE_RULE3');
    }

    public function calculator(reqApplySaf $request)
    {
        try {
            $calculation = $this->reviewCalculation($request);
            return $calculation->original;
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Review for the Calculation
     */
    public function reviewCalculation(reqApplySaf $req)
    {
        try {
            $this->_reqs = $req;
            $calculation = new SafCalculation;
            $refPropOccupancyFactor = new RefPropOccupancyFactor();

            $this->_occupancyFactors = $refPropOccupancyFactor->getOccupancyFactors();
            if ($req->propertyType != 4) {
                $floors = collect($req->floor);
                $floors = $floors->map(function ($item, $key) {
                    return collect($item)->put('floorKey', $key + 1);           // Floor Key recognizes the identification of floor even the floor No
                });
                $req->merge(['floor' => $floors->toArray()]);
            }

            if (isset($req->isGBSaf))
                $req->merge(['isGBSaf' => $req->isGBSaf]);
            else
                $req->merge(['isGBSaf' => false]);
            $response = $calculation->calculateTax($req);
            if ($response->original['status'] == false)
                throw new Exception($response->original['message']);
            $finalResponse['demand'] = $response->original['data']['demand'];
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['floorKey', 'ruleSet']);
            $finalTaxReview = collect();

            collect($reviewDetails)->map(function ($ruleSets) use ($finalTaxReview) {
                collect($ruleSets)->map(function ($floor) use ($finalTaxReview) {
                    $first = $floor->first();
                    $response = $first->only([
                        'floorKey',
                        'mFloorNo',
                        'mUsageType',
                        'arv',
                        'buildupArea',
                        'dateFrom',
                        'quarterYear',
                        'qtr',
                        'ruleSet',
                        'holdingTax',
                        'waterTax',
                        'latrineTax',
                        'educationTax',
                        'healthTax',
                        'totalTax',
                        'rwhPenalty',
                        'rentalValue',
                        'carpetArea',
                        'calculationPercFactor',
                        'multiFactor',
                        'rentalRate',
                        'occupancyFactor',
                        'circleRate',
                        'taxPerc',
                        'calculationFactor',
                        'matrixFactor',
                        'area',
                        'yearlyTax'
                    ]);
                    $finalTaxReview->push($response);
                    return $response;
                });
            });

            $totalTaxDetails = collect($response->original['data']['details']);
            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) use ($totalTaxDetails, $calculation) {
                return collect($collection)->pipe(function ($collect) use ($totalTaxDetails, $calculation) {
                    $quaters['floors'] = $collect;
                    $ruleSetWiseCollection = $totalTaxDetails
                        ->where('ruleSet', $collect->first()['ruleSet'])
                        ->values();

                    // Calculation Parameters
                    if ($collect->first()['ruleSet'] == 'RuleSet1' && $this->_reqs->propertyType != 4)              // If Property Type is Building
                        $quaters['rentalRates'] = $this->generateRentalValues($calculation->_rentalValue);

                    if ($collect->first()['ruleSet'] == 'RuleSet2' && $this->_reqs->propertyType != 4) {
                        $quaters['multiFactors'] = $this->generateMultiFactors($calculation->_multiFactors)->where('effective_date', $this->_effectiveRuleset2)->values();
                        $quaters['occupancyFactors'] = $this->_occupancyFactors;
                        $quaters['rentalRate'] = $this->generateRentalRates(collect($calculation->_rentalRates)->where('effective_date', $this->_effectiveRuleset2), $calculation->_paramRentalRate);
                    }

                    if ($collect->first()['ruleSet'] == 'RuleSet2' && $this->_reqs->propertyType == 4) {            // For Vacant Land(RuleSet 2)
                        $quaters['multiFactors'] = $this->_occupancyFactors;
                        $rentalRates = collect($calculation->_vacantRentalRates)
                            ->where('effective_date', '2016-04-01')
                            ->where('ulb_type_id', $calculation->_ulbType);
                        $quaters['rentalRate'] = $this->generateVacantRentalRates($rentalRates);
                    }

                    if ($collect->first()['ruleSet'] == 'RuleSet3' && $this->_reqs->propertyType != 4) {
                        $quaters['calculationFactor'] = $this->generateMultiFactors($calculation->_multiFactors)->where('effective_date', $this->_effectiveRuleset3)->values();
                        $quaters['occupancyFactors'] = $this->_occupancyFactors;
                        $quaters['matrixFactor'] = $this->generateMatrixFactor(collect($calculation->_rentalRates)->where('effective_date', $this->_effectiveRuleset3));
                        $quaters['circleRates'] = $this->readCapitalValueRates($calculation->_wardNo);
                    }

                    if ($collect->first()['ruleSet'] == 'RuleSet3' && $this->_reqs->propertyType == 4) {        // For Vacant Land (Ruleset3)
                        $quaters['circleRates'] = $this->readCapitalValueRates($calculation->_wardNo);
                        $quaters['matrixFactor'] = $this->generateMatrixFactor(collect($calculation->_rentalRates)->where('effective_date', $this->_effectiveRuleset3));
                        $quaters['occupancyFactors'] = $this->_occupancyFactors;
                        $rentalRates = collect($calculation->_vacantRentalRates)
                            ->where('effective_date', '2022-04-01')
                            ->where('ulb_type_id', $calculation->_ulbType);
                        $vacantRentalRates = $this->generateVacantRentalRates($rentalRates)
                            ->whereIn('prop_road_type_id', [2, 3, 4])                           // Road Types
                            ->values();
                        $quaters['vacantRentalRates'] = $vacantRentalRates;
                    }

                    $groupByTotalTax = $ruleSetWiseCollection->groupBy('totalTax');
                    $quaterlyTaxes = collect();
                    $i = 1;
                    collect($groupByTotalTax)->map(function ($floors) use ($quaterlyTaxes, $i) {
                        if ($this->_reqs->propertyType != 4)
                            $groupByFloor = $floors->groupBy('floorKey')->values();
                        else
                            $groupByFloor = $floors->groupBy('propertyType')->values();

                        $taxDetails = $groupByFloor->map(function ($item) {
                            /**
                             * | Every First Quarter key is taken for sum the total quaterly Taxes of the floors individually
                             */
                            $firstTaxes = [
                                'arv' => $item->first()['arv'] ?? null,
                                'holdingTax' => $item->first()['holdingTax'] ?? null,
                                'waterTax' => $item->first()['waterTax'] ?? null,
                                'latrineTax' => $item->first()['latrineTax'] ?? null,
                                'educationTax' => $item->first()['educationTax'] ?? null,
                                'healthTax' => $item->first()['healthTax'] ?? null,
                                'rwhPenalty' => $item->first()['rwhPenalty'] ?? null,
                                'yearlyTax' => $item->first()['yearlyTax'] ?? null,
                                'quaterlyTax' => $item->first()['totalTax'] ?? null,
                            ];
                            return collect($firstTaxes);
                        });
                        $taxes = [
                            'key' => $i,
                            'effectingFrom' => $floors->first()['quarterYear'] . '/' . $floors->first()['qtr'],
                            'qtr' => $floors->first()['qtr'],
                            'area' => $floors->first()['area'] ?? null,
                            'arv' => roundFigure($taxDetails->sum('arv')),
                            'holdingTax' => roundFigure($taxDetails->sum('holdingTax')),
                            'waterTax' => roundFigure($taxDetails->sum('waterTax')),
                            'latrineTax' => roundFigure($taxDetails->sum('latrineTax')),
                            'educationTax' => roundFigure($taxDetails->sum('educationTax')),
                            'healthTax' => roundFigure($taxDetails->sum('healthTax')),
                            'rwhPenalty' => roundFigure($taxDetails->sum('rwhPenalty')),
                            'quaterlyTax' => roundFigure($taxDetails->sum('quaterlyTax')),
                        ];
                        $quaterlyTaxes->push($taxes);
                    });
                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
                    $i += 1;
                    return $quaters;
                });
            });
            $finalResponse['details'] = $reviewCalculation;
            return responseMsgs(true, "", $finalResponse, "", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "1.0", responseTime(), "POST", $req->deviceId);
        }
    }


    /**
     * | Renta Rate Generation
     */
    public function generateRentalValues($rentalRates)
    {
        foreach ($rentalRates as $rentalRate) {
            $rentalRate->usage_type = ($rentalRate->usage_types_id == 1) ? 'RESIDENTIAL' : 'COMMERCIAL';
            $rentalRate->construction_type = Config::get('PropertyConstaint.CONSTRUCTION-TYPE.' . $rentalRate->construction_types_id);
        }
        return collect($rentalRates)->groupBy(['usage_type', 'construction_type']);
    }

    /**
     * | Generate Multi Factors
     */
    public function generateMultiFactors($multiFactors)
    {
        foreach ($multiFactors as $multiFactor) {
            $usageTypeId = $multiFactor->usage_type_id;
            $multiFactor->usage_type = Config::get("PropertyConstaint.USAGE-TYPE.$usageTypeId.TYPE");
        }
        return collect($multiFactors)->where('usage_type', '!=', null)->values();
    }

    /**
     * | Generate Rental Rates
     */
    public function generateRentalRates($rentalRates, $param)
    {
        foreach ($rentalRates as $rentalRate) {
            $rentalRate->road_type = $this->_roadTypes[$rentalRate->prop_road_type_id];
            $rentalRate->rate = round($rentalRate->rate * $param);
            $rentalRate->construction_type = Config::get('PropertyConstaint.CONSTRUCTION-TYPE.' . $rentalRate->construction_types_id);
        }
        return collect($rentalRates)->groupBy(['construction_type', 'road_type']);
    }

    /**
     * | Generate Matrix Factors
     */
    public function generateMatrixFactor($rentalRates)
    {
        $rentalRates = collect($rentalRates)
            ->whereIn('prop_road_type_id', [1, 3]);
        foreach ($rentalRates as $rentalRate) {
            $rentalRate->road_type = $this->_roadTypes[$rentalRate->prop_road_type_id];
            if ($rentalRate->road_type == 'Principal Main Road')
                $rentalRate->road_type = 'Main Road';
            $rentalRate->construction_type = Config::get('PropertyConstaint.CONSTRUCTION-TYPE.' . $rentalRate->construction_types_id);
        }
        return collect($rentalRates)->groupBy(['construction_type', 'road_type']);
    }


    /**
     * | Read Capital Value Rates
     */
    public function readCapitalValueRates($wardNo)
    {
        return $this->_mCapitalValueRates->readCvRatesByWardNo($wardNo)->groupBy('property_type');
    }

    /**
     * | Generate Vacant Rental Rates
     */
    public function generateVacantRentalRates($rentalRates)
    {
        $rentalRates->map(function ($rentalRate) {
            $rentalRate->prop_road_type = $this->_roadTypes[$rentalRate->prop_road_type_id];
        });
        return $rentalRates->values();
    }



    public function dashboardDate(Request $request)
    {
        return $this->Repository->getDashboardData($request);
    }
}
