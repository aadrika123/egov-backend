<?php

namespace App\Repository\Water\Interfaces;

use Illuminate\Http\Request;

/**
 * | ---------------------- Interface for the New Connections for Water ----------------------- |
 * | Created On-07-10-2022 
 * | Created By - Anshu Kumar
 */

interface iNewConnection
{
   public function store(Request $req);                                 // Apply for new water connection
   public Function waterInbox();                                        // Inbox for water
   public function waterOutbox();                                       // Outbox for water
   public function postNextLevel($req);                                 // Approval in the workflow level
   
   // Citizen View Water Screen for Mobile
   public function getConnectionType();
   public function getConnectionThrough();
   public function getPropertyType();
   public function getOwnerType();
   public function getWardNo();
}
