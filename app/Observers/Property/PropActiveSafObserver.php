<?php

namespace App\Observers\Property;

use App\MicroServices\IdGeneration;
use App\Models\Property\PropActiveSaf;
use Illuminate\Support\Facades\Config;

class PropActiveSafObserver
{
    /**
     * Handle the PropActiveSaf "created" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function created(PropActiveSaf $propActiveSaf)
    {
        $paramId = Config::get('PropertyConstaint.PARAM_ID');
        $gbParamId = Config::get('PropertyConstaint.GB_PARAM');
        $idGeneration = new IdGeneration;
        if ($propActiveSaf->is_gb_saf == false)
            $safNo = $idGeneration->generateId($paramId, true);
        else
            $safNo = $idGeneration->generateId($gbParamId, true);
        $propActiveSaf->saf_no = $safNo;
        $propActiveSaf->save();
    }

    /**
     * Handle the PropActiveSaf "updated" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function updated(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "deleted" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function deleted(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "restored" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function restored(PropActiveSaf $propActiveSaf)
    {
        //
    }

    /**
     * Handle the PropActiveSaf "force deleted" event.
     *
     * @param  \App\Models\PropActiveSaf  $propActiveSaf
     * @return void
     */
    public function forceDeleted(PropActiveSaf $propActiveSaf)
    {
        //
    }
}
