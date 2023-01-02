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
}
