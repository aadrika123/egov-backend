<?php

namespace App\Repository\SelfAdvertisement;

use App\Repository\SelfAdvertisement\SelfAdvertisement;
use App\Http\Requests\SelfAdvertisement as SelfAdvertisementRequest;
use App\Models\TempSelfAdvertisement;
use Exception;
use Illuminate\Http\Request;
use App\Traits\SelfAdvertisement as SelfAdvertisementTrait;
use App\Helpers\helper;
use Illuminate\Support\Facades\DB;


/**
 *| Created On-25-07-2022 
 *| Created By-Anshu Kumar
 *| -----------------------------------------------------------------------------------------------
 *| Created For- The Crud operations as well as more operations for Self Advertissements
 *| -----------------------------------------------------------------------------------------------
 *| Code Tested By-
 *| Code Tested On-
 */

class EloquentSelfAdvertisement implements SelfAdvertisement
{
    use SelfAdvertisementTrait;                             // Trait Used

    /**
     *| @desc Storing Self Advertisement applied by the users
     *| Save Using App\Traits\SelfAdvertisement
     *| @param SelfAdvertisementRequest $request
     *| @return response
     *| ================================================================================
     *| Find Initiator and CurrentUser
     *| ================================================================================
     *| --#refStmt= Sql Query for Finding Workflows
     *| --Find #workflow[] = Workflows(Initiator,Approver)
     *| --#helper = Creating new Object for Generating New UniqueID --App\Helpers\helper.php
     */

    public function storeSelfAdvertisement(SelfAdvertisementRequest $request)
    {
        try {
            $self_advertisement = new TempSelfAdvertisement;

            // $refStmt = "SELECT
            //                 u.initiator,
            //                 u.finisher
            //             FROM ulb_workflow_masters u
            //             WHERE u.ulb_id=2 AND u.workflow_id=1";

            // $workflow = DB::select($refStmt);
            $helper = new helper;
            $self_advertisement->unique_id = $helper->getNewUniqueID('SF');
            $this->storing($self_advertisement, $request);                      // Save Using Trait
            // $self_advertisement->initiator = $workflow[0]->initiator;
            // $self_advertisement->current_user = $workflow[0]->initiator;
            $self_advertisement->save();                                        // Save
            return response()->json('Successfully Saved', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     *| update Self Advertisement byIds
     *| update using App\Traits\SelfAdvertisement
     *| @param Request $request
     *| @return response
     */

    public function updateSelfAdvertisement(SelfAdvertisementRequest $request, $id)
    {
        try {
            $self_advertisement = TempSelfAdvertisement::find($id);
            $this->storing($self_advertisement, $request);              // Update Using Trait
            $self_advertisement->save();
            return response()->json('Successfully Updated', 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get Self Advertisement By ID
     *| @param temp_selfadvertisement_id $id
     *| @return response
     */

    public function getSelfAdvertisementByID($id)
    {
        try {
            $data = TempSelfAdvertisement::find($id);
            if ($data) {
                return $data;
            } else {
                return response()->json('Data Not Found For This ID', 404);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get All Self Advertisements 
     * @return response
     */
    public function getAllSelfAdvertisements()
    {
        try {
            $data = TempSelfAdvertisement::orderBy('id', 'desc')->get();
            return $data;
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Delete Self Advertisement By ID
     *| @param SelfAdvertisement $id
     *| @return response
     */
    public function deleteSelfAdvertisement($id)
    {
        try {
            $self_advertisement = TempSelfAdvertisement::find($id);
            if ($self_advertisement) {
                $self_advertisement->delete();
                return response()->json('Successfully Deleted', 200);
            } else {
                return response()->json('Data Not Found for this ID', 404);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }
}
