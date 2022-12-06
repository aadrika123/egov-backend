<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropTransferMode extends Model
{
    use HasFactory;

    /**
     * | Get Transfer Modes
     */
    public function getTransferModes()
    {
        return RefPropTransferMode::select('id', 'transfer_mode')
            ->where('status', 1)
            ->get();
    }
}
