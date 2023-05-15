<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdPartyController;
use App\Models\ActiveCitizen;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropTransaction;
use App\Models\Workflows\WfActiveDocument;
use App\Pipelines\SearchHolding;
use App\Pipelines\SearchPtn;
use App\Repository\Property\Interfaces\iSafRepository;
use App\Repository\Water\Concrete\WaterNewConnection;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On - 11-03-2023
 * | Created By - Mrinal Kumar
 * | Status - Open
 */

class PropertyController extends Controller
{
    /**
     * | Send otp for caretaker property
     */
    public function caretakerOtp(Request $req)
    {
        try {
            $mPropOwner = new PropOwner();
            $ThirdPartyController = new ThirdPartyController();
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            if ($req->moduleId == $propertyModuleId) {
            }
            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');
            $propOwners = $mPropOwner->getOwnerByPropId($propDtl->id);
            $firstOwner = collect($propOwners)->first();
            $ownerMobile = $firstOwner->mobileNo;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $ownerMobile]);
            $response = $ThirdPartyController->sendOtp($myRequest);

            $response = collect($response)->toArray();
            $data['otp'] = $response['original']['data'];
            $data['mobileNo'] = $ownerMobile;

            return responseMsgs(true, "OTP send successfully", $data, '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Care taker property tag
     */
    public function caretakerPropertyTag(Request $req)
    {
        $req->validate([
            'holdingNo' => 'required_without:ptNo|max:255',
            'ptNo' => 'required_without:holdingNo|numeric',
        ]);
        try {
            $userId = authUser()->id;
            $activeCitizen = ActiveCitizen::findOrFail($userId);

            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');

            $allPropIds = $this->ifPropertyExists($propDtl->id, $activeCitizen);
            $activeCitizen->caretaker = $allPropIds;
            $activeCitizen->save();

            return responseMsgs(true, "Property Tagged!", '', '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Function if Property Exists
     */
    public function ifPropertyExists($propId, $activeCitizen)
    {
        $propIds = collect(explode(',', $activeCitizen->caretaker));
        $propIds->push($propId);
        return $propIds->implode(',');
    }

    /**
     * | Logged in citizen Holding & Saf
     */
    public function citizenHoldingSaf(Request $req)
    {
        $req->validate([
            'type' => 'required|In:holding,saf,ptn',
            'ulbId' => 'required|numeric'
        ]);
        try {
            $citizenId = authUser()->id;
            $ulbId = $req->ulbId;
            $type = $req->type;
            $mPropSafs = new PropSaf();
            $mPropActiveSafs = new PropActiveSaf();
            $mPropProperty = new PropProperty();

            if ($type == 'holding') {
                $data = $mPropProperty->getCitizenHoldings($citizenId, $ulbId);
                $data = collect($data)->map(function ($value) {
                    if (isset($value['new_holding_no'])) {
                        return $value;
                    }
                })->filter()->values();
                $msg = 'Citizen Holdings';
            }

            if ($type == 'saf') {
                $data = $mPropActiveSafs->getCitizenSafs($citizenId, $ulbId);
                $msg = 'Citizen Safs';
            }
            if ($type == 'ptn') {
                $data = $mPropProperty->getCitizenPtn($citizenId, $ulbId);
                $msg = 'Citizen Ptn';
            }
            if ($data->isEmpty())
                throw new Exception('No Data Found');

            return responseMsgs(true, $msg, remove_null($data), '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Property Basic Edit
     */
    public function basicPropertyEdit(Request $req)
    {
        try {
            $mPropProperty = new PropProperty();
            $mPropOwners = new PropOwner();
            $propId = $req->propertyId;
            $mOwners = $req->owner;

            $mreq = new Request(
                [
                    "new_ward_mstr_id" => $req->newWardMstrId,
                    "khata_no" => $req->khataNo,
                    "plot_no" => $req->plotNo,
                    "village_mauja_name" => $req->villageMauja,
                    "prop_pin_code" => $req->pinCode,
                    "building_name" => $req->buildingName,
                    "street_name" => $req->streetName,
                    "location" => $req->location,
                    "landmark" => $req->landmark,
                    "prop_address" => $req->address,
                    "corr_pin_code" => $req->corrPin,
                    "corr_address" => $req->corrAddress
                ]
            );
            $mPropProperty->editProp($propId, $mreq);

            collect($mOwners)->map(function ($owner) use ($mPropOwners) {            // Updation of Owner Basic Details
                if (isset($owner['ownerId'])) {

                    $req = new Request([
                        'id' =>  $owner['ownerId'],
                        'owner_name' => $owner['ownerName'],
                        'guardian_name' => $owner['guardianName'],
                        'relation_type' => $owner['relation'],
                        'mobile_no' => $owner['mobileNo'],
                        'aadhar_no' => $owner['aadhar'],
                        'pan_no' => $owner['pan'],
                        'email' => $owner['email'],
                    ]);
                    $mPropOwners->editPropOwner($req);
                }
            });

            return responseMsgs(true, 'Data Updated', '', '010801', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Saf LatLong for map
     * | Using wardId 
     * | @param request
     * | @var 
     * | @return
        | For MVP testing
     */
    public function getpropLatLong(Request $req)
    {
        try {
            $req->validate([
                'wardId' => 'required|integer',
            ]);
            $mPropProperty = new PropProperty();
            $propDetails = $mPropProperty->getPropLatlong($req->wardId);
            $propDetails = collect($propDetails)->map(function ($value) {
                $currentDate = Carbon::now();
                $geoDate = strtotime($value['created_at']);
                $geoDate = date('Y-m-d', $geoDate);
                if ($geoDate < $currentDate) {
                    $path = $this->readRefDocumentPath($value['doc_path']);
                    $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                    return $value;
                }
                $path = $this->readDocumentPath($value['doc_path']);

                # arrrer,current,paid
                $mPropDemand = new PropDemand();
                $refUnpaidPropDemands = $mPropDemand->getDueDemandByPropId($value['property_id']);
                // if()
                // collect($refUnpaidPropDemands)->min('')

                $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "latLong Details", remove_null($propDetails), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $req->deviceId);
        }
    }
    public function readRefDocumentPath($path)
    {
        $path = ("http://smartulb.co.in/RMCDMC/getImageLink.php?path=" . "/" . $path);
        return $path;
    }
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }

    public function uploadDocument(Request $req)
    {
        $mWfActiveDocuments = new WfActiveDocument();
    }
}
