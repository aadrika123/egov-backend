<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\ZoneMaster;
use Exception;
use Illuminate\Http\Request;

/**
 *  Created by : Mrinal Kumar
 *  Created On : 24-01-2023
 */

class ZoneController extends Controller
{

    # ---------------------------------------------------------#
    # ----- APIs that are currently inactive or unused --------#
    # ---------------------------------------------------------#

    /**
     * | Retrieve list of zones for a given ULB (Urban Local Body) ID.
    */
    public function getZoneByUlb(Request $req)
    {
        try {
            $mZoneMaster  = new ZoneMaster();
            $zone = $mZoneMaster->getZone($req->ulbId);

            return responseMsgs(true, "Zone", remove_null($zone), "011401", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "011401", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
