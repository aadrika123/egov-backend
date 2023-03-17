<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\ActiveCitizen;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Pipelines\SearchHolding;
use App\Pipelines\SearchPtn;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Http;

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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $req->bearerToken(),
                'Accept' => 'application/json',
            ])->post('192.168.0.16:8000/api/user/send-otp', [
                'mobileNo' => $ownerMobile,
            ]);

            // $data = json_decode($response->getBody()->getContents(), true);

            $data['otp'] = $response['data'];
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
            $activeCitizen = ActiveCitizen::find($userId);

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

            if ($activeCitizen->caretaker == NULL)
                $activeCitizen->caretaker = '{"propId":' . $propDtl->id . '}';
            else
                $activeCitizen->caretaker = $activeCitizen->caretaker .     ',{"propId":' . $propDtl->id . '}';
            $activeCitizen->save();

            return responseMsgs(true, "Property Tagged!", '', '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
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
            $mPropProperty = new PropProperty();

            if ($type == 'holding') {
                $data = $mPropProperty->getCitizenHoldings($citizenId, $ulbId);
                $msg = 'Citizen Holdings';
            }

            if ($type == 'saf') {
                $data = $mPropSafs->getCitizenSafs($citizenId, $ulbId);
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
            $mPropSaf = new PropSaf();
            $propDetails = $mPropSaf->getpropLatLongDetails($req->wardId);
            return responseMsgs(true,"latLong Details",remove_null($propDetails),"","01",".ms","POST",$req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $req->deviceId);
        }
    }
}
