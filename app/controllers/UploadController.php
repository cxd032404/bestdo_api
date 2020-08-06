<?php


class UploadController  extends BaseController
{

    public function uploadAction(){
        header('Content-Type: application/x-www-form-urlencoded');
        $oUpload = new UploadService();
        $this->logger->info('eeee');
        $upload = $oUpload->getUploadedFile([],[],0,0,['pic'=>1,'video'=>1,'txt'=>1]);
        $this->logger->info(json_encode($upload));
        return $this->success(array_values($upload));
    }

} 