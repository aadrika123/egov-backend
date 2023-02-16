<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\IReport;
use App\Traits\Auth;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use Auth;    
    
    private $Repository;
    public function __construct(IReport $TradeRepository)
    {
        $this->Repository = $TradeRepository;
    }

    public function collectionReport(Request $request)
    {
        $request->request->add(["metaData"=>["pr1.1",1.1,null,$request->getMethod(),null,]]);
        return $this->Repository->collectionReport($request);
    }
}