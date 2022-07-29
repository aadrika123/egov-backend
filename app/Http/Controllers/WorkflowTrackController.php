<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\WorkflowTrack\EloquentWorkflowTrack;

class WorkflowTrackController extends Controller
{
    /**
     * Created On-18-07-2022 
     * Created By-Anshu Kumar
     * ---------------------------------------------------------------------------------------------
     * Saving, Fetching the workflow track messages
     */

    /**
     * Initializing Repository
     */
    protected $eloquentWorkflowTrack;

    public function __construct(EloquentWorkflowTrack $eloquentWorkflowTrack)
    {
        $this->EloquentWorkflowTrack = $eloquentWorkflowTrack;
    }

    /**
     * Store Workflow Track
     */
    public function store(Request $request)
    {
        return $this->EloquentWorkflowTrack->store($request);
    }

    // Get Workflow Track by Workflow Track Id
    public function getWorkflowTrackByID($id)
    {
        return $this->EloquentWorkflowTrack->getWorkflowTrackByID($id);
    }

    // Get Workflow Track By RefTableID and Value
    public function getWorkflowTrackByTableIDValue($ref_table_id, $ref_table_value)
    {
        return $this->EloquentWorkflowTrack->getWorkflowTrackByTableIDValue($ref_table_id, $ref_table_value);
    }
}
