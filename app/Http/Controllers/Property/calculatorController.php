<?php

namespace App\Http\Controllers\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iCalculatorRepository;

class calculatorController extends Controller
{
    protected $Repository;
    public function __construct(iCalculatorRepository $iCalculatorRepository)
    {
        $this->Repository = $iCalculatorRepository;
    }

    public function calculator(Request $request)
    {
        return $this->Repository->safCalculator($request);
    }


    public function dashboardDate(Request $request)
    {
        return $this->Repository->getDashboardData($request);
    }
}
