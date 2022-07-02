<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repository\Ulbs\EloquentUlbRepository;

/**
 * Created On-02-07-2022 
 * Created By-Anshu Kumar
 * --------------------------------------------------------------------------------
 * Saving, viewing and editing the Ulbs
 */

class UlbController extends Controller
{
    protected $eloquentUlb;
    // Initializing Construct function
    public function __construct(EloquentUlbRepository $eloquentUlb)
    {
        $this->EloquentUlb = $eloquentUlb;
    }
    // Storing 
    public function store(Request $request)
    {
        return $this->EloquentUlb->store($request);
    }

    // Updating
    public function edit(Request $request, $id)
    {
        return $this->EloquentUlb->edit($request, $id);
    }

    // View Ulbs by Id
    public function view($id)
    {
        return $this->EloquentUlb->view($id);
    }
}
