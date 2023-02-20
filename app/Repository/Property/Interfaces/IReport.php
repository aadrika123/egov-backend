<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

interface IReport
{
    public function collectionReport(Request $request);
    public function safCollection(Request $request);
    public function safPropIndividualDemandAndCollection(Request $request);
    public function levelwisependingform(Request $request);
    public function levelformdetail(Request $request);
    public function userWiseWardWireLevelPending(Request $request);
    public function safSamFamGeotagging(Request $request);
}