<?php
namespace Home\Controller;
use Home\Model\CategoryModel;
use Think\Controller;

class CategoryController extends Controller {

    //栏目首页
    public function indexAction()
    {
        $Cate=new CategoryModel();
        $result=$Cate->order('sort asc')->select();
        $result2=$Cate->subtree($result);
        $this->assign('results',$result2);
        $this->display();
    }

    //栏目编辑
    public function updateAction(){

        $Cate=new CategoryModel();
        $id=I('get.id');
        $result=$Cate->where('id=%d',['%d'=>$id])->find();

        if(IS_POST){

            if (!$Cate->create(I('post.'))){
                foreach($Cate->getError() as $v){
                    $data1['error']=1;
                    $data1['message']=$v;
                    $this->ajaxReturn($data1);
                }
            }

            $data['name']=I('post.name');
            $data['time']=time();
            $data['type']=I('post.type');
            $data['parent']=I('post.parent');
            $data['title']=I('post.title');
            $data['keyWord']=I('post.keyWord');
            $data['description']=I('post.description');

            if($Cate->where('id=%d',['%d'=>I('post.id')])->save($data)!==false){
                $data1['error']=0;
                $data1['message']='栏目更新成功!';
                $this->ajaxReturn($data1);
            }else{
                $data1['error']=1;
                $data1['message']='栏目更新失败!';
                $this->ajaxReturn($data1);
            }

        }else{
            $this->assign('results',$Cate->select());
            $this->assign('result',$result);
            $this->display();
        }
    }

    //栏目添加
    public function addAction(){
        $Cate=new CategoryModel();
        if(IS_POST){
            if (!$Cate->create(I('post.'))){
                foreach($Cate->getError() as $v){
                    $data1['error']=1;
                    $data1['message']=$v;
                    $this->ajaxReturn($data1);
                }
            }
            $result=$Cate->where('parent=%d',['%d'=>I('post.parent')])->order('sort desc')->find();
            //$data['uid']=session('uid');
            $data['name']=I('post.name');
            $data['type']=I('post.type');
            $data['sort']=$result['sort']+1;
            $data['time']=time();
            $data['title']=I('post.title');
            $data['keyWord']=I('post.keyWord');
            $data['description']=I('post.description');
            $data['parent']=I('post.parent');
            $data['level']=I('post.parent')?1:0;
            if($Cate->data($data)->add()){
                $data1['error']=0;
                $data1['message']='栏目添加成功!';
                $this->ajaxReturn($data1);
            }else{
                $data1['error']=1;
                $data1['message']='栏目添加失败!';
                $this->ajaxReturn($data1);
            }
        }else{
            $this->assign('results',$Cate->select());
            $this->display();
        }
    }

    //栏目删除
    public function deleteAction(){
        $Cate=new CategoryModel();
        $id=I('post.id');

        $result=$Cate->where('id=%d',['%d'=>$id])->find();
        //二级栏目删除
        if($result['level']==1){
            if($Cate->where('id=%d',['%d'=>$id])->delete()){
                $cates=$Cate->where("parent=%d",['%d'=>$result['parent']])->select();
                foreach($cates as $v){
                    if($v['sort']>$result['sort']){
                        $data['sort']=$v['sort']-1;
                        if($Cate->where('id=%d',['%d'=>$v['id']])->save($data)===false){
                            $data1['error']=1;
                            $data1['message']='栏目删除失败!';
                            $this->ajaxReturn($data1);
                        }
                    }
                }
                $data1['error']=0;
                $data1['message']='栏目删除成功!';
                $this->ajaxReturn($data1);
            }else{
                $data1['error']=1;
                $data1['message']='栏目删除失败!';
                $this->ajaxReturn($data1);
            }
        }
        //一级栏目删除
        if($result['level']==0){
            //删除一级栏目
            if($Cate->where('id=%d',['%d'=>$id])->delete()){
                $cates=$Cate->where("parent=%d",['%d'=>$result['parent']])->select();
                foreach($cates as $v){
                    if($v['sort']>$result['sort']){
                        $data['sort']=$v['sort']-1;
                        if($Cate->where('id=%d',['%d'=>$v['id']])->save($data)===false){
                            $data1['error']=1;
                            $data1['message']='栏目删除失败!';
                            $this->ajaxReturn($data1);
                        }
                    }
                }
                //找到并删除二级栏目
                if($Cate->where('parent=%d',['%d'=>$result['id']])->select()){
                    if($Cate->where('parent=%d',['%d'=>$result['id']])->delete()){
                        $data1['error']=0;
                        $data1['message']='栏目删除成功!';
                        $this->ajaxReturn($data1);
                    }
                }else{
                    $data1['error']=0;
                    $data1['message']='栏目删除成功!';
                    $this->ajaxReturn($data1);
                }
            }else{
                $data1['error']=1;
                $data1['message']='栏目删除失败!';
                $this->ajaxReturn($data1);
            }
        }
    }

    //栏目上升
    public function upAction()
    {
        $id = I('get.id');

        $cate = M('Category');

        $cate1 = $cate -> where('id=%d',['%d'=>$id])->find();
        if($cate1['sort'] == 1){
            $data1['error']=1;
            $data1['message']='栏目上移失败!';
            $this->ajaxReturn($data1);
        }
        $sort = $cate1['sort'];
        $parent = $cate1['parent'];

        $cate2 = $cate -> where("sort=%d and parent=%d",array($sort-1,$parent))->find();

        $data1['id'] = $id;
        $data1['sort'] = $sort - 1;

        $data2['id'] =$cate2['id'];
        $data2['sort'] = $sort;

        $result1 = $cate -> data($data1) ->save();
        $result2 = $cate -> data($data2) ->save();

        if($result1!==false && $result2!==false){
            $data1['error']=0;
            $data1['message']='栏目上移成功!';
            $this->ajaxReturn($data1);
        }

    }

    //栏目下移
    public function downAction()
    {
        $id = I('get.id');

        $cate = M('Category');

        $cate1 = $cate -> where('id=%d',['%d'=>$id])->find();
        $sort = $cate1['sort'];
        $parent = $cate1['parent'];
        $cate2=$cate->where("parent=%d",array($parent))->order('sort desc')->find();
        if($cate1['sort']==$cate2['sort']){
            $data1['error']=1;
            $data1['message']='栏目下移失败!';
            $this->ajaxReturn($data1);
        }
        $cate3 = $cate -> where("sort=%d and parent=%d",array($sort+1,$parent))->find();

        $data1['id']=$id;
        $data1['sort']=$sort+1;

        $data2['id']=$cate3['id'];
        $data2['sort']=$sort;

        $result1 = $cate -> data($data1) ->save();
        $result2 = $cate -> data($data2) ->save();

        if($result1!==false && $result2!==false){
            $data1['error']=0;
            $data1['message']='栏目下移成功!';
            $this->ajaxReturn($data1);
        }

    }
}