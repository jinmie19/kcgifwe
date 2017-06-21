<?php
namespace Home\Controller;

use Home\Model\ArticleModel;
use Home\Model\CategoryModel;
use Think\Controller;
date_default_timezone_set('prc');

class ArticleController extends Controller
{
    public function indexAction(){
        $Article=new ArticleModel();
        $Cate=new CategoryModel();
        $results=array();
        foreach($Cate->select() as $m){
            foreach($Article->select() as $n){
                if($m['id']==$n['parent']){
                    $results[]=$n;
                }
            }
        }
        $this->assign('cates',$Cate->select());
        $this->assign('results',$results);
        $this->display();
    }

    public function addAction(){
        $Article=new ArticleModel();
        $Cate=new CategoryModel();
        if(IS_POST){
            $cate1=$Cate->where('id=%d',['%d'=>I('post.parent')])->find();
            $art1=$Article->where('parent=%d',['%d'=>I('post.parent')])->order('sort desc')->find();
            $data['parent']=I('post.parent');
            $data['source']=I('post.source');
            $data['name']=I('post.name');
            $data['intro']=I('post.intro');
            $data['status']=I('post.status');
            $data['content']=I('post.content');
            $data['author']=I('post.author');
            $data['title']=I('post.title');
            $data['keyWord']=I('post.keyWord');
            $data['description']=I('post.description');
            $data['created']=time();
            $data['updated']=time();
            //$data['uid']=session('uid');
            $data['level']=(int)$cate1['level']+1;
            $data['sort']=(int)$art1['sort']+1;
            if($Article->add($data)){
                $data['error']=0;
                $data['message']='文章添加成功!';
                $this->ajaxReturn($data);
            }else{
                $data['error']=1;
                $data['message']='文章添加失败!';
                $this->ajaxReturn($data);
            }
        }else{
            $this->assign('results',$Cate->select());
            $this->display();
        }
    }

    //文章删除
    public function deleteAction(){
        if(IS_POST){
            $id=I('post.id');
            $Article=new ArticleModel();
            $art=$Article->where('id=%d',['%d'=>$id])->find();
            if($Article->where('id=%d',['%d'=>$id])->delete()){
                //重新排序
                $result=$Article->where('parent=%d',['%d'=>$art['parent']])->select();
                foreach($result as $v){
                    if($v['sort']>$art['sort']){
                        $data['sort']=$v['sort']-1;
                        if($Article->where('id=%d',['%d'=>$v['id']])->save($data)===false){
                            $data1['error']=1;
                            $data1['message']='栏目删除失败!';
                            $this->ajaxReturn($data1);
                        }
                    }
                }
                $data['error']=0;
                $data['message']='文章删除成功!';
                $this->ajaxReturn($data);
            }else{
                $data['error']=1;
                $data['message']='文章删除失败!';
                $this->ajaxReturn($data);
            }
        }
    }

    //文章编辑
    public function updateAction(){
        $Cate=new CategoryModel();
        $Art=new ArticleModel();
        $id=I('get.id');
        if(IS_POST){
            $data['parent']=I('post.parent');
            $data['source']=I('post.source');
            $data['name']=I('post.name');
            $data['intro']=I('post.intro');
            //$data['img']=I('post.img');
            //$data['file']=I('post.file');
            $data['status']=I('post.status');
            $data['content']=I('post.content');
            $data['author']=I('post.author');
            $data['title']=I('post.title');
            $data['keyWord']=I('post.keyWord');
            $data['description']=I('post.description');
            $data['updated']=time();
            //$data['uid']=session('uid');
            $result=$Art->where('id=%d',['%d'=>I('post.id')])->save($data);
            //var_dump($result);
            if($result!==false){
                $data1['error']=0;
                $data1['message']='文章修改成功!';
                $this->ajaxReturn($data1);
            }else{
                $data1['error']=1;
                $data1['message']='文章修改失败!';
                $this->ajaxReturn($data1);
            }
        }else{
            $data=array('cate'=>$Cate->select(),'art'=>$Art->where('id=%d',['%d'=>$id])->find());
            $this->assign($data);
            $this->display();
        }
    }

    //栏目上移
    public function upAction()
    {
        $Article=new ArticleModel();
        $id=I('get.id');
        $art=$Article->where('id=%d',['%d'=>$id])->find();
        if($art['sort']==1){
            $data1['error']=1;
            $data1['message']='文章上移失败!';
            $this->ajaxReturn($data1);
        }
        $parent=$art['parent'];
        $art1=$Article->where('parent=%d and sort=%d',[$parent,$art['sort']-1])->find();

        $result1=$Article->where('id=%d',['%d'=>$id])->data(['sort'=>$art['sort']-1])->save();
        $result2=$Article->where('id=%d',['%d'=>$art1['id']])->data(['sort'=>$art1['sort']+1])->save();
        if($result1!==false && $result2!==false){
            $data1['error']=0;
            $data1['message']='文章上移成功!';
            $this->ajaxReturn($data1);
        }
    }

    //栏目下移
    public function downAction()
    {
        $Article=new ArticleModel();
        $id=I('get.id');
        $art=$Article->where('id=%d',['%d'=>$id])->find();
        $art1=$Article->where('parent=%d',[$art['parent']])->order('sort desc')->find();
        if($art['sort']==$art1['sort']){
            $data1['error']=1;
            $data1['message']='文章下移失败!';
            $this->ajaxReturn($data1);
        }
        $parent=$art['parent'];
        $art1=$Article->where('parent=%d and sort=%d',[$parent,$art['sort']+1])->find();

        $result1=$Article->where('id=%d',['%d'=>$id])->data(['sort'=>$art['sort']+1])->save();
        $result2=$Article->where('id=%d',['%d'=>$art1['id']])->data(['sort'=>$art1['sort']-1])->save();
        if($result1!==false && $result2!==false){
            $data1['error']=0;
            $data1['message']='文章下移成功!';
            $this->ajaxReturn($data1);
        }
    }

    //上传组件
    public function uploadAction(){
        if(IS_POST){
            $upload = new \Think\Upload();
            $upload->maxSize = 10240000 ;
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
            $upload->rootPath = './Public/Artimg/';
            $upload->savePath = '';
            $upload->autoSub = true;
            $upload->subName = array('date','Y-m-d');
            $info = $upload->upload();
            if($info){
                foreach($info as $file){
                    $data=['status'=>1];
                    return $data;
                }
            }else{

                $data=['status'=>0];
                return $data;
            }

        }
    }
}