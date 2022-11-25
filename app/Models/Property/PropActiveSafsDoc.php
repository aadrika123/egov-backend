<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSafsDoc extends Model
{
    use HasFactory;

    // Get Document by document id
    public function getSafDocument($id)
    {
        return PropActiveSafsDoc::where('id', $id)
            ->first();
    }
}
