<?php

namespace App\Repository\WorkflowMaster\Interface;

use Illuminate\Http\Request;

/**
 * Created On-07-10-2022 
 * Created By-Mrinal Kumar
 * -----------------------------------------------------------------------------------------------------
 * Interface for the functions to used in EloquentWorkflowWardUserRepository
 * @return ChildRepository App\Repository\WorkflowMaster\EloquentWorkflowWardUserRepository
 */


interface iWorkflowWardUserRepository
{
    //for crud
    public function create(Request $request);
    public function list();
    public function delete($id);
    public function update(Request $request, $id);
    public function view($id);

    //for mapping
    public function getRoleDetails(Request $req);

    public function getUserById(Request $request);
    public function getWorkflowNameByUlb(Request $request);
    public function getRoleByUlb(Request $request);
    public function getWardByUlb(Request $request);
    public function getRoleByWorkflowId(Request $request);

    public function getUserByRole(Request $request);
    public function getRoleByWorkflow(Request $request);
    public function getUserByWorkflow(Request $request);
    public function getWardsInWorkflow(Request $request);
    public function getUlbInWorkflow(Request $request);

    public function getWorkflowByRole(Request $request);
    public function getUserByRoleId(Request $request);

    public function getWardByRole(Request $request);
    public function getUlbByRole(Request $request);
    public function getUserInUlb(Request $request);
    public function getRoleInUlb(Request $request);
    public function getWorkflowInUlb(Request $request);
}
