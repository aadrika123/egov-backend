<?php

namespace App\Traits\Property;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * | Created On - 20-11-2022 
 * | Created By - Mrinal Kumar
 * | Created for the Objection Workflow Trait
 */
trait Objection
{

    // Get Concession List
    public function getObjectionList($ulbId)
    {
        return DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.id',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_no as application_no',
                'p.ward_mstr_id as old_ward_id',
                'u.ward_name as old_ward_no',
                'p.new_ward_mstr_id',
                // 'owner_name as applicant_name',
                DB::raw("string_agg(owner_name,',') as applicant_name"),
                'p.new_holding_no',
                'p.holding_no',
                'p.application_date',
                'p.balance',
                't.property_type',
                'p.assessment_type',
                'objection_for'
            )
            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->leftJoin('ref_prop_types as t', 't.id', '=', 'p.prop_type_mstr_id')
            ->join('prop_owners', 'prop_owners.property_id', 'p.id')
            ->join('ulb_ward_masters as u', 'u.id', '=', 'p.ward_mstr_id')
            // ->leftJoin('ulb_ward_masters as u1', 'u.id', '=', 'p.new_ward_mstr_id')
            ->where('prop_active_objections.ulb_id', $ulbId)
            ->groupBy(
                'prop_active_objections.id',
                'prop_active_objections.workflow_id',
                'prop_active_objections.objection_no',
                'p.ward_mstr_id',
                'u.ward_name',
                'p.new_ward_mstr_id',
                'p.new_holding_no',
                'p.holding_no',
                'p.application_date',
                'p.balance',
                't.property_type',
                'p.assessment_type',
                'objection_for'
            );
    }

    public function assessmentDetails($details, $req)
    {
        $basicDetails = $this->generateBasicDetails($details);         // (Basic Details) Trait function to get Basic Details
        $basicElement = [
            'headerTitle' => "Basic Details",
            "data" => $basicDetails
        ];

        $propertyDetails = $this->generatePropertyDetails($details);   // (Property Details) Trait function to get Property Details
        $propertyElement = [
            'headerTitle' => "Property Details & Address",
            'data' => $propertyDetails
        ];

        $corrDetails = $this->generateCorrDtls($details);              // (Corresponding Address Details) Trait function to generate corresponding address details
        $corrElement = [
            'headerTitle' => 'Corresponding Address',
            'data' => $corrDetails,
        ];

        $electDetails = $this->generateElectDtls($details);            // (Electricity & Water Details) Trait function to generate Electricity Details
        $electElement = [
            'headerTitle' => 'Electricity & Water Details',
            'data' => $electDetails
        ];
        $fullDetailsData['application_no'] = $details->objection_no;
        $fullDetailsData['apply_date'] = $details->date;
        $fullDetailsData['objection_for'] = $details->objection_for;
        $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $propertyElement, $corrElement, $electElement]);
        // Table Array
        $ownerList = $mPropOwners->getOwnersByPropId($details->property_id);
        $ownerList = json_decode(json_encode($ownerList), true);       // Convert Std class to array
        $ownerDetails = $this->generateOwnerDetails($ownerList);
        $ownerElement = [
            'headerTitle' => 'Owner Details',
            'tableHead' => ["#", "Owner Name", "Gender", "DOB", "Guardian Name", "Relation", "Mobile No", "Aadhar", "PAN", "Email", "IsArmedForce", "isSpeciallyAbled"],
            'tableData' => $ownerDetails
        ];
        $floorList = $mPropFloors->getPropFloors($details->property_id);    // Model Function to Get Floor Details
        $floorDetails = $this->generateFloorDetails($floorList);
        $floorElement = [
            'headerTitle' => 'Floor Details',
            'tableHead' => ["#", "Floor", "Usage Type", "Occupancy Type", "Construction Type", "Build Up Area", "From Date", "Upto Date"],
            'tableData' => $floorDetails
        ];
        $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$ownerElement, $floorElement]);
        // Card Details
        $cardElement = $this->generateObjCardDtls($details, $ownerList);
        $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

        $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->id);
        $fullDetailsData['levelComment'] = $levelComment;

        $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->id, $details->user_id);
        $fullDetailsData['citizenComment'] = $citizenComment;

        $metaReqs['customFor']  = 'PROPERTY-OBJECTION';
        $metaReqs['wfRoleId']   =  $details->current_role;
        $metaReqs['workflowId'] =  $details->workflow_id;
        $metaReqs['lastRoleId'] =  $details->last_role_id;
        $req->request->add($metaReqs);

        //role details
        $forwardBackward = $mForwardBackward->getRoleDetails($req);
        $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];
        $fullDetailsData['timelineData'] = collect($req);

        $custom = $mCustomDetails->getCustomDetails($req);
        $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

        $custom = $mCustomDetails->getCustomDetails($req);
        $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];
    }
}
