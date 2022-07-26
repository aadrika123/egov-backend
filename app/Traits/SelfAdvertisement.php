<?php

namespace App\Traits;

/**
 * Created On-26-07-2022 
 * Created For-Code Reusable for SelfAdvertisement
 */
trait SelfAdvertisement
{
    // Save and Updata function 
    public function storing($self_advertisement, $request)
    {
        $self_advertisement->license_year = $request->LicenseYear;
        $self_advertisement->applicant = $request->Applicant;
        $self_advertisement->father = $request->Father;
        $self_advertisement->email = $request->Email;
        $self_advertisement->residence_address = $request->ResidenceAddress;
        $self_advertisement->ward_no = $request->WardNo;
        $self_advertisement->permanent_address = $request->PermanentAddress;
        $self_advertisement->mobile_no = $request->Mobile;
        $self_advertisement->aadhar_no = $request->AadharNo;
        $self_advertisement->entity_name = $request->EntityName;
        $self_advertisement->entity_address = $request->EntityAddress;
        $self_advertisement->entity_ward = $request->WardNo1;
        $self_advertisement->installation_location = $request->InstallationLocation;
        $self_advertisement->brand_display_name = $request->BrandDisplayName;
        $self_advertisement->holding_no = $request->HoldingNo;
        $self_advertisement->trade_license_no = $request->TradeLicenseNo;
        $self_advertisement->gst_no = $request->GstNo;
        $self_advertisement->display_type = $request->DisplayType;
        $self_advertisement->display_area = $request->DisplayArea;
        $self_advertisement->longitude = $request->Longitude;
        $self_advertisement->latitude = $request->Latitude;
    }
}
