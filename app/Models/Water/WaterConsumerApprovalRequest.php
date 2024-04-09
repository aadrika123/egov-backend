<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterConsumerApprovalRequest extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    public function getConserDtls()
    {
        return $this->belongsTo(WaterConsumer::class,"consumer_id","id","id")->first();
    }
}
