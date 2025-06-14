<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropHarvestingGeotagUpload extends Model
{
    use HasFactory;
    protected $guarded = [''];

    /**
     * | Get the latest geotag upload for a specific application ID
       | Reference Function : siteVerification
     */
    public function add($req)
    {
        PropHarvestingGeotagUpload::create($req);
    }

    /**
     * | Get the latest geotag upload for a specific application ID
       | Common Function
     */
    public function getLatLong($applicationId)
    {
        return PropHarvestingGeotagUpload::on('pgsql::read')
            ->where('application_id', $applicationId)
            ->orderbydesc('id')
            ->first();
    }
}
