<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\SelfAdvertisement\EloquentSelfAdvertisement;
use App\Http\Requests\SelfAdvertisement as SelfAdvertisementRequest;

/**
 *| Created On-25-07-2022 
 *| Created By-Anshu Kumar
 *| -----------------------------------------------------------------------------------------------
 *| Created For-Self Advertisement all Modules Save, Edit, View etc.
 *| -----------------------------------------------------------------------------------------------
 *| Code Tested by-
 *| Code Tested On-
 */

class SelfAdvertisementController extends Controller
{
    // Initializing function for Repository
    protected $eloquentSelfAdvertisement;
    public function __construct(EloquentSelfAdvertisement $eloquentSelfAdvertisement)
    {
        $this->RepositorySelfAdvertisement = $eloquentSelfAdvertisement;
    }

    // Store Self Advertisement
    public function storeSelfAdvertisement(SelfAdvertisementRequest $request)
    {
        return $this->RepositorySelfAdvertisement->storeSelfAdvertisement($request);
    }

    // Update Self Advertisement
    public function updateSelfAdvertisement(SelfAdvertisementRequest $request, $id)
    {
        return $this->RepositorySelfAdvertisement->updateSelfAdvertisement($request, $id);
    }

    // Get Self Advertisement By ID
    public function getSelfAdvertisementByID($id)
    {
        return $this->RepositorySelfAdvertisement->getSelfAdvertisementByID($id);
    }

    // Get All Self Advertisements 
    public function getAllSelfAdvertisements()
    {
        return $this->RepositorySelfAdvertisement->getAllSelfAdvertisements();
    }

    // Delete Self Advertisement By ID
    public function deleteSelfAdvertisement($id)
    {
        return $this->RepositorySelfAdvertisement->deleteSelfAdvertisement($id);
    }
}
