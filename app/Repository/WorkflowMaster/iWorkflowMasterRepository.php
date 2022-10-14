<?php

namespace App\Repository\WorkflowMaster;

use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentWorkflowMasterRepository
 * @return ChildRepository App\Repository\WorkflowMaster\EloquentWorkflowMasterRepository
 */


interface iWorkflowMasterRepository
{
    public function create(Request $request);
    public function list();
    public function delete($id);
    public function update(Request $request, $id);
    public function view($id);
}
