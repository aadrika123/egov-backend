<?php

namespace App\Repository\Property\Concrete;

use App\Models\Property\PropActiveSafsDoc;
use App\Repository\Property\Interfaces\iDocumentOperationRepo;
use Exception;

class DocumentOperationRepo implements iDocumentOperationRepo
{
    /**
     * |-----------------------------------------------------------------------------------------------------------|
     * | Document Reated repo-  
     * | Created By-Sam kerktta
     * | Created On-27-11-2022 
     * ----------------------------------------------------------------------------------------------------------------|
     */


    /**
     * | -------------------------Get All the Docment Details According to workflow and applicaton Id ------------------------------- |
     * | @param request
     * | @var documentDetails
     * | @param error
     * | Operation : fetch all the Document details for Active Saf from the Table
     * | Rating : 1
     * | Time :
     */
    public function getAllDocuments($request)
    {
        try {
            $documentDetails = PropActiveSafsDoc::join('ref_prop_docs_required', 'ref_prop_docs_required.id', '=', 'prop_active_safs_docs.doc_mstr_id')
                ->join('prop_active_safs', 'prop_active_safs.id', '=', 'prop_active_safs_docs.saf_id')
                ->select(
                    'prop_active_safs_docs.id AS docId',
                    'doc_path AS docPath',
                    'ref_prop_docs_required.doc_name AS docName',
                    'prop_active_safs_docs.remarks',
                    
                )
                ->where('prop_active_safs.workflow_id', $request->workflowId)
                ->where('prop_active_safs.id', $request->applicationId)
                ->get();

            if (empty($documentDetails['0'])) {
                return responseMsg(false, "Data Not Found!", "");
            }

            return responseMsg(true, "Document Details!", remove_null($documentDetails));
        } catch (Exception $error) {
            return responseMsg(false, "ERROR!", $error->getMessage());
        }
    }
}
