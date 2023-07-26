<?php

namespace App\Http\Controllers;

use App\Models\MPropBuildingRentalconst;
use App\Models\MPropBuildingRentalRate;
use App\Models\MPropBuildingRentalrate as ModelsMPropBuildingRentalrate;
use App\Models\MPropForgeryType;
use App\Models\MPropRentalValue;
use App\Models\MPropVacanatRentalrate;
use App\Models\MPropVacantRentalrate;
use App\Models\PropApartmentdtl;
use App\Models\RefPropBuildingRenatlRate;
use App\Models\RefPropConstructionType;
use App\Models\RefPropFloor;
use App\Models\RefPropGbbuildingusagetype;
use App\Models\RefPropGbpropusagetype;
use App\Models\RefPropObjectionType;
use App\Models\RefPropOccupancyFactor;
use App\Models\RefPropOccupancyType;
use App\Models\RefPropOwnershipType;
use App\Models\RefPropPenaltyType;
use App\Models\RefPropRebateType;
use App\Models\RefPropRoadType;
use App\Models\RefPropType;
use App\Models\RefPropUsageType;
use Illuminate\Http\Request;

/**
 * | Creation of Reference APIs
 * | Created By-Tannu Verma
 * | Created On-24-05-2023 
 * | Status-Open
 */

 /**
  * | Functions for creation of Reference APIs
    * 1. listBuildingRentalconst()
    * 2. listPropForgeryType()
    * 3. listPropRentalValue()
    * 4. listPropApartmentdtl()
    * 5. listBropBuildingRentalrate()
    * 6. listPropVacantRentalrate()
    * 7. listPropConstructiontype()
    * 8. listPropFloor()
    * 9. listPropgbBuildingUsagetype()
    * 10. listPropgbPropUsagetype()
    * 11. listPropObjectiontype()
    * 12. listPropOccupancyFactor()
    * 13. listPropOccupancytype()
    * 14. listPropOwnershiptype()
    * 15. listPropPenaltytype()
    * 16. listPropRebatetype()
    * 17. listPropRoadtype()
    * 18. listPropTransfermode()
    * 19. listPropType()
    * 20. listPropUsagetype()
*/


