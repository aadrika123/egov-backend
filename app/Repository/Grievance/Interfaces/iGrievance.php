<?php

namespace App\Repository\Grievance\Interfaces;

use Illuminate\Http\Request;


interface iGrievance
{
    // grievance
    public function postFileComplain(Request $request);
    public function getAllComplainById($id);
}
