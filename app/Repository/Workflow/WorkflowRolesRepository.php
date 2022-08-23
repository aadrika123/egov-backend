<?php

namespace App\Repository\Workflow;

use App\Repository\Workflow\iWorkflowRepository;
use Illuminate\Http\Request;

/*
| ---------------------------------------------------------------------------------------------------
| Created On-23-08-2022 
| Created By- Anshu Kumar
| Workflow Wise Roles Crud Operations
| ----------------------------------------------------------------------------------------------------
*/

class WorkflowRolesRepository implements iWorkflowRepository
{
    /**
     * | Store Request Resource In DB
     * | @param Request
     * | @param Request $request
     */
    public function store(Request $request)
    {
        dd($request->all());
    }
}
