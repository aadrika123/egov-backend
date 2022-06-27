<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\AuthUserRequest;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Repository\Auth\EloquentAuthRepository;

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
}
