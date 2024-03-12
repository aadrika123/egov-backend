<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\RefPropFloor;
use App\Models\RefPropConstructionType;
use App\Models\RefPropGbbuildingusagetype;
use App\Models\RefPropGbpropusagetype;
use App\Models\RefPropObjectionType;
use App\Models\RefPropOccupancyFactor;




use Illuminate\Http\Request;
use Exception;


class MasterReferenceController extends Controller
{
    public function createConstructionType(Request $req)
    {
        try {
            $req->validate([
                'constructionType' => 'required'
            ]);

            $create = new RefPropConstructionType();
            $create->addConstructionType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    public function updateConstructionType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'constructionType' => 'required',
             ]);
             $update = new RefPropConstructionType();
             $list  = $update->updateConstructionType($req);
 
             return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function constructiontypebyId(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required'
             ]);
             $listById = new RefPropConstructionType();
             $list  = $listById->getById($req);
 
             return responseMsgs(true, "ConstructionType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function allConstructiontypelist(Request $req)
     {
         try {
             $list = new RefPropConstructionType();
             $masters = $list->listConstructionType();
 
             return responseMsgs(true, "All ConstructionType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function deleteConstructionType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'status'=>'required|int'
             ]);
             $delete = new RefPropConstructionType();
             $message = $delete->deleteConstructionType($req);
             return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }



     public function createFloorType(Request $req)
    {
        try {
            $req->validate([
                'floorName' => 'required'
            ]);

            $create = new RefPropFloor();
            $create->addFloorType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    public function updateFloorType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'floorName' => 'required',
             ]);
             $update = new RefPropFloor();
             $list  = $update->updatefloorType($req);
 
             return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function floortypebyId(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required'
             ]);
             $listById = new RefPropFloor();
             $list  = $listById->getById($req);
 
             return responseMsgs(true, "FloorType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function allFloortypelist(Request $req)
     {
         try {
             $list = new RefPropFloor();
             $masters = $list->listFloorType();
 
             return responseMsgs(true, "All FloorType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function deleteFloorType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'status'=>'required|int'
             ]);
             $delete = new RefPropFloor();
             $message = $delete->deletefloorType($req);
             return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }

     public function createGbBuildingType(Request $req)
    {
        try {
            $req->validate([
                'buildingType' => 'required'
            ]);

            $create = new RefPropGbbuildingusagetype();
            $create->addGbBuildingType($req);

            return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    public function updateGbBuildingType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'buildingType' => 'required',
             ]);
             $update = new RefPropGbbuildingusagetype();
             $list  = $update->updateGbBuildingType($req);
 
             return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function GbBuildingtypebyId(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required'
             ]);
             $listById = new RefPropGbbuildingusagetype();
             $list  = $listById->getById($req);
 
             return responseMsgs(true, "ConstructionType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function allGbBuildingtypelist(Request $req)
     {
         try {
             $list = new RefPropGbbuildingusagetype();
             $masters = $list->listGbBuildingType();
 
             return responseMsgs(true, "All ConstructionType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function deleteGbBuildingType(Request $req)
     {
         try {
             $req->validate([
                 'id' => 'required',
                 'status'=>'required|int'
             ]);
             $delete = new RefPropGbbuildingusagetype();
             $message = $delete->deleteGbBuildingType($req);
             return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }

     public function createGbPropUsageType(Request $req)
     {
         try {
             $req->validate([
                 'propUsageType' => 'required'
             ]);
             $create = new RefPropGbpropusagetype();
             $create->addGbPropUsageType($req);
 
             return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
         } catch (Exception $e) {
             return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
         }
     }
 
     public function updateGbPropUsageType(Request $req)
      {
          try {
              $req->validate([
                  'id' => 'required',
                  'propUsageType' => 'required',
              ]);
              $update = new RefPropGbpropusagetype();
              $list  = $update->updateGbPropUsageType($req);
  
              return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
          } catch (Exception $e) {
              return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
          }
      }
  
      public function GbPropUsagetypebyId(Request $req)
      {
          try {
              $req->validate([
                  'id' => 'required'
              ]);
              $listById = new RefPropGbpropusagetype();
              $list  = $listById->getById($req);
  
              return responseMsgs(true, "GbPropUsageType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
          } catch (Exception $e) {
              return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
          }
      }
  
      public function allGbPropUsagetypelist(Request $req)
      {
          try {
              $list = new RefPropGbpropusagetype();
              $masters = $list->listGbPropUsageType();
  
              return responseMsgs(true, "All GbPropUsageType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
          } catch (Exception $e) {
              return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
          }
      }
  
      public function deleteGbPropUsageType(Request $req)
      {
          try {
              $req->validate([
                  'id' => 'required',
                  'status'=>'required|int'
              ]);
              $delete = new RefPropGbpropusagetype();
              $message = $delete->deleteGbPropUsageType($req);
              return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
          } catch (Exception $e) {
              return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
          }
      }

      public function createObjectionType(Request $req)
      {
          try {
              $req->validate([
                  'Type' => 'required'
              ]);
              $create = new RefPropObjectionType();
              $create->addObjectionType($req);
  
              return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
          } catch (Exception $e) {
              return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
          }
      }
  
      public function updateObjectionType(Request $req)
       {
           try {
               $req->validate([
                   'id' => 'required',
                   'Type' => 'required',
               ]);
               $update = new RefPropObjectionType();
               $list  = $update->updateObjectionType($req);
   
               return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
           } catch (Exception $e) {
               return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
           }
       }
   
       public function ObjectiontypebyId(Request $req)
       {
           try {
               $req->validate([
                   'id' => 'required'
               ]);
               $listById = new RefPropObjectionType();
               $list  = $listById->getById($req);
   
               return responseMsgs(true, "ObjectionType List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
           } catch (Exception $e) {
               return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
           }
       }
   
       public function allObjectiontypelist(Request $req)
       {
           try {
               $list = new RefPropObjectionType();
               $masters = $list->listObjectionType();
   
               return responseMsgs(true, "All ObjectionType List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
           } catch (Exception $e) {
               return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
           }
       }
   
       public function deleteObjectionType(Request $req)
       {
           try {
               $req->validate([
                   'id' => 'required',
                   'status'=>'required|int'
               ]);
               $delete = new RefPropObjectionType();
               $message = $delete->deleteObjectionType($req);
               return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
           } catch (Exception $e) {
               return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
           }
       }

       public function createOccupancyFactor(Request $req)
       {
           try {
               $req->validate([
                   'multFactor' => 'required',
                   'occupancyName' => 'required'
               ]);
               $create = new RefPropOccupancyFactor();
               $create->addOccupancyFactor($req);
   
               return responseMsgs(true, "Successfully Saved", "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
           } catch (Exception $e) {
               return responseMsgs(false, $e->getMessage(), "", "120101", "01", responseTime(), $req->getMethod(), $req->deviceId);
           }
       }
   
       public function updateOccupancyFactor(Request $req)
        {
            try {
                $req->validate([
                    'id' => 'required',
                    'multFactor' => 'required',
                   'occupancyName' => 'required'
                ]);
                $update = new RefPropOccupancyFactor();
                $list  = $update->updateOccupancyFactor($req);
    
                return responseMsgs(true, "Successfully Updated", $list, "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
            } catch (Exception $e) {
                return responseMsgs(false, $e->getMessage(), "", "120102", "01", responseTime(), $req->getMethod(), $req->deviceId);
            }
        }
    
        public function OccupancyFactorbyId(Request $req)
        {
            try {
                $req->validate([
                    'id' => 'required'
                ]);
                $listById = new RefPropOccupancyFactor();
                $list  = $listById->getById($req);
    
                return responseMsgs(true, "OccupancyFactor List", $list, "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
            } catch (Exception $e) {
                return responseMsgs(false, $e->getMessage(), "", "120103", "01", responseTime(), $req->getMethod(), $req->deviceId);
            }
        }
    
        public function allOccupancyFactorlist(Request $req)
        {
            try {
                $list = new RefPropOccupancyFactor();
                $masters = $list->listOccupancyFactor();
    
                return responseMsgs(true, "All Occupancy factor List", $masters, "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            } catch (Exception $e) {
                return responseMsgs(false, $e->getMessage(), "", "120104", "01", responseTime(), $req->getMethod(), $req->deviceId);
            }
        }
    
        public function deleteOccupancyFactor(Request $req)
        {
            try {
                $req->validate([
                    'id' => 'required',
                    'status'=>'required|int'
                ]);
                $delete = new RefPropOccupancyFactor();
                $message = $delete->deleteOccupancyFactor($req);
                return responseMsgs(true, "", $message, "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
            } catch (Exception $e) {
                return responseMsgs(false, $e->getMessage(), "", "120105", "01", responseTime(), $req->getMethod(), $req->deviceId);
            }
        }
}
