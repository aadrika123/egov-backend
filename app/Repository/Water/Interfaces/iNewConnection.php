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
   public function store(Request $req);
   public function getUserWaterConnectionCharges(Request $req);
<<<<<<< HEAD
=======
   public function waterPayment(Request $req);
   public function applicantDocumentUpload(Request $req);
>>>>>>> bb3ead69f792464135978e8532379b78f9186914
}
