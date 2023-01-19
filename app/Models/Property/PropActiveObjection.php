<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class PropActiveObjection extends Model
{
    use HasFactory;

    /**
     * Created By: Mrinal Kumar
     * Date : 14/12/2022
     * Status : Open
     */


    /**
     * |-------------------------- details of all concession according id -----------------------------------------------
     * | @param request
     */
    public function allObjection($request)
    {
        $objection = PropActiveObjection::where('id', $request->id)
            ->get();
        return $objection;
    }

    /**
     * This code is for generating the appliction number of Objection 
     * | @param id
        | remove try catch
     */

    public function objectionNo($id)
    {
        try {
            $count = PropActiveObjection::where('id', $id)
                ->select('id')
                ->get();
            $_objectionNo = 'OBJ' . "/" . str_pad($count['0']->id, 5, '0', STR_PAD_LEFT);

            return $_objectionNo;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function objectionList()
    {
        return DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.id',
                'applicant_name as ownerName',
                'holding_no as holdingNo',
                'objection_for as objectionFor',
                'ward_name as wardId',
                'property_type as propertyType',
                'dob',
                'gender',
            )
            ->join('prop_properties', 'prop_properties.id', 'prop_active_objections.property_id')
            ->join('ref_prop_types', 'ref_prop_types.id', 'prop_properties.prop_type_mstr_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', 'prop_properties.ward_mstr_id')
            ->join('prop_owners', 'prop_owners.property_id', 'prop_properties.id')
            ->where('prop_active_objections.status', 1);
    }

    /**
     * | Get Objection Detail by id
     */
    public function getObjectionById($objId)
    {
        return  DB::table('prop_active_objections')
            ->select(
                'prop_active_objections.*',
                'prop_active_objections.id as objection_id',
                'objection_for',
                'prop_active_objections.objection_no',
                'prop_active_objections.workflow_id',
                'prop_active_objections.current_role',
                'prop_active_objections.last_role_id',
                'p.*',
                'p.assessment_type as assessment',
                'w.ward_name as old_ward_no',
                'nw.ward_name as new_ward_no',
                'o.ownership_type',
                'pt.property_type'
            )

            ->join('prop_properties as p', 'p.id', '=', 'prop_active_objections.property_id')
            ->join('ulb_ward_masters as w', 'w.id', '=', 'p.ward_mstr_id')
            ->join('ulb_ward_masters as nw', 'nw.id', '=', 'p.new_ward_mstr_id')
            ->join('ref_prop_ownership_types as o', 'o.id', '=', 'p.ownership_type_mstr_id')
            ->join('ref_prop_types as pt', 'pt.id', '=', 'p.prop_type_mstr_id')
            ->where('p.status', 1)
            ->where('prop_active_objections.id', $objId)
            ->first();
    }

    /**
     * | Get Objection by Objection No
     */
    public function getObjByObjNo($objectionNo)
    {
        return DB::table('prop_active_objections as o')
            ->select(
                'o.id',
                'o.objection_no as application_no',
                'p.new_holding_no',
                'p.id as property_id',
                'p.ward_mstr_id',
                'p.new_ward_mstr_id',
                'u.ward_name as old_ward_no',
                'u1.ward_name as new_ward_no'
            )
            ->join('prop_properties as p', 'p.id', '=', 'o.property_id')
            ->join('ulb_ward_masters as u', 'p.ward_mstr_id', '=', 'u.id')
            ->leftJoin('ulb_ward_masters as u1', 'p.new_ward_mstr_id', '=', 'u1.id')
            ->where('o.objection_no', $objectionNo)
            ->first();
    }

    /**
     * 
     */
    public function getObjectionNo($objId)
    {
        return PropActiveObjection::select('*')
            ->where('id', $objId)
            ->first();
    }
}
