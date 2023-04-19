<?php

namespace App\Http\Controllers\property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iCalculatorRepository;
use Exception;

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
            if (isset($req->isGBSaf)) {
                $req->merge(['isGBSaf' => $req->isGBSaf]);
            } else
                $req->merge(['isGBSaf' => false]);
            $response = $calculation->calculateTax($req);
            if ($response->original['status'] == false)
                throw new Exception($response->original['message']);
            $finalResponse['demand'] = $response->original['data']['demand'];
            $reviewDetails = collect($response->original['data']['details'])->groupBy(['ruleSet', 'mFloorNo', 'mUsageType']);
            $finalTaxReview = collect();
            $review = collect($reviewDetails)->map(function ($reviewDetail) use ($finalTaxReview) {
                $table = collect($reviewDetail)->map(function ($floors) use ($finalTaxReview) {
                    $usageType = collect($floors)->map(function ($floor) use ($finalTaxReview) {
                        $first = $floor->first();
                        $response = $first->only([
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
                    return $usageType;
                });
                return $table;
            });

            $ruleSetCollections = collect($finalTaxReview)->groupBy(['ruleSet']);
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) {
                return collect($collection)->pipe(function ($collect) {
                    $quaters['floors'] = $collect;
                    $groupByFloors = $collect->groupBy(['quarterYear', 'qtr']);
                    $quaterlyTaxes = collect();
                    collect($groupByFloors)->map(function ($qtrYear) use ($quaterlyTaxes) {
                        return collect($qtrYear)->map(function ($qtr, $key) use ($quaterlyTaxes) {
                            return collect($qtr)->pipe(function ($floors) use ($quaterlyTaxes, $key) {
                                $taxes = [
                                    'key' => $key,
                                    'effectingFrom' => $floors->first()['quarterYear'],
                                    'qtr' => $floors->first()['qtr'],
                                    'area' => $floors->first()['area'] ?? null,
                                    'arv' => roundFigure($floors->sum('arv') + $quaterlyTaxes->sum('arv')),
                                    'holdingTax' => roundFigure($floors->sum('holdingTax') + $quaterlyTaxes->sum('holdingTax')),
                                    'waterTax' => roundFigure($floors->sum('waterTax') + $quaterlyTaxes->sum('waterTax')),
                                    'latrineTax' => roundFigure($floors->sum('latrineTax') + $quaterlyTaxes->sum('latrineTax')),
                                    'educationTax' => roundFigure($floors->sum('educationTax') + $quaterlyTaxes->sum('educationTax')),
                                    'healthTax' => roundFigure($floors->sum('healthTax') + $quaterlyTaxes->sum('healthTax')),
                                    'rwhPenalty' => roundFigure($floors->sum('rwhPenalty') + $quaterlyTaxes->sum('rwhPenalty')),
                                    'quaterlyTax' => roundFigure($floors->sum('totalTax') + $quaterlyTaxes->sum('quaterlyTax')),
                                ];
                                $quaterlyTaxes->push($taxes);
                            });
                        });
                    });
                    $quaters['totalQtrTaxes'] = $quaterlyTaxes;
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
