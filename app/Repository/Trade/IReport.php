<?php

namespace App\Repository\Trade;

use Illuminate\Http\Request;

interface IReport
{
    public function CollectionReports(Request $request);
    public function teamSummary (Request $request);

}