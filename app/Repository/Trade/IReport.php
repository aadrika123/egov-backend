<?php

namespace App\Repository\Trade;

use Illuminate\Http\Request;

interface IReport
{
    public function CollectionReports(Request $request);
    public function teamSummary (Request $request);
    public function valideAndExpired(Request $request);
    public function CollectionSummary(Request $request);
    public function tradeDaseboard(Request $request);

}