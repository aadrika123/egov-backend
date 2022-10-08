<?php

namespace App\Repository\Water\Interfaces;

use Illuminate\Http\Request;

/**
 * | ---------------------- Interface for the New Connections for Water ----------------------- |
 * | Created On-07-10-2022 
 * | Created By - Anshu Kumar
 */

interface iNewConnection
{
   public function store(Request $req);
   public function getUserWaterConnectionCharges(Request $req);
}
