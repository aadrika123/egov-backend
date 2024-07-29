<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActRule extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = "pgsql_master";
    public function addRule($req)

    {
        $data = new self;
        $data->rule_name = $req->ruleName;
        $data->unique_id = $req->uniqueId;
        $data->reference_no = $req->ReferenceNo;
        $data->rule_image_url = $req->ruleImage;
        $data->save();
        return $data->id;
    }

    public function updateRule($req)
    {
        $data = self::where('id', $req->id)
            ->where('status', true)
            ->first();
        $data->rule_name = $req->ruleName ?? $data->rule_name;
        $data->rule_image_url = $req->ruleImage ?? $data->rule_image_url;
        $data->reference_no = $req->ReferenceNo ?? $data->reference_no;
        $data->unique_id = $req->uniqueId ?? $data->unique_id;
        return $data->update();
    }

    public function listRule()
    {
        $list = self::orderBy('id', 'asc')
            ->get();
        return $list;
    }

    public function deleteRule($req)
    {
        $sliderType = self::find($req->id);
        $oldStatus = $sliderType->status;
        $sliderType->status = $req->status;
        $sliderType->save();
        if ($oldStatus == 1 && $sliderType->status == 0) {
            $message = "Data Disabled";
        } else {
            $message = "Data Enabled";
        }
        return $message;
    }

    public function getById($req)
    {
        $list = self::where('id', $req->id)
            ->first();
        return $list;
    }

    public function listDash()
    {
        return self::select('*')
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get();
    }
}
