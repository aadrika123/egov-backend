<?php

namespace App\MicroServices;

/**
 * | Created On-13-12-2021 
 * | Created By-Anshu Kumar
 * | Created For the Document Upload MicroService
 */
class DocUpload
{
    /**
     * | Image Document Upload
     * | @param refImageName format Image Name like SAF-geotagging-id
     * | @param requested image
     * | @return imageName imagename to save
     */
    public function upload($refImageName, $image)
    {
        $extention = $image->getClientOriginalExtension();
        $imageName = time() . '-' . $refImageName . '.' . $extention;
        $image->storeAs('public/Property/GeoTagging', $imageName);
        return $imageName;
    }
}
