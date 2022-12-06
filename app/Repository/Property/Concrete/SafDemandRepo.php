<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTransaction;
use App\Repository\Property\Interfaces\iSafDemandRepo;
use Exception;

/**
 * | Created On-27-11-2022 
 * | Created By-Anshu Kumar
 * | Created for the SAF Demandable operations
 */

class SafDemandRepo implements iSafDemandRepo
{
    /**
     * | Get the Demandable Amount By SAF ID After Payment
     * | @param $req
     * | Query Run time -272ms 
     * | Rating-2
     */
    public function getDemandBySafId($req)
    {
        try {
            $demand = array();
            $transaction = new PropTransaction();
            $demand['amounts'] = $transaction->getPropTransactions($req->id, "saf_id");

            $propSafDemand = new PropSafsDemand();
            $demand['details'] = $propSafDemand->getDemandBySafId($req->id);

            return responseMsg(true, "All Demands", remove_null($demand));
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
