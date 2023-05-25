<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropHarvestingGeotagUpload extends Model
{
    use HasFactory;
    protected $guarded = ['*'];

    /**
     * |
     */
    public function add($req)
    {
        PropHarvestingGeotagUpload::create($req);
        // $geoTagging->application_id = $req->applicationId;
        // $geoTagging->image_path = $imageName;
        // $geoTagging->relative_path = $relativePath;
        // $geoTagging->user_id = authUser()->id;
        // $geoTagging->save();
    }
}
