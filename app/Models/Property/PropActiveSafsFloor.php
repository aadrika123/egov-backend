<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PropActiveSafsFloor extends Model
{
    use HasFactory;
    protected $fillable = [
        'saf_id', 'floor_mstr_id', 'usage_type_mstr_id', 'const_type_mstr_id', 'occupancy_type_mstr_id',
        'builtup_area', 'date_from', 'date_upto', 'user_id', 'prop_floor_details_id', 'carpet_area'
    ];

    /**
     * | Get Saf Floor Details by SAF id
     */
    public function getFloorsBySafId($safId)
    {
        return DB::table('prop_active_safs_floors')
            ->select('prop_active_safs_floors.*', 'f.floor_name', 'u.usage_type', 'o.occupancy_type', 'c.construction_type')
            ->join('ref_prop_floors as f', 'f.id', '=', 'prop_active_safs_floors.floor_mstr_id')
            ->join('ref_prop_usage_types as u', 'u.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
            ->join('ref_prop_occupancy_types as o', 'o.id', '=', 'prop_active_safs_floors.occupancy_type_mstr_id')
            ->join('ref_prop_construction_types as c', 'c.id', '=', 'prop_active_safs_floors.const_type_mstr_id')
            ->where('saf_id', $safId)
            ->get();
    }

    /**
     * | Get occupancy type according to Saf id
     */
    public function getOccupancyType($safId, $refTenanted)
    {
        $occupency = PropActiveSafsFloor::where('saf_id', $safId)
            ->where('occupancy_type_mstr_id', $refTenanted)
            ->get();
        $check = collect($occupency)->first();
        if ($check) {
            $metaData = [
                'tenanted' => true
            ];
            return $metaData;
        }
        return  $metaData = [
            'tenanted' => false
        ];
        return $metaData;
    }

    /**
     * | Get usage type according to Saf NO
     */
    public function getSafUsageCatagory($safId)
    {
        return PropActiveSafsFloor::select(
            'ref_prop_usage_types.usage_code'
        )
            ->join('ref_prop_usage_types', 'ref_prop_usage_types.id', '=', 'prop_active_safs_floors.usage_type_mstr_id')
            ->where('saf_id', $safId)
            ->orderByDesc('ref_prop_usage_types.id')
            ->get();
    }

    /**
     * | Floor Edit
     */
    public function editFloor($req, $citizenId)
    {
        $req = new Request($req);
        $floor = PropActiveSafsFloor::find($req->floorId);
        if ($req->occupancyType == 1) {
            $carpetArea =  $req->buildupArea * 0.70;
        } else {
            $carpetArea =  $req->buildupArea * 0.80;
        }
        $reqs = [
            'floor_mstr_id' => $req->floorNo,
            'usage_type_mstr_id' => $req->useType,
            'const_type_mstr_id' => $req->constructionType,
            'occupancy_type_mstr_id' => $req->occupancyType,
            'builtup_area' => $req->buildupArea,
            'carpet_area' => $carpetArea,
            'date_from' => $req->dateFrom,
            'date_upto' => $req->dateUpto,
            // 'prop_floor_details_id' => $req->propFloorDetail,
            'user_id' => $citizenId,

        ];
        $floor->update($reqs);
    }

    public function addfloor($req, $safId, $citizenId)
    {
        if ($req['occupancyType'] == 1) {
            $carpetArea =  $req['buildupArea'] * 0.70;
        } else {
            $carpetArea =  $req['buildupArea'] * 0.80;
        }

        $floor = new  PropActiveSafsFloor();
        $floor->saf_id = $safId;
        $floor->floor_mstr_id = $req['floorNo'] ?? null;
        $floor->usage_type_mstr_id = $req['useType'] ?? null;
        $floor->const_type_mstr_id = $req['constructionType'] ?? null;
        $floor->occupancy_type_mstr_id = $req['occupancyType'] ??  null;
        $floor->builtup_area = $req['buildupArea'] ?? null;
        $floor->carpet_area = $carpetArea;
        $floor->date_from = $req['dateFrom'] ?? null;
        $floor->date_upto = $req['dateUpto'] ?? null;
        $floor->prop_floor_details_id = $req['propFloorDetail'] ?? null;
        $floor->user_id = $citizenId;
        $floor->save();
    }
}
