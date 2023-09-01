<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeNoticeConsumerDtl extends TradeParamModel
{
    use HasFactory;
    public $timestamp = false;
    protected $connection;
    public function __construct($DB=null)
    {
        parent::__construct($DB);
    }
}
