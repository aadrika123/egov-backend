<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repository\Water\Interfaces\iNewConnection;
use Illuminate\Support\Facades\Validator;

class NewConnectionController extends Controller
{
    private iNewConnection $newConnection;
    public function __construct(iNewConnection $newConnection)
    {
        $this->newConnection = $newConnection;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        #   validation
        $validateUser = Validator::make(
            $request->all(),
            [
                'connectionTypeId'   => 'required|integer',
                'propertyTypeId'     => 'required|integer',
                'ownerType'          => 'required',
                // 'pipelineTypeId'     => 'required|integer',
                'wardId'             => 'required|integer',
                'areaSqft'           => 'required|integer',
                // 'address'            => 'required',
                'landmark'           => 'required',
                'pin'                => 'required|integer',
                // 'flatCount'          => 'required|integer',
                'elecKNo'            => 'required',
                'elecBindBookNo'     => 'required',
                'elecAccountNo'      => 'required',
                'elecCategory'       => 'required',
                'connection_through' => 'required|integer',
                'owners'          => 'required',
            ]
        );

        if ($validateUser->fails()) {
            return response()->json(["status" => false, "message" => "Validation Error!", "data" => $validateUser->getMessageBag()], 400);
        }
        return $this->newConnection->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

    /**
     * | Get Applied Water Charges Status
     */
    public function getUserWaterConnectionCharges(Request $req)
    {
        return $this->newConnection->getUserWaterConnectionCharges($req);
    }

    // Citizen View Water Screen For Mobile
    // Get connection type / water
    public function getConnectionType()
    {
        return $this->newConnection->getConnectionType();
    }

    // Get connection through / water
    public function getConnectionThrough()
    {
        return $this->newConnection->getConnectionThrough();
    }

    // Get property type / water
    public function getPropertyType()
    {
        return $this->newConnection->getPropertyType();
    }

    // Get owner type / water
    public function getOwnerType()
    {
        return $this->newConnection->getOwnerType();
    }

    // Get ward no / water
    public function getWardNo()
    {
        return $this->newConnection->getWardNo();
    }
}
