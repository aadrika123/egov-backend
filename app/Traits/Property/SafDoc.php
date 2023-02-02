<?php

namespace App\Traits\Property;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Used for Gettting the Document Lists By Property Types and Owner Details
 * | Created On-02-02-2022 
 * | Created By-Anshu Kumar
 */
trait SafDoc
{
    /**
     * | Count No of Documents
     */
    public function countDocs($totalDocLists)
    {
        $countPropDocs = collect($totalDocLists['listDocs'])->pipe(function ($docs) {
            $reqDocs = $docs->where('docType', 'R')->concat('docType');
            $optDocs = $docs->where('docType', 'O')->concat('docType');
            return [
                'required' => $reqDocs,
                'options' => $optDocs
            ];
        });
        return $countPropDocs;
        $countOwnersDocs = collect($totalDocLists['ownerDocs'])->map(function ($owners) {
            $docs = $owners['documents']->pipe(function ($docs) {
                $reqDocs = $docs->where('docType', 'R')->count('docType');
                $optDocs = $docs->where('docType', 'O')->count('docType');
                return [
                    'required' => $reqDocs,
                    'options' => $optDocs
                ];
            });
            return $docs;
        });
        $countOwnerDocs = [
            'required' => collect($countOwnersDocs)->sum('required'),
            'options' => collect($countOwnersDocs)->sum('options')
        ];
        $docCollections = new Collection([$countPropDocs, $countOwnerDocs]);
        $totalDocs = $docCollections->sum('required') + $docCollections->sum('options');
        return $totalDocs;
    }
}
