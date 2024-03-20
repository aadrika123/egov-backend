<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyHoarding extends Model
{
    use HasFactory;

    public function get()
    {
        return self::select('*')
        ->get();
    }
}
