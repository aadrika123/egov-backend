<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Property\Interfaces\iSafRepository;
use App\EloquentClass\Property\SafCalculation;
use App\Traits\Property\SAF;

class SafCalculatorController extends Controller
{
    use SAF;
    public function __construct(iSafRepository $repository)
    {
        $this->Repository = $repository;
    }
    public function calculateSaf(Request $req)
    {
        $data = $this->Repository->details($req);
        $req = $data->original['data'];
        // return $req;
        $array = $this->generateSafRequest($req);

        $safCalculation = new SafCalculation();
        $request = new Request($array);
        $safTaxes = $safCalculation->calculateTax($request);
        return $safTaxes;
    }
}
