<?php

namespace App\EloquentClass\Property;

use Carbon\Carbon;

/**
 * | Created On-31-01-2023 
 * | Created By-Anshu Kumar
 * | Calculation for the Penalty and Rebate of Property and SAF
 * | Status-Open
 */

class PenaltyRebateCalculation
{
    /**
     * | One Percent Penalty Calculation
     * | @param quarterDueDate The floor/Property Due Date
     */
    public function calcOnePercPenalty($quarterDueDate)
    {
        $currentDate = Carbon::now();
        $currentDueDate = Carbon::now()->lastOfQuarter()->floorMonth();
        $quarterDueDate = Carbon::parse($quarterDueDate)->floorMonth();
        $diffInMonths = $quarterDueDate->diffInMonths($currentDate);
        if ($quarterDueDate >= $currentDueDate)                                       // Means the quarter due date is on current quarter or next quarter
            $onePercPenalty = 0;
        else
            $onePercPenalty = $diffInMonths;

        return $onePercPenalty;
    }

    /**
     * | Read Rebate
     * | @param currentQuarter Current Date Quarter
     * | @param loggedInUserType Logged In User type
     * | @param mLastQuarterDemand Last Quarter Demand
     * | @param ownerDetails First owner Details
     */
    public function readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, $ownerDetails, $totalDemand, $totalDuesList)
    {
        $currentDate = Carbon::now();
        $citizenRebatePerc = 5;
        $jskRebatePerc = 2.5;
        $speciallyAbledRebatePerc = 5;
        $rebates = array();
        $rebate1 = 0;
        $rebate = 0;
        $rebateAmount = 0;
        $seniorCitizen = 60;
        $specialRebateAmt = 0;
        $years = $currentDate->diffInYears(Carbon::parse($ownerDetails['dob']));

        if ($currentQuarter == 1) {                                                         // Rebate On Financial Year Payment On 1st Quarter
            $rebate1 += 5;
            $rebateAmount += roundFigure(($mLastQuarterDemand * 5) / 100);
            array_push($rebates, [
                "rebateTypeId" => 5,
                "rebateType" => 'firstQuartPmtRebate',
                "rebatePerc" => 5,
                "rebateAmount" =>  $rebateAmount
            ]);
        }

        if ($loggedInUserType == 'Citizen') {                                         // In Case of Citizen
            $rebate1 += $citizenRebatePerc;
            $rebateAmount += roundFigure(($mLastQuarterDemand * $citizenRebatePerc) / 100);
            array_push($rebates, [
                "rebateType" => "citizenRebate",
                "rebatePerc" => $citizenRebatePerc,
                "rebateAmount" => $rebateAmount
            ]);
        }
        if ($loggedInUserType == 'JSK') {                                              // In Case of JSK
            $rebate1 += $jskRebatePerc;
            $rebateAmount += roundFigure(($mLastQuarterDemand * $jskRebatePerc) / 100);
            array_push($rebates, [
                "rebateType" => "jskRebate",
                "rebatePerc" => $jskRebatePerc,
                "rebateAmount" => $rebateAmount
            ]);
        }

        if (
            $ownerDetails['is_armed_force'] == 1 || $ownerDetails['is_specially_abled'] == 1 ||
            $ownerDetails['gender']  == 'Female' || $ownerDetails['gender'] == 'Transgender'  || $years >= $seniorCitizen
        ) {
            $rebate += $speciallyAbledRebatePerc;
            $specialRebateAmt = roundFigure(($totalDemand * $speciallyAbledRebatePerc) / 100);
            array_push($rebates, [
                "rebateType" => "speciallyAbledRebate",
                "rebatePerc" => $speciallyAbledRebatePerc,
                "rebateAmount" => $specialRebateAmt,
            ]);
        }

        $totalDuesList['rebatePerc'] = $rebate1;
        $totalDuesList['rebateAmt'] = $rebateAmount;
        $totalDuesList['specialRebatePerc'] = $rebate;
        $totalDuesList['specialRebateAmt'] = $specialRebateAmt;

        return $totalDuesList;
    }
}
