<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AuthorizeRequestUser;
use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Repository\Auth\EloquentAuthRepository;
use App\Traits\Auth;
use Illuminate\Http\Request;

/**
 * Controller for user data login, logout, changing password
 * Child Repository => App\Repository\Auth
 * Creation Date:-24-06-2022
 * Created By:- Anshu Kumar
 * 
 *  ** Code Test  **
 * Code Tested By-Anil Mishra Sir
 * Code Testing Date-24-06-2022
 */

class UserController extends Controller
{
    // Initializing for Repository
    protected $eloquentAuth;

    public function __construct(EloquentAuthRepository $eloquentAuth)
    {
        $this->EloquentAuth = $eloquentAuth;
    }

    // Store User In Database
    public function store(AuthUserRequest $request)
    {
        return $this->EloquentAuth->store($request);
    }

    //
    public function authorizeStore(AuthorizeRequestUser $request)
    {
        $request['ulb'] = auth()->user()->ulb_id;
        return $this->EloquentAuth->store($request);
    }



    // Update User Details
    public function update(Request $request, $id)
    {
        return $this->EloquentAuth->update($request, $id);
    }

    // User Authentication
    public function loginAuth(LoginUserRequest $request)
    {
        return $this->EloquentAuth->loginAuth($request);
    }

    // User Logout
    public function logOut()
    {
        return $this->EloquentAuth->logOut();
    }

    // Changing Password
    public function changePass(ChangePassRequest $request)
    {
        return $this->EloquentAuth->changePass($request);
    }

    // Redis Test Function
    public function testing()
    {
        return $this->EloquentAuth->testing();
    }

    // Get All Users
    public function getAllUsers()
    {
        return $this->EloquentAuth->getAllUsers();
    }

    // Get User by Ids
    public function getUser($id)
    {
        return $this->EloquentAuth->getUser($id);
    }

    /**
     * ----------------------------------------------------------------------------------
     * Current Logged In Users
     * ----------------------------------------------------------------------------------
     */
    // My Profile Details
    public function myProfileDetails()
    {
        return $this->EloquentAuth->myProfileDetails();
    }

    // Edit My Profile Details
    public function editMyProfile(Request $request)
    {
        return $this->EloquentAuth->editMyProfile($request);
    }
}
