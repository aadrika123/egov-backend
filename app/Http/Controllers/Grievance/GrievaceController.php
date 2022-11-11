<?php

namespace App\Http\Controllers\Grievance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Grievance\Interfaces\iGrievance;

class GrievaceController extends Controller
{
    private iGrievance $newGrievance;
    public function __construct(iGrievance $newGrievance)
    {
        $this->newGrievance = $newGrievance;
    }

    //
    public function postFileComplain(Request $request)
    {
        return $this->newGrievance->postFileComplain($request);
    }

    //
    public function getAllComplainById($id)
    {
        return $this->newGrievance->getAllComplainById($id);
    }

}