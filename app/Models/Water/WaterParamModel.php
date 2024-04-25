<?php

namespace App\Models\water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterParamModel extends Model
{

    use HasFactory;
    protected $connection ="pgsql_water";
    protected $guarded = [];    

    public static function readConnection()
    {
        $self = new static; //OBJECT INSTANTIATION
        return $self->setConnection($self->connection."::read");
    }
}
