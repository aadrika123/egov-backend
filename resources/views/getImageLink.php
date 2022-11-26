<?php
class getImageLink
{
    // for viewing or showing document
    public static function getImage($path)
    {
        // $full_path = $_SERVER['DOCUMENT_ROOT'].'/RMCDMC/writable/uploads/'.$path;
        $full_path = storage_path("app/public/$path");
        // dd( $full_path);
        if(!file_exists($full_path))
        {
            die("File not available.");
        }

        $getInfo = getimagesize($full_path);
        $explod_path = explode('.', $full_path);
        $exp = end($explod_path);
        
        if(file_exists($full_path) && !isset($getInfo['mime']))
        $getInfo['mime']='application/pdf';
        
        if($getInfo['mime']=='application/pdf')
        {
            header('Content-type: '. $getInfo['mime']);
            header('Content-Length: ' . filesize($full_path));
            header('Cache-Control: no-cache');
            header('Content-Transfer-Encoding: binary'); 
            header('Accept-Ranges: bytes');
        }
        else
        {
            header('Content-type: '. $getInfo['mime']);
            header('Content-Length: ' . filesize($full_path));
            header('Cache-Control: no-cache');
        }
        header('Content-type: ' . $getInfo['mime']);
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: no-cache');
        ob_clean();
        flush();
        return readfile($full_path);
    }
}

getImageLink::getImage($_GET["path"]);