<?php

namespace App\Repository\Citizen;

use Illuminate\Http\Request;

/**
 * | Created On-08-08-2022 
 * | Created By-Anshu Kumar
 * --------------------------------------------------------------------------------------
 * Created For Interface for Citizen Repository
 */
interface iCitizenRepository
{

    public function getCitizenByID($id);

    public function getAllCitizens();

    public function editCitizenByID(Request $request, $id);

    public function getAllAppliedApplications($req);

    public function commentIndependent($req);

    public function getTransactionHistory();                                                                // Get Payment Transaction History of the User
}
