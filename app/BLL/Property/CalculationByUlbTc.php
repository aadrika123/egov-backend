<?php

namespace App\BLL\Property;

use App\BLL\Property\TcVerificationDemandAdjust;

/**
 * | Created On-19-04-2023 
 * | Created By-Anshu Kumar
 * | Created for the Busines Logic Layer for Calculating Property Tax with Ulb Tc Data
 */

class CalculationByUlbTc extends TcVerificationDemandAdjust
{
    public function calculateTax(array $req)
    {
        return $this->calculateQuaterlyTax();
    }
}
