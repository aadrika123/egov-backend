<?php
namespace App\EloquentModels\Trade;

use App\Models\Trade\ActiveLicence;
use App\Models\Trade\ExpireLicence;
use App\Models\Trade\ActiveLicenceOwner;
use App\Models\Trade\ExpireLicenceOwner;
use Exception;
use Illuminate\Support\Facades\DB;

class ModelApplication implements IModelApplication
{
    protected $ActiveLicence;
    protected $ExpireLicence;
    protected $ActiveLicenceOwner;
    protected $ExpireLicenceOwner;
    public function __construct()
    {
        $this->ActiveLicence=new ActiveLicence();
        $this->ExpireLicence=new ExpireLicence();
        $this->ActiveLicenceOwner=new ActiveLicenceOwner();
        $this->ExpireLicenceOwner=new ExpireLicenceOwner();
    }

    public function searchLicence(string $licence_no)
    {
        try{
            $data = ActiveLicence::select("*")
                    ->join(
                        DB::raw("(SELECT licence_id,
                                    string_agg(owner_name,',') as owner_name,
                                    string_agg(guardian_name,',') as guardian_name,
                                    string_agg(mobile,',') as mobile
                                    FROM active_licence_owners
                                    WHERE status =1
                                    GROUP BY licence_id
                                    ) owner
                                    "),
                                    function ($join) {
                                        $join->on("owner.licence_id","=",  "active_licences.id");
                                    }
                                    )
                    ->where('status',1)
                    ->where('license_no',$licence_no)
                    ->first();
                    return $data;
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
}