<?php

namespace App\Repository\Grievance\Interfaces;

use Illuminate\Http\Request;


interface iGrievance
{
    // grievance
    public function saveFileComplain(Request $request);
    public function getAllComplainById($id);
    public function updateRateComplaintById(Request $req, $id);
}
