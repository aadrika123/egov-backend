<?php

namespace App\Repository\Api;

use App\Repository\Api\ApiRepository;
use App\Http\Requests\Api\ApiStoreRequest;
use App\Http\Requests\Api\ApiUpdateRequest;
use App\Http\Requests\Api\ApiSearchRequest;
use App\Models\ApiMaster;
use Exception;
use App\Traits\Api\StoreApi;

/**
 * Repository for Storing, Modifing, Fetching The Api master 
 * ---------------------------------------------------------------------------------------------------------
 * Created On-29-06-2022 
 * Created By-Anshu Kumar
 * ---------------------------------------------------------------------------------------------------------
 * Code Tested By-
 * Feedback-
 */

class EloquentApiRepository implements ApiRepository
{
    use StoreApi;
    /**
     * Storing API 
     * @param App\Http\Requests\Api\ApiStoreRequest
     * @param App\Http\Requests\Api\ApiStoreRequest $request
     * @return App\Traits\Api\StoreApi Trait
     */
    public function store(ApiStoreRequest $request)
    {
        try {
            $api_master = new ApiMaster;
            return $this->saving($api_master, $request);            //Save using StoreApi Trait
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }

    /**
     * Modifying APIs
     * @param App\Http\Requests\Api\ApiUpdateRequest
     * @param \App\Http\Requests\Api\ApiUpdateRequest $request
     * @return App\Traits\Api\StoreApi Trait
     */

    public function update(ApiUpdateRequest $request)
    {
        try {
            $api_master = ApiMaster::find($request->id);
            if ($api_master) {
                return $this->saving($api_master, $request);  //Save using StoreApi Trait(Code Duplication Removed)
            } else {
                return response()->json('Id Not Found', 404);
            }
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }

    /**
     * Searching APIs by Api End Point
     * @param App\Http\Requests\Api\ApiSearchRequest
     * @param App\Http\Requests\Api\ApiSearchRequest $request
     * @return response
     */

    public function search(ApiSearchRequest $request)
    {
        try {
            $api_master = ApiMaster::where('end_point', $request->EndPoint)
                ->orWhere('end_point', 'like', '%' . $request->EndPoint . '%')
                ->get();

            if ($api_master->count() > 0) {
                return response()->json($api_master, 302);
            } else {
                return response()->json(['Message' => 'No End Point Available'], 404);
            }
        } catch (Exception $e) {
            return response()->json([$e, 400]);
        }
    }
}