class ReferenceController extends Controller
{ 
    /** 
     * 1. listBuildingRentalconst()
     *    Display List for Building Rental Const
    */
    public function listBuildingRentalconst(Request $request)
   {
    try {
        $m_buildingRentalconst = MPropBuildingRentalconst::where('status', 1)
            ->get();

        if (!$m_buildingRentalconst) {
            return response()->json([
                'message' => 'Building Rental Const Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Building Rental Const Retrieved Successfully',
            'status' => 'success',
            'data' => $m_buildingRentalconst
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Building Rental Const',
            'status' => 'error'
        ], 500);
    }
   }
   
   /** 
     * 2. listPropForgeryType()
     *    Display List for Property Forgery type
    */

   public function listPropForgeryType(Request $request)
   {
    try {
        $m_propforgerytype = MPropForgeryType::where('status', 1)
            ->get();

        if (!$m_propforgerytype) {
            return response()->json([
                'message' => 'Forgery type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Forgery type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propforgerytype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving  Forgery Type',
            'status' => 'error'
        ], 500);
    }
   }

   /** 
     * 3. listPropRentalValue()
     *    Display List for Property rental Value
    */

    public function listPropRentalValue(Request $request)
    {
     try {
         $m_proprentalvalue = MPropRentalValue::where('status', 1)
             ->get();
 
         if (!$m_proprentalvalue) {
             return response()->json([
                 'message' => 'Rental Value Not Found',
                 'status' => 'error'
             ], 404);
         }
 
         return response()->json([
             'message' => 'Rental Value Retrieved Successfully',
             'status' => 'success',
             'data' => $m_proprentalvalue
         ]);
     } catch (\Exception $e) {
         return response()->json([
             'message' => 'Error retrieving Rental value',
             'status' => 'error'
         ], 500);
     }
    }


    /** 
     * 4. listPropApartmentdtl()
     *    Display List for Apartment Detail
    */

    public function listPropApartmentdtl(Request $request)
    {
     try {
         $m_propapartmentdtl = PropApartmentDtl::where('status', 1)
             ->get();
 
         if (!$m_propapartmentdtl) {
             return response()->json([
                 'message' => 'Apartment details Not Found',
                 'status' => 'error'
             ], 404);
         }
 
         return response()->json([
             'message' => 'Apartment details Retrieved Successfully',
             'status' => 'success',
             'data' => $m_propapartmentdtl
         ]);
     } catch (\Exception $e) {
         return response()->json([
             'message' => 'Error retrieving Apartment Details',
             'status' => 'error'
         ], 500);
     }
    }

    
    /** 
     * 5. listPropBuildingRentalrate()
     *    Display List for Building Rental Rate
    */

    public function listPropBuildingRentalrate(Request $request)
    {
     try {
         $m_propbuildingrentalrate = MPropBuildingRentalrate::where('status', 1)
             ->get();
 
         if (!$m_propbuildingrentalrate) {
             return response()->json([
                 'message' => 'Building Rental Rate Not Found',
                 'status' => 'error'
             ], 404);
         }
 
         return response()->json([
             'message' => 'Building Rental Rate Retrieved Successfully',
             'status' => 'success',
             'data' => $m_propbuildingrentalrate
         ]);
     } catch (\Exception $e) {
         return response()->json([
             'message' => 'Error retrieving Building Rental Rate',
             'status' => 'error'
         ], 500);
     }
    }

    
    /** 
     * 6. listPropVacantRentalrate()
     *    Display List for Vacant Rental Rate
    */

    public function listPropVacantRentalrate(Request $request)
   {
    try {
        
        $status = $request->input('status', 1); // Status filter, default is 1
        
        $m_propvacantrentalrate = MPropVacantRentalrate::where('status', $status)
            ->get();

        if (!$m_propvacantrentalrate->count()) {
            return response()->json([
                'message' => 'Vacant Rental Rate Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Vacant Rental Rate Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propvacantrentalrate
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Vacant Rental Rate',
            'status' => 'error'
        ], 500);
    }
  }


  /** 
     * 7. listPropConstructiontype()
     *    Display List for Property Construction Type
    */

    public function listPropConstructiontype(Request $request)
   {
    try {
        $m_propconstructiontype = RefPropConstructionType::where('status', 1)
             ->get();

        if (!$m_propconstructiontype) {
            return response()->json([
                'message' => ' Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Construction Type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propconstructiontype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Construction Type',
            'status' => 'error'
        ], 500);
    }
  }


  /** 
     * 8. listPropFloor()
     *    Display List for Property Floor
    */

    public function listPropFloor(Request $request)
   {
    try {
        
        $status = $request->input('status', 1); // Status filter, default is 1
        
        $m_propfloor = RefPropFloor::where('status', $status)
            ->get();

        if (!$m_propfloor->count()) {
            return response()->json([
                'message' => ' Floor Type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Floor Type Retrieved Successfully',
            'status' => true,
            'data' => $m_propfloor
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Construction Type',
            'status' => 'error'
        ], 500);
    }
  }

   /** 
     * 9. listPropgbBuildingUsagetype()
     *    Display List for Property GB Building Usage Type
    */

    public function listPropgbBuildingUsagetype(Request $request)
   {
    try {
        
        $m_propgbbuildingusagetype = RefPropGbbuildingusagetype::where('status',1)
        ->get();

        if (!$m_propgbbuildingusagetype) {
            return response()->json([
                'message' => '  GB Building Usage Type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'GB Building Usage Type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propgbbuildingusagetype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving GB Building Usage Type',
            'status' => 'error'
        ], 500);
    }
  }



  /** 
     * 10. listPropgbPropUsagetype()
     *    Display List for Property Usage Type
    */

    public function listPropgbPropUsagetype(Request $request)
   {
    try {
        
        $m_propgbpropusagetype = RefPropGbpropusagetype::where('status',1)
        ->get();

        if (!$m_propgbpropusagetype) {
            return response()->json([
                'message' => '  GB Property Usage Type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'GB Property Usage Type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propgbpropusagetype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving GB Property Usage Type',
            'status' => 'error'
        ], 500);
    }
  }


  /** 
     * 11. listPropObjectiontype()
     *    Display List for Property Objection Type
    */

    public function listpropobjectiontype(Request $request)
   {
    try {
        $m_propobjectiontype = RefPropObjectionType::where('status', 1)
        ->get();

        if (!$m_propobjectiontype) {
            return response()->json([
                'message' => 'Property Objection Type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Property Objection Type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propobjectiontype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Property Objection Type',
            'status' => 'error'
        ], 500);
    }
  }


  /** 
     * 12. listPropOccupancyFactor()
     *    Display List for Property Occupancy Factor
    */

    public function listPropOccupancyFactor(Request $request)
   {
    try {
        $m_propoccupancyfactor = RefPropOccupancyFactor::where('status', 1)
        ->get();

        if (!$m_propoccupancyfactor) {
            return response()->json([
                'message' => 'Property Occupancy Factor Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Property Occupancy Factor Retrieved Successfully',
            'status' => true,
            'data' => $m_propoccupancyfactor
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Property Occupancy Factor',
            'status' => 'error'
        ], 500);
    }
  }


  /** 
     * 13. listPropOccupancytype()
     *    Display List for Property Occupancy Type
    */

    public function listPropOccupancytype(Request $request)
   {
    try {
        $m_propoccupancytype = RefPropOccupancyType::where('status', 1)
        ->get();

        if (!$m_propoccupancytype) {
            return response()->json([
                'message' => 'Property Occupancy Type Not Found',
                'status' => 'error'
            ], 404);
        }

        return response()->json([
            'message' => 'Property Occupancy Type Retrieved Successfully',
            'status' => 'success',
            'data' => $m_propoccupancytype
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error retrieving Property Objection Type',
            'status' => 'error'
        ], 500);
    }
  }


   /** 
     * 14. listPropOwnershiptype()
     *    Display List for Property Ownership Type
    */
    
    public function listPropOwnershiptype(Request $request)
    {
      try {
         $m_propownershiptype = RefPropOwnershipType::where('status', 1)
         ->get();

         if(!$m_propownershiptype)
         return response()->json([
            'message'=> 'Property Ownership Type not found',
            'status'=> false
         ]);
         
         return response()->json([
            'message'=>'Property Ownership Type Retrieved Successfully',
            'status'=> true,
            'data'=> $m_propownershiptype
         ]);
        }catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Ownership Type',
                'status'=>'error'
            ],500);
        }

      }

    /** 
     * 15. listPropPenaltytype()
     *    Display List for Property Penalty Type
    */
    
    public function listPropPenaltytype(Request $request)
    {
        try {
            $m_proppenaltytype = RefPropPenaltyType::where('status', 1)
            ->get();
            
            if(!$m_proppenaltytype)
            return response()->json([
                'message'=>'Property Penalty Type not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Penalty Type Retrieved Successfully',
                'status'=>true,
                'data'=>$m_proppenaltytype
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Penalty Type',
                'status'=>'error'
            ],500);

        }
    }

    /** 
     * 16. listPropRebatetype()
     *    Display List for Property Rebate Type
    */
    
    public function listPropRebatetype(Request $request)
    {
        try {
            $m_proprebatetype = RefPropRebateType::where('status', 1)
            ->get();
            
            if(!$m_proprebatetype)
            return response()->json([
                'message'=>'Property Rebate Type not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Rebate Type Retrieved Successfully',
                'status'=>true,
                'data'=>$m_proprebatetype
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Rebate Type',
                'status'=>'error'
            ],500);

        }
    }
    
    
    /** 
     * 17. listPropRoadtype()
     *    Display List for Property Road Type
    */
    
    public function listPropRoadtype(Request $request)
    {
        try {
            $m_proproadtype = RefPropRoadType::where('status', 1)
            ->get();
            
            if(!$m_proproadtype)
            return response()->json([
                'message'=>'Property Road Type not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Road Type Retrieved Successfully',
                'status'=>true,
                'data'=>$m_proproadtype
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Road Type',
                'status'=>'error'
            ],500);

        }
    }

    /** 
     * 18. listPropTransfermode()
     *    Display List for Property Transfer Mode
    */
    
    public function listPropTransfermode(Request $request)
    {
        try {
            $m_proptransfermode = RefPropRebateType::where('status', 1)
            ->get();
            
            if(!$m_proptransfermode)
            return response()->json([
                'message'=>'Property Transfer Mode not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Transfer Mode Retrieved Successfully',
                'status'=>true,
                'data'=>$m_proptransfermode
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Transfer Mode',
                'status'=>'error'
            ],500);

        }
    }

    /** 
     * 19. listProptype()
     *    Display List for Property Type
    */
    
    public function listProptype(Request $request)
    {
        try {
            $m_proptype = RefPropType::where('status', 1)
            ->get();
            
            if(!$m_proptype)
            return response()->json([
                'message'=>'Property Type not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Type Retrieved Successfully',
                'status'=>true,
                'data'=>$m_proptype
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Type',
                'status'=>'error'
            ],500);

        }
    }

    /** 
     * 20. listPropUsagetype()
     *    Display List for Property Usage Type
    */
    
    public function listPropUsagetype(Request $request)
    {
        try {
            $m_propusagetype = RefPropUsageType::where('status', 1)
            ->get();
            
            if(!$m_propusagetype)
            return response()->json([
                'message'=>'Property Usage Type not find',
                'status'=>false,
            ]);

            return response()->json([
                'message'=>'Property Usage Type Retrieved Successfully',
                'status'=>true,
                'data'=>$m_propusagetype
            ]);

         } catch(\Exception $e){
            return response()->json([
                'message'=>'Error Retrieving Property Usage Type',
                'status'=>'error'
            ],500);

        }
    }


    
      


}

    
     

    




  
  

  





  









