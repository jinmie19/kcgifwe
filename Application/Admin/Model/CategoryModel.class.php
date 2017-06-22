<?php
namespace Admin\Model;
use Think\Model;

class CategoryModel extends Model{



    // 开启批量验证
    public $patchValidate = true;

    protected $_validate=[
        ['name','require','请输入栏目名',self::EXISTS_VALIDATE],
        ['name','','栏目名不能重复',self::EXISTS_VALIDATE,'unique'],
        ['type','require','请输入栏目类型',self::EXISTS_VALIDATE],
        ['parent','require','请输入上级栏目',self::EXISTS_VALIDATE],
        ['title','require','请输入SEO标题',self::EXISTS_VALIDATE],
        ['keyWord','require','请输入SEO关键字',self::EXISTS_VALIDATE],
        ['description','require','请输入SEO描述',self::EXISTS_VALIDATE],
    ];

    function subtree($arr,$id=0,$level=0) {
        $subs = array(); // 子孙数组
        foreach($arr as $v) {
            if($v['parent'] == $id) {
                $v['level'] = $level;
                $subs[] = $v;
                $subs = array_merge($subs,$this->subtree($arr,$v['id'],$level+1));
            }
        }
        return $subs;
    }
}


