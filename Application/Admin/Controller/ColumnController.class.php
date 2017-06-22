<?php
namespace Admin\Controller;
use Admin\Model\CategoryModel;
use Think\Controller;

class ColumnController extends Controller
{
    public function listAction()
    {
        $Cate=new CategoryModel();
        if(IS_POST){

        }else{
            $results=$Cate->subtree($Cate->order('sort asc')->select());
            $this->assign('results',$results);
            $this->display();
        }
    }

    public function addAction()
    {
        $Cate=new CategoryModel();
        if(IS_POST){
            if (!$Cate->create(I('post.'))){
                foreach($Cate->getError() as $v){
                    $this->ajaxResult(1,$v);
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
                $this->ajaxResult(0,'栏目添加成功!');
            }else{
                $this->ajaxResult(1,'栏目添加失败!');
            }
        }else{
            $results=$Cate->select();
            $this->assign('results',$results);
            $this->display();
        }
    }

    public function examineAction(){
        $Cate=new CategoryModel();
        $id=I('get.id');
        $result=$Cate->where('id=%d',['%d'=>$id])->find();
        $results=$Cate->select();
        $this->assign('result',$result);
        $this->assign('results',$results);
        $this->display();
    }

    public function editAction()
    {
        $Cate=new CategoryModel();
        if(IS_POST){
            if (!$Cate->create(I('post.'))){
                foreach($Cate->getError() as $v){
                    $this->ajaxResult(1,$v);
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
                $this->ajaxResult(0,'栏目更新成功!');
            }else{
                $this->ajaxResult(1,'栏目更新失败!');
            }
        }else{
            $id=I('get.id');
            $result=$Cate->where('id=%d',['%d'=>$id])->find();
            $results=$Cate->select();
            $this->assign('result',$result);
            $this->assign('results',$results);
            $this->display();
        }
    }

    public function delAction(){
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
                            $this->ajaxResult(1,'栏目删除失败!');
                        }
                    }
                }
                $this->ajaxResult(0,'栏目删除成功!');
            }else{
                $this->ajaxResult(1,'栏目删除失败!');
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
                            $this->ajaxResult(1,'栏目删除失败!');
                        }
                    }
                }
                //找到并删除二级栏目
                if($Cate->where('parent=%d',['%d'=>$result['id']])->select()){
                    if($Cate->where('parent=%d',['%d'=>$result['id']])->delete()){
                        $this->ajaxResult(0,'栏目删除成功!');
                    }
                }else{
                    $this->ajaxResult(0,'栏目删除成功!');
                }
            }else{
                $this->ajaxResult(1,'栏目删除失败!');
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
            $this->ajaxResult(1,'栏目上移失败!');
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
            $this->ajaxResult(0,'栏目上移成功!');
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
            $this->ajaxResult(1,'栏目下移失败!');
        }
        $cate3 = $cate -> where("sort=%d and parent=%d",array($sort+1,$parent))->find();
        $data1['id']=$id;
        $data1['sort']=$sort+1;
        $data2['id']=$cate3['id'];
        $data2['sort']=$sort;
        $result1 = $cate -> data($data1) ->save();
        $result2 = $cate -> data($data2) ->save();
        if($result1!==false && $result2!==false){
            $this->ajaxResult(0,'栏目上移成功!');
        }

    }
   function ajaxResult($error=1,$message='error'){
        $data['error']=$error;
        $data['message']=$message;
        $this->ajaxReturn($data);
    }
}