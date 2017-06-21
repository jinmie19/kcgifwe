<?php
namespace Home\Model;
use Think\Model;

class ArticleModel extends Model{


    protected $trueTableName = 'kc_article';

    // 开启批量验证
    public $patchValidate = true;

    protected $_validate=[

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


