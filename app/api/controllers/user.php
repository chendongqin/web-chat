<?php
/**
 * Created by PhpStorm.
 * User: chendongqin
 * Date: 2019/4/22
 * Time: 13:33
 */

namespace app\api\controllers;

use app\userApi;

class  user extends userApi
{

    public function index()
    {


    }

    //查询返回结果
    public function search()
    {
        $searchStr = $this->helper->getParam('searchStr','','string');
        $page = $this->helper->getParam('page',1,'int');
        $pageLimit = $this->helper->getParam('pagelimit',4,'int');
        if(empty($searchStr)){
            return $this->error('查询条件不能为空');
        }
        if(is_numeric($searchStr)){
            $where = "`id` like '".$searchStr."%' OR `user_name`  like '".$searchStr."%' OR `name` like '%".$searchStr."%'";
        }else{
            $where = "`user_name` like '".$searchStr."%' OR `name` like '%".$searchStr."%'";
        }
        $db = $this->getDb();
        $count = 0;
        $users = $db->select('user',$where,'`id` DESC',$page,$pageLimit,$count,['id','user_name','name']);
        $data = [
            'total'=>$count,'pages'=>$this->getPages($count,$pageLimit),'users'=>$users,'page'=>$page
        ];
        return $this->success('成功',$data);
    }

    //获取请求的消息通知
    public function applylist(){
        $limit = $this->helper->getParam('limit',3,'int');
        $user = $this->getLoginUser();
        $db = $this->getDb();
        $where = ['user_id'=>$user['id'],'status>0' ,'OR:'=>['friend_id'=>$user['id'],'status'=>0]];
        $count = 0;
        $result = $db->select('apply',$where,'update_at desc,id desc',1,$limit,$count);
        $lists = [];
        foreach ($result as $item){
            if($item['user_id'] == $user['id']){
                $status = $item['status'] == 1?'通过了':'拒绝了';
                $applyUser = $db->find('user',['id'=>$item['friend_id']]);
                $str = $status.'您的好友申请：'.$item['refuse_reason'];
            }else{
                $applyUser = $db->find('user',['id'=>$item['user_id']]);
                $str = '请求添加好友：'.$item['reason'];
            }
            $list['user_name'] = $applyUser['name'];
            $list['user_avatar'] = $applyUser['avatar'];
            $list['update_at'] = date('Y-m-d H:i:s',strtotime($item['update_at']));
            $list['msg'] = $str;
            $lists[] = $list;
        }
        return $this->success('成功',['lists'=>$lists,'total'=>$count]);
    }


    public function chat()
    {

    }


}