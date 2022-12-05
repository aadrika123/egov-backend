<?php

namespace App\Repository\Water\Interfaces;

use Illuminate\Http\Request;

interface IWaterNewConnection
{
    public function applyApplication(Request $request);
}