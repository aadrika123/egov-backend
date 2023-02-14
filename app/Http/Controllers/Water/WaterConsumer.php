<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Repository\Water\Interfaces\IConsumer;
use Illuminate\Http\Request;

class WaterConsumer extends Controller
{
    private $Repository;
    public function __construct(IConsumer $Repository)
    {
        $this->Repository = $Repository ;
    }


    /**
     * | Calcullate the Consumer demand 
     * | @param request
     * | @return Repository
        | Serial No :
     */
    public function calConsumerDemand(Request $request)
    { 
        return $this->Repository->calConsumerDemand($request);
    }

    
}
