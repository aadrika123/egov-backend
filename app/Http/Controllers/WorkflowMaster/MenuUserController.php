<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentMenuUserRepository;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class MenuUserController extends Controller
{
    protected $eloquentUser;
    // Initializing Construct function
    public function __construct(EloquentMenuUserRepository $eloquentUser)
    {
        $this->EloquentUser = $eloquentUser;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentUser->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentUser->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentUser->delete($id);
    }

    // Updating
    public function update(Request $request, $id)
    {
        return $this->EloquentUser->update($request, $id);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentUser->view($id);
    }
}
