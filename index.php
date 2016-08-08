<?php
/**
 * Created by PhpStorm.
 * User: dragon-w
 * Date: 16/5/1
 * Time: 11:06
 */
include 'worker.php';
set_time_limit(0);
define('DEFAULT_LEVEL',5);

/*
 * 1.读取源图,计算出原图的缩放等级:$level=>ceil(高度/256),
 * 2.循环缩放原图$level次,得到每层的原图
 * 3.分别读取每层的原图,获取其的宽度和高度, 得到 网格的行与列的数量,$row = ceil(width/256),$col=ceil(height/256)
 * 4.使用两个for循环,裁剪该层图片,并存储到对应的文件夹中
 */



//1
$source_path="./gis.png";

if(!file_exists($source_path)){

    exit('源文件不存在!');
}

$source_info   = getimagesize($source_path);

$source_width  = $source_info[0];
$source_height = $source_info[1];
$source_ratio  = $source_height/$source_width; //高宽比


//临时图层目录处理
$root=__DIR__.'/temp/';

if(!file_exists($root)){

    mkdir($root);

}else{

    if($handle=opendir($root)){

        while(false!==($item=readdir($handle))){

            if($item=="." || $item==".."){

                continue;
            }

            $item=rtrim($root,"/")."/".$item;

            if(is_dir($item)){
                continue;
            }
            @unlink($item);
        }
    }
}


//对原图进行缩放处理
for($i=0;$i<DEFAULT_LEVEL;$i++){

    $target_path=$root.$i.'.png';

    if($i==0){

        if($source_ratio>1){
            $target_height=256;
        }else{
            $target_width=256;
        }

    }elseif($i==1){

        if($source_ratio>1){
            $target_height=512;
        }else{
            $target_width=512;
        }


    }else{

        $l=pow(2,$i);

        if($source_ratio>1){
            $target_height=256*$l;
        }else{
            $target_width=256*$l;
        }

    }


    if($source_ratio>1){

        $target_width=$source_width*$target_height/$source_height;

    }else{

        $target_height=$source_height*$target_width/$source_width;
    }

    resize('./',$root,'gis.png',$i.'.png',$target_width,$target_height);
}


$tmp=array();

$work_tile_map=array();

//生成层级切图任务列表
for($z=0;$z<DEFAULT_LEVEL;$z++){

    $source_path=$root.$z.'.png';

    $work_tile_map[]=array(
        'z'=>$z,
        'sourse_path'=>$source_path
    );
}


//循环切图
foreach($work_tile_map as $val){

    create_tile($val['sourse_path'],$val['z']);

    echo "done...";

}