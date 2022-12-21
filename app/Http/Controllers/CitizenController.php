<?php

namespace App\Http\Controllers;

use App\Models\ActiveCitizen;
use Illuminate\Http\Request;
use App\Repository\Citizen\iCitizenRepository;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make(request()->all(), [
            'name'     => 'required',
            'mobile' => 'required',
            'email' => 'required|unique:active_citizens',
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $mCitizen = new ActiveCitizen();
            $mCitizen->citizenRegister($request);
            return responseMsg(true, "Succesfully Registered", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
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

    // Independent Comment
    public function commentIndependent(Request $req)
    {
        $req->validate([
            'message' => 'required|string'
        ]);

        return $this->Repository->commentIndependent($req);
    }

    // Citizen Transaction History
    public function getTransactionHistory()
    {
        return $this->Repository->getTransactionHistory();
    }
}
