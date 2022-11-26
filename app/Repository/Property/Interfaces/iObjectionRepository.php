<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-17-11-2022 
 * | Created By-Mrinal Kumar
 **/

interface iObjectionRepository
{
    public function applyObjection($request);
    public function objectionType();
    public function ownerDetails($request);
    public function assesmentDetails($request);
    public function inbox();
    public function outbox();
    public function getDetailsById($req);                       // Get Objection Details By ID
    public function specialInbox();                             // Get the Escalated Application List
    public function postEscalate($req);                         // Escalate the application and send to special category
    public function postNextLevel($req);
    public function approvalRejection($req);
    public function backTocitizen($req);
}
