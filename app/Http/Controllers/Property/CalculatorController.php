<?php

namespace App\Http\Controllers\property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iCalculatorRepository;
use Exception;
use Illuminate\Support\Facades\Config;

class CalculatorController extends Controller
{
    protected $Repository;
    public function __construct(iCalculatorRepository $iCalculatorRepository)
    {
        $this->Repository = $iCalculatorRepository;
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
            $calculation = new SafCalculation;
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
                        'area'
                    ]);
                    $finalTaxReview->push($response);
                    return $response;
                });
            });

            $totalTaxDetails = collect($response->original['data']['details']);
            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) use ($totalTaxDetails) {
                return collect($collection)->pipe(function ($collect) use ($totalTaxDetails) {
                    $quaters['floors'] = $collect;
                    $ruleSetWiseCollection = $totalTaxDetails
                        ->where('ruleSet', $collect->first()['ruleSet'])
                        ->values();
                    $groupByTotalTax = $ruleSetWiseCollection->groupBy('totalTax');
                    $quaterlyTaxes = collect();
                    $i = 1;
                    collect($groupByTotalTax)->map(function ($floors) use ($quaterlyTaxes, $i) {
                        $groupByFloor = $floors->groupBy('floorKey')->values();
                        $taxDetails = $groupByFloor->map(function ($item) {
                            $firstTaxes = [
                                'arv' => $item->first()['arv'],
                                'holdingTax' => $item->first()['holdingTax'],
                                'waterTax' => $item->first()['waterTax'],
                                'latrineTax' => $item->first()['latrineTax'],
                                'educationTax' => $item->first()['educationTax'],
                                'healthTax' => $item->first()['healthTax'],
                                'rwhPenalty' => $item->first()['rwhPenalty'],
                                'quaterlyTax' => $item->first()['totalTax'],
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
            return responseMsg(true, "", $finalResponse);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function dashboardDate(Request $request)
    {
        return $this->Repository->getDashboardData($request);
    }
}
