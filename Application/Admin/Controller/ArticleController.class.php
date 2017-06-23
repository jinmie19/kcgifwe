<?php
namespace Admin\Controller;
use Admin\Model\CategoryModel;
use Home\Model\ArticleModel;
use Think\Controller;

class ArticleController extends Controller
{
    public function listAction()
    {
        $Article=new ArticleModel();
        $Cate=new CategoryModel();
        $results=array();
        foreach($Cate->select() as $m){
            foreach($Article->order('sort asc')->select() as $n){
                if($m['id']==$n['parent']){
                    $results[]=$n;
                }
            }
        }
        $this->assign('cates',$Cate->select());
        $this->assign('results',$results);
        $this->display();
    }

    public function examineAction(){
        $Article=new ArticleModel();
        $Cate=new CategoryModel();

        $this->assign('cates',$Cate->select());
        $this->assign('art',$Article->where('id=%d',['%d'=>I('get.id')])->find());
        $this->display();
    }
    public function addAction()
    {
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
            $data['isWork']=@I('post.isWork')?1:0;
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
            $this->assign('cates',$Cate->subtree($Cate->order('sort asc')->select()));
            $this->display();
        }
    }

    public function editAction()
    {
        $Cate=new CategoryModel();
        $Art=new ArticleModel();
        if(IS_POST){
            $data['parent']=I('post.parent');
            $cate=$Cate->where('id=%d',['%d'=>I('post.parent')])->find();
            $data['level']=$cate['level']?2:1;
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
            $data['isWork']=@I('post.isWork')?1:0;
            //$data['uid']=session('uid');
            $result=$Art->where('id=%d',['%d'=>I('post.id')])->save($data);
            //var_dump($result);
            if($result!==false){
                $this->ajaxResult(0,'文章修改成功!');
            }else{
                $this->ajaxResult(1,'文章修改失败!');
            }
        }else{
            $data=[
              'cates'=>$Cate->subtree($Cate->order('sort asc')->select()),
               'art'=>$Art->where('id=%d',['%d'=>I('get.id')])->find(),
            ];
            $this->assign($data);
            $this->display();
        }
    }

    //文章删除
    public function delAction(){
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
                            $this->ajaxResult(1,'文章删除失败!');
                        }
                    }
                }
                $this->ajaxResult(0,'文章删除成功!');
            }else{
                $this->ajaxResult(1,'文章删除失败!');
            }
        }
    }

    //栏目上移
    public function upAction()
    {
        $Article=new ArticleModel();
        $id=I('post.id');
        $art=$Article->where('id=%d',['%d'=>$id])->find();
        if($art['sort']==1){
            $this->ajaxResult(1,'文章上移失败!');
        }
        $parent=$art['parent'];
        $art1=$Article->where('parent=%d and sort=%d',[$parent,$art['sort']-1])->find();

        $result1=$Article->where('id=%d',['%d'=>$id])->data(['sort'=>$art['sort']-1])->save();
        $result2=$Article->where('id=%d',['%d'=>$art1['id']])->data(['sort'=>$art1['sort']+1])->save();
        if($result1!==false && $result2!==false){
            $this->ajaxResult(0,'文章上移成功!');
        }
    }

    //栏目下移
    public function downAction()
    {
        $Article=new ArticleModel();
        $id=I('post.id');
        $art=$Article->where('id=%d',['%d'=>$id])->find();
        $art1=$Article->where('parent=%d',[$art['parent']])->order('sort desc')->find();
        if($art['sort']==$art1['sort']){
            $this->ajaxResult(1,'文章下移失败!');
        }
        $parent=$art['parent'];
        $art1=$Article->where('parent=%d and sort=%d',[$parent,$art['sort']+1])->find();

        $result1=$Article->where('id=%d',['%d'=>$id])->data(['sort'=>$art['sort']+1])->save();
        $result2=$Article->where('id=%d',['%d'=>$art1['id']])->data(['sort'=>$art1['sort']-1])->save();
        if($result1!==false && $result2!==false){
            $this->ajaxResult(0,'文章下移成功!');
        }
    }
    function ajaxResult($error=1,$message='error'){
        $data['error']=$error;
        $data['message']=$message;
        $this->ajaxReturn($data);
    }
}