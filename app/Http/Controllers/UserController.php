<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Auth\EloquentAuthRepository;

/*
  *  Controller for user data login, logout, changing password
  *  Child Repository => App\Repository\Auth
*/

class UserController extends Controller
{
    // INITIALIZING FOR REPOSITORY
    protected $eloquentAuth;

    public function __construct(EloquentAuthRepository $eloquentAuth)
    {
        $this->EloquentAuth = $eloquentAuth;
    }

    // STORE USER IN DATABASE
    public function store(Request $request)
    {
        return $this->EloquentAuth->store($request);
    }

    // USER AUTHENTICATION
    public function loginAuth(Request $request)
    {
        return $this->EloquentAuth->loginAuth($request);
    }

    // USER LOGOUT
    public function logOut()
    {
        return $this->EloquentAuth->logOut();
    }

    // CHANGING PASSWORD
    public function changePass(Request $request)
    {
        return $this->EloquentAuth->changePass($request);
    }

    // REDIS TEST FUNCTION 
    public function testing()
    {
        return $this->EloquentAuth->testing();
    }
}
