<?php

namespace App\Http\Controllers\property;

use App\EloquentClass\Property\SafCalculation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iCalculatorRepository;
use Exception;

class calculatorController extends Controller
{
    protected $Repository;
    public function __construct(iCalculatorRepository $iCalculatorRepository)
    {
        $this->Repository = $iCalculatorRepository;
    }

    public function calculator(reqApplySaf $request)
    {
        try {
            $calculation = new SafCalculation;
            $calculateArr = array();
            $mobileTower = array();
            $hordingBoard = array();
            $petrolPump = array();

            $mobileTower['area'] = $request->mobileTowerArea;
            $mobileTower['dateFrom'] = $request->mobileTowerDate;

            $hordingBoard['area'] = $request->hoardingArea;
            $hordingBoard['dateFrom'] = $request->hoardingDate;

            $petrolPump['area'] = $request->petrolPumpArea;
            $petrolPump['dateFrom'] = $request->petrolPumpDate;

            $calculateArr['ulbId'] = $request->ulbId;
            $calculateArr['isMobileTower'] = ($request->mobileTowerArea) ? 1 : 0;
            $calculateArr['mobileTower'] = $mobileTower;
            $calculateArr['isHoardingBoard'] = ($request->hoardingArea) ? 1 : 0;
            $calculateArr['hoardingBoard'] = $hordingBoard;
            $calculateArr['isPetrolPump'] = ($request->petrolPumpArea) ? 1 : 0;
            $calculateArr['petrolPump'] = $petrolPump;


            $request->request->add($calculateArr);

            $response = $calculation->calculateTax($request);
            $fetchDetails = collect($response->original['data']['details'])->groupBy('ruleSet');
            $finalResponse['demand'] = $response->original['data']['demand'];
            $finalResponse['details']['description'] = $fetchDetails;
            return responseMsg(true, "", $finalResponse);
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
            $calculateArr = array();
            $mobileTower = array();
            $hordingBoard = array();
            $petrolPump = array();

            $mobileTower['area'] = $req->mobileTowerArea;
            $mobileTower['dateFrom'] = $req->mobileTowerDate;

            $hordingBoard['area'] = $req->hoardingArea;
            $hordingBoard['dateFrom'] = $req->hoardingDate;

            $petrolPump['area'] = $req->petrolPumpArea;
            $petrolPump['dateFrom'] = $req->petrolPumpDate;

            $calculateArr['ulbId'] = $req->ulbId;
            $calculateArr['isMobileTower'] = ($req->mobileTowerArea) ? 1 : 0;
            $calculateArr['mobileTower'] = $mobileTower;
            $calculateArr['isHoardingBoard'] = ($req->hoardingArea) ? 1 : 0;
            $calculateArr['hoardingBoard'] = $hordingBoard;
            $calculateArr['isPetrolPump'] = ($req->petrolPumpArea) ? 1 : 0;
            $calculateArr['petrolPump'] = $petrolPump;


            $req->request->add($calculateArr);
            $response = $calculation->calculateTax($req);

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
                            'matrixFactor'
                        ]);
                        $finalTaxReview->push($response);
                        return $response;
                    });
                    return $usageType;
                });
                return $table;
            });

            $ruleSetCollections = collect($finalTaxReview)->groupBy('ruleSet');
            $reviewCalculation = collect($ruleSetCollections)->map(function ($collection) {
                return collect($collection)->pipe(function ($collect) {
                    $quaters['floors'] = $collect;
                    $quaters['totalQtrTaxes'] = [
                        'effectingFrom' => $collect->first()['dateFrom'],
                        'qtr' => $collect->first()['qtr'],
                        'arv' => roundFigure($collect->sum('arv')),
                        'holdingTax' => roundFigure($collect->sum('holdingTax')),
                        'waterTax' => roundFigure($collect->sum('waterTax')),
                        'latrineTax' => roundFigure($collect->sum('latrineTax')),
                        'educationTax' => roundFigure($collect->sum('educationTax')),
                        'healthTax' => roundFigure($collect->sum('healthTax')),
                        'rwhPenalty' => roundFigure($collect->sum('rwhPenalty')),
                        'quaterlyTax' => roundFigure($collect->sum('totalTax')),
                    ];
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
