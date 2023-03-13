<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropGbOfficer extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Created By-Anshu Kumar
     * | Created for-13/03/2023 
     * | Model for The Officers Details for GB Saf
     */
    public function store($req)
    {
        PropGbOfficer::create($req);
    }
}
