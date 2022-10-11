<?php

namespace App\Http\Controllers\WorkflowMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\WorkflowMaster\Concrete\EloquentWorkflowTrackRepository;

/**
 * Created On-08-10-2022 
 * Created By-Mrinal Kumar
 * --------------------------------------------------------------------------------
 * 
 */


class WorkflowTrackControllers extends Controller
{
    protected $eloquentTrack;
    // Initializing Construct function
    public function __construct(EloquentWorkflowTrackRepository $eloquentTrack)
    {
        $this->EloquentTrack = $eloquentTrack;
    }
    // Create 
    public function create(Request $request)
    {
        return $this->EloquentTrack->create($request);
    }

    // Get All data
    public function list()
    {
        return $this->EloquentTrack->list();
    }

    // Delete data
    public function delete($id)
    {
        return $this->EloquentTrack->delete($id);
    }

    // Updating
    public function update(Request $request)
    {
        return $this->EloquentTrack->update($request);
    }

    // View data by Id
    public function view($id)
    {
        return $this->EloquentTrack->view($id);
    }
}
