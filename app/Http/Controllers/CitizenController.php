<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;

/**
 * | Created On-08-08-2022 
 * | Created By-Anshu Kumar
 * --------------------------------------------------------------------------------------------
 * | Citizens Operations for Save, approve,Reject
 */
class CitizenController extends Controller
{
    // Initializing Repository
    protected $repository;

    public function __construct(iCitizenRepository $repository)
    {
        $this->Repository = $repository;
    }

    // Citizen Registrations
    public function citizenRegister(Request $request)
    {
        return $this->Repository->citizenRegister($request);
    }

    // Get Citizen By ID
    public function getCitizenByID($id)
    {
        return $this->Repository->getCitizenByID($id);
    }

    // Get All Citizens
    public function getAllCitizens()
    {
        return $this->Repository->getAllCitizens();
    }

    // Update or Reject Citizen By id
    public function editCitizenByID(Request $request, $id)
    {
        return $this->Repository->editCitizenByID($request, $id);
    }

    // Get all applications
    public function getAllAppliedApplications(Request $req)
    {
        return $this->Repository->getAllAppliedApplications($req);
    }
}
