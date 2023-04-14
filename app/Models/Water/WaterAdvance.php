<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterAdvance extends Model
{
    use HasFactory;

    /**
     * | Get Advance respective for consumer id
     * | list all the advance toward consumer
     * | @param consumerId
     * | @var
     * | @return 
     */
    public function getAdvanceByRespectiveId($consumerId,$advanceFor)
    {
        return WaterAdvance::where('related_id',$consumerId)
        ->where('status',1);
    }
}
