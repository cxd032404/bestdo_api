<?php
// +----------------------------------------------------------------------
// | API控制器
// +----------------------------------------------------------------------
// | Copyright (c) 2017--20xx年 捞月狗. All rights reserved.
// +----------------------------------------------------------------------
// | File:     api.php
// |
// | Author:   huzhichao@laoyuegou.com
// | Created:  2017-xx-xx
// +----------------------------------------------------------------------
use Phalcon\Translate\Adapter\NativeArray;
//use PHPExcel;
use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;

class ListController extends BaseController
{

    public function postAction()
    {
        //调用user_token解密方法
        $tokenInfo = (new UserService)->getDecrypt();
        //返回值判断
        if ($tokenInfo['result'] != 1) {
            return $this->failure(['jump_url' => '/login'], $tokenInfo['msg'], $tokenInfo['code']);
        }
        $visible = intval($this->request->getPost('visible') ?? 0);
        $list_id = intval($this->request->getPost('list_id') ?? 0);
        $post_id = intval($this->request->getPost('post_id') ?? 0);
        $app_id = $this->request->getHeader("app_id")??101;
        $user_id = $tokenInfo['data']['user_info']->user_id ?? 0;
        $manager_id = $tokenInfo['data']['user_info']->manager_id ?? 0;
        $detail = $this->request->getPost('detail') ?? [];
        $detail['comment'] = $this->request->getPost("comment") ?? "";

        //内容不为空时检测 否则微信接口报错
        if($detail['comment']) {
            //检测上传的内容是否包含敏感词
            $check_result = (new WechatService())->wechatMsgCheck($detail['comment'], $app_id);
            if (!$check_result['result']) {
                return $this->failure([], $check_result['msg']);
            }
        }

        $detail['title'] = $this->request->getPost("title") ?? "";
        if ($post_id > 0) {
            $post = (new PostsService())->updatePosts($post_id, $user_id, $manager_id, $detail, $visible);
        } else {
            $post = (new PostsService())->addPosts($list_id, $user_id, $detail, $visible);
        }
        if ($post['result']) {
            return $this->success($post['data'],$post['data']['msg']);
        } else {
            return $this->failure([], $post['data']['msg']);
        }
    }

    public function source_removeAction()
    {
        $referer = $this->request->getHTTPReferer();
        if (strpos($referer, "admin.")) {
            $this->redirect($referer);
        }
        $post_id = intval($this->request->get('post_id') ?? 0);
        $remove = ['result' => false, 'data' => ['msg' => "文章不存在"]];
        if ($post_id > 0) {
            $remove = (new PostsService())->removeSource(intval($post_id), trim($this->request->get('sid') ?? ""));
        }
        if ($remove['result']) {
            if (strpos($referer, "admin.")) {
                $this->redirect($referer);
            }
            return $this->success($remove['data']);
        } else {
            if (strpos($referer, "admin.")) {
                $this->redirect($referer);
            }
            return $this->failure([], $remove['data']['msg']);
        }
    }

    public function post_displayAction()
    {
        $referer = $this->request->getHTTPReferer();
        if (strpos($referer, "admin.")) {
            $this->redirect($referer);
        }
        $post_id = intval($this->request->get('post_id') ?? 0);
        $remove = ['result' => false, 'data' => ['msg' => "文章不存在"]];
        if ($post_id > 0) {
            $remove = (new PostsService())->updateDisplay(intval($post_id), trim($this->request->get('display') ?? 0));
        }
        if ($remove['result']) {
            if (strpos($referer, "admin.")) {
                $this->redirect($referer);
            }
            return $this->success($remove['data']);
        } else {
            if (strpos($referer, "admin.")) {
                $this->redirect($referer);
            }
            return $this->failure([], $remove['data']['msg']);
        }
    }
}

