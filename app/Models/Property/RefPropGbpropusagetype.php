<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropGbpropusagetype extends Model
{
    use HasFactory;

    /**
     * | Get GB prop usage types
     */
    public function getGbpropusagetypes()
    {
        return RefPropGbpropusagetype::select('*')
            ->get();
    }
}
