<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveGbOfficer extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'prop_active_gbofficers';

    /**
     * | Created By-Anshu Kumar
     * | Created for-13/03/2023 
     * | Model for The Officers Details for GB Saf
     */
    public function store($req)
    {
        PropActiveGbOfficer::create($req);
    }
}
