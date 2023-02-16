<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

interface IReport
{
    public function collectionReport(Request $request);
    public function safCollection(Request $request);
}