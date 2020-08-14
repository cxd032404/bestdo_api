<?php


class UploadController  extends BaseController
{

    public function uploadAction(){
        header('Content-Type: application/x-www-form-urlencoded');
        $oUpload = new UploadService();
        $upload = $oUpload->getUploadedFile([],[],0,0,['pic'=>1,'video'=>1,'txt'=>1]);
        return $this->success(array_values($upload));
    }

}
