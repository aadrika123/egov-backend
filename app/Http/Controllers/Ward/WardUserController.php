<?php

namespace App\Http\Controllers\Ward;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Ward\EloquentWardUserRepository;
use App\Http\Requests\Ward\WardUserRequest;

class WardUserController extends Controller
{
    /**
     * | Created On-20-08-2022 
     * | Created By-Anshu Kumar
     * | Ward Users Master Crud Operations
     */

    //  Initializing Repository
    protected $eloquent_ward;
    public function __construct(EloquentWardUserRepository $eloquent_ward)
    {
        $this->Repository = $eloquent_ward;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->Repository->getAllWardUsers();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(WardUserRequest $request)
    {
        return $this->Repository->storeWardUser($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->Repository->getWardUserByID($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(WardUserRequest $request, $id)
    {
        return $this->Repository->updateWardUser($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
