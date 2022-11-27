<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Repository\Property\Interfaces\iDocumentOperationRepo;
use Illuminate\Http\Request;

class DocumentOperationController extends Controller
{
     /**
     * | Created On-27-11-2022 
     * | Created By-Sam kerketta
     * --------------------------------------------------------------------------------------
     * | Controller for Property Document Operation
     */

    // Initializing function for Repository
    protected $DocumentOperationRepo;
    public function __construct(iDocumentOperationRepo $DocumentOperationRepo)
    {
        $this->DocumentOperationRepo = $DocumentOperationRepo;
    }

    public function getAllDocuments(Request $request)
    {
        $request->validate([
            'workflowId' => 'required|intreger',
            'applicationId' => 'required|integer'
        ]);
        return $this->DocumentOperationRepo->getAllDocuments($request);
    }
}
