<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\EloquentClass\Property\SafCalculation;

class SafCalculatorController extends Controller
{
    public function safCalculation(Request $req)
    {
        $safCalculation = new SafCalculation();
        return $safCalculation->calculateTax($req);
    }
}
