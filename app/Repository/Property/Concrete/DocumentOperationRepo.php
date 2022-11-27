<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iDocumentOperationRepo;

class DocumentOperationRepo implements iDocumentOperationRepo
{
    public function getAllDocuments($request)
    {
        return ("working");
    }
}
