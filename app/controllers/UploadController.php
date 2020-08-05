<?php


class UploadController  extends BaseController
{

    public function uploadAction(){
        $oUpload = new UploadService();
        $upload = $oUpload->getUploadedFile([],[],0,0,['pic'=>1,'video'=>1,'txt'=>1]);
        return $this->success($upload);
    }

}