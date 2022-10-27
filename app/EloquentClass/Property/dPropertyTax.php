<?php

namespace App\EloquentClass\Property;

use App\Models\ActiveSafTaxe;

class dPropertyTax
{
    protected $saf_dtl_id;
    protected $fy_mstr_id;
    protected $arv;
    protected $holding_tax;
    protected $water_tax;
    protected $education_cess;
    protected $health_cess;
    protected $latrine_tax;
    protected $additional_tax;
    protected $qtr;
    protected $fyear;
    protected $quarterly_tax;

    public function InsertTax($saf_id, array $tax)
    {
        $this->saf_dtl_id = $saf_id;
        $this->arv = 0;
        $this->fy_mstr_id = 0;
        $this->holding_tax = 0;
        $this->water_tax = 0;
        $this->education_cess = 0;
        $this->health_cess = 0;
        $this->latrine_tax = 0;
        $this->additional_tax = 0;
        $this->qtr = 0;
        $this->fyear = 0;
        $this->quarterly_tax = 0;
        // dd($tax);
        $taxs = array_map(function ($val) {
            //dd($val['Tax']);
            return $val['Tax'] ?? false;
        }, $tax);
        $floar['floor'] = [];
        if (isset($tax['floorsDtl'])) {
            $floar['floor'] = array_map(function ($val) {
                return $val['Tax'] ?? false;
            }, $tax['floorsDtl']);
            $taxs['floorsDtl'] = $floar['floor'][0] ?? false;
        }
        $vacandLand = [];
        if (isset($tax['vacandLand'])) {
            $vacandLand = array_map(function ($val) {
                return $val['Tax'] ?? false;
            }, $tax['floorsDtl']);
            $taxs['vacandLand'] = $vacandLand[0] ?? false;
        }
        $formulaEmplimentDate = fromRuleEmplimenteddate();
        $FromFyYear = getFYear($formulaEmplimentDate);
        $t = FyListasoc($formulaEmplimentDate);
        $PrivFyTax = 0;
        foreach ($t as $val) {
            $FyTax = array_column($taxs, $val);
            $Q1 = array_column($FyTax, 'qtr-1');
            $Q2 = array_column($FyTax, 'qtr-2');
            $Q3 = array_column($FyTax, 'qtr-3');
            $Q4 = array_column($FyTax, 'qtr-4');
            $Q1 = array_sum($Q1);
            $Q2 = array_sum($Q2);
            $Q3 = array_sum($Q3);
            $Q4 = array_sum($Q4);

            $arv = array_column($FyTax, 'ARV');
            $arv = array_sum($arv);

            $HoldingTax = array_column($FyTax, 'HoldingTax');

            $HoldingTax = array_sum($HoldingTax);
            print_var($HoldingTax);
            $LatineTax = array_column($FyTax, 'LatineTax');
            $LatineTax = array_sum($LatineTax);

            $WaterTax = array_column($FyTax, 'WaterTax');
            $WaterTax = array_sum($WaterTax);

            $HealthTax = array_column($FyTax, 'HealthTax');
            $HealthTax = array_sum($HealthTax);

            $EducationTax = array_column($FyTax, 'EducationTax');
            $EducationTax = array_sum($EducationTax);
            // dd($FyTax);

            if ($PrivFyTax != $Q1) {
                echo "demand insert 1 $val priv = $PrivFyTax  current = $Q1 <br>";
                $inputs = [];
                $inputs = [
                    'saf_dtl_id'         => $this->saf_dtl_id,
                    'fy_mstr_id'        => null,
                    "arv"               => $arv,
                    "water_tax"         => $WaterTax,
                    "education_cess"    => $EducationTax,
                    "health_cess"       => $HealthTax,
                    "latrine_tax"       => $LatineTax,
                    "additional_tax"    => 0,
                    "qtr"               => 2,
                    "fyear"             => $val,
                    "quarterly_tax"     => $Q1,

                ];
                $PrivFyTax = $Q1;
            }
            if ($PrivFyTax != $Q2) {
                echo "demand insert 2 $val priv = $PrivFyTax  current = $Q2 <br>";
                $inputs = [];
                $inputs = [
                    'saf_dtl_id'         => $this->saf_dtl_id,
                    'fy_mstr_id'        => null,
                    "arv"               => $arv,
                    "water_tax"         => $WaterTax,
                    "education_cess"    => $EducationTax,
                    "health_cess"       => $HealthTax,
                    "latrine_tax"       => $LatineTax,
                    "additional_tax"    => 0,
                    "qtr"               => 3,
                    "fyear"             => $val,
                    "quarterly_tax"     => $Q2,

                ];
                $PrivFyTax = $Q2;
            }
            if ($PrivFyTax != $Q3) {
                echo "demand insert 3 $val priv = $PrivFyTax  current = $Q3 <br>";
                $inputs = [];
                $inputs = [
                    'saf_dtl_id'         => $this->saf_dtl_id,
                    'fy_mstr_id'        => null,
                    "arv"               => $arv,
                    "water_tax"         => $WaterTax,
                    "education_cess"    => $EducationTax,
                    "health_cess"       => $HealthTax,
                    "latrine_tax"       => $LatineTax,
                    "additional_tax"    => 0,
                    "qtr"               => 3,
                    "fyear"             => $val,
                    "quarterly_tax"     => $Q3,

                ];
                $this->insert($inputs);
                $PrivFyTax = $Q3;
            }
            if ($PrivFyTax != $Q4) {
                echo "demand insert 4 $val priv = $PrivFyTax  current = $Q4 <br>";
                $inputs = [];
                $inputs = [
                    'saf_dtl_id'         => $this->saf_dtl_id,
                    'fy_mstr_id'        => null,
                    "arv"               => $arv,
                    "water_tax"         => $WaterTax,
                    "education_cess"    => $EducationTax,
                    "health_cess"       => $HealthTax,
                    "latrine_tax"       => $LatineTax,
                    "additional_tax"    => 0,
                    "qtr"               => 4,
                    "fyear"             => $val,
                    "quarterly_tax"     => $Q4,

                ];
                $PrivFyTax = $Q4;
            }
        }
        die;
    }

    public function insert(array $inputs)
    {
        // $active_saf_tax = new ActiveSafTaxe;
        // $active_saf_tax->saf_dtl_id     = $inputs['saf_dtl_id'];
        // $active_saf_tax->fy_mstr_id     = $inputs['fy_mstr_id'];
        // $active_saf_tax->arv            = $inputs['arv'];
        // $active_saf_tax->water_tax      = $inputs['water_tax'];
        // $active_saf_tax->education_cess = $inputs['education_cess'];
        // $active_saf_tax->health_cess    = $inputs['health_cess'];
        // $active_saf_tax->latrine_tax    = $inputs['latrine_tax'];
        // $active_saf_tax->additional_tax = $inputs['additional_tax'];
        // $active_saf_tax->qtr            = $inputs['qtr'];
        // $active_saf_tax->fyear          = $inputs['fyear'];
        // $active_saf_tax->quarterly_tax  = $inputs['quarterly_tax'];
        // $active_saf_tax->save();
    }
}
