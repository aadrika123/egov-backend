<?php

namespace App\Repository\SelfAdvertisement;

use App\Repository\SelfAdvertisement\SelfAdvertisement;
use App\Http\Requests\SelfAdvertisement as SelfAdvertisementRequest;
use App\Models\TempSelfAdvertisement;
use Exception;
use App\Traits\SelfAdvertisement as SelfAdvertisementTrait;
use App\Helpers\helper;
use App\Models\UlbWorkflowMaster;
use Illuminate\Support\Facades\Config;


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


            $refWorkflowID = Config::get('workflow-constants.SELF_ADVERTISEMENT_WORKFLOW_ID');

            $workflow = UlbWorkflowMaster::select('initiator', 'finisher')
                ->where('ulb_id', auth()->user()->ulb_id)
                ->where('workflow_id', $refWorkflowID)
                ->first();

            if (!$workflow) {
                $message = ['status' => false, 'data' => '', 'message' => 'Workflow Not Available'];
                return response()->json($message, 200);
            }

            $self_advertisement = new TempSelfAdvertisement;
            $helper = new helper;
            $self_advertisement->unique_id = $helper->getNewUniqueID('SF');
            $this->storing($self_advertisement, $request);                      // Save Using Trait
            $self_advertisement->initiator = $workflow->initiator;
            $self_advertisement->current_user = $workflow->initiator;
            $self_advertisement->approver = $workflow->finisher;
            $self_advertisement->save();                                        // Save
            $message = ['status' => true, 'data' => '', 'message' => 'Successfully Submitted Your Application'];
            return response()->json($message, 200);
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
            $message = ['status' => true, 'data' => '', 'message' => 'Successfully Updated The Application'];
            return response()->json($message, 200);
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
            $self_Adv = TempSelfAdvertisement::find($id);
            if ($self_Adv) {
                $message = ['status' => true, 'message' => 'Data Available', 'data' => $self_Adv];
                return response()->json($message, 200);
            } else {
                $message = ['status' => false, 'message' => 'Data Not Available', 'data' => ''];
                return response()->json($message, 200);
            }
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * Get All Self Advertisements in Inbox
     * @return response
     */
    public function getAllSelfAdvertisementsInbox()
    {
        try {
            $user = auth()->user()->id;
            $self_Adv = TempSelfAdvertisement::where('current_user', $user)
                ->orderBy('id', 'desc')
                ->get();
            $message = ['status' => true, 'message' => 'Data Available', 'data' => $self_Adv];
            return response()->json($message, 200);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * | Get All Self Advertisements In Outbox
     * | @return Response
     */
    public function getAllSelfAdvertisementsOutbox()
    {
        try {
            $user = auth()->user()->id;
            $self_Adv = TempSelfAdvertisement::where('current_user', '<>', $user)
                ->orderBy('id', 'desc')
                ->get();
            $message = ['status' => true, 'message' => 'Data Available', 'data' => $self_Adv];
            return response()->json($message, 200);
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
