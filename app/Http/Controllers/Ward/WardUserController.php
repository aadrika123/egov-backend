<?php

namespace App\Http\Controllers\Ward;

use App\Http\Controllers\Controller;
use App\Repository\Ward\EloquentWardUserRepository;
use App\Http\Requests\Ward\WardUserRequest;

class WardUserController extends Controller
{
    /**
     * | Created On-20-08-2022 
     * | Created By-Anshu Kumar
     * | Ward Users Master Crud Operations
     */

    //  Initializing Repository
    protected $eloquent_ward;
    public function __construct(EloquentWardUserRepository $eloquent_ward)
    {
        $this->Repository = $eloquent_ward;
    }

    // Store New Ward Users
    public function storeWardUser(WardUserRequest $request)
    {
        return $this->Repository->storeWardUser($request);
    }

    // Update Existing Ward Users
    public function updateWardUser(WardUserRequest $request)
    {
        return $this->Repository->updateWardUser($request);
    }

    // Get Ward Users by ID
    public function getWardUserByID($id)
    {
        return $this->Repository->getWardUserByID($id);
    }

    // Get All Ward Users
    public function getAllWardUsers()
    {
        return $this->Repository->getAllWardUsers();
    }
}
