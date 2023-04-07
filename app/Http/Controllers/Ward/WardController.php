<?php

namespace App\Http\Controllers\Ward;

use App\Http\Requests\Ward\UlbWardRequest;
use App\Http\Controllers\Controller;
use App\Models\Ulb\UlbNewWardmap;
use App\Repository\Ward\EloquentWardRepository;
use Illuminate\Http\Request;

/**
 * | Created On-19-08-2022 
 * | Created By-Anshu Kumar
 * | Ulb Wards Operations
 */

class WardController extends Controller
{
    // Initializing Construct Function 
    protected $eloquent_repository;
    public function __construct(EloquentWardRepository $eloquent_repository)
    {
        $this->Repository = $eloquent_repository;
    }
    // Save Ulb Ward
    public function storeUlbWard(UlbWardRequest $request)
    {
        return $this->Repository->storeUlbWard($request);
    }

    // Edit Ulb Ward
    public function editUlbWard(UlbWardRequest $request, $id)
    {
        return $this->Repository->editUlbWard($request, $id);
    }

    // Get Ulb Ward by Ulb ID
    public function getUlbWardByID($id)
    {
        return $this->Repository->getUlbWardByID($id);
    }

    // Get All Ulb Wards
    public function getAllUlbWards()
    {
        return $this->Repository->getAllUlbWards();
    }

    // Get All Ulb Wards
    public function getNewWardByOldWard(Request $req)
    {
        $req->validate([
            'oldWardMstrId' => 'required',
        ]);
        $mulbNewWardMap = new UlbNewWardmap();
        $newWard = $mulbNewWardMap->getNewWardByOldWard($req);

        return responseMsg(true, "Data Retrived", remove_null($newWard));
    }
}
