<?php
/**
 * Created by PhpStorm.
 * User: dragon-w
 * Date: 16/5/2
 * Time: 12:01
 */

//得到某一层级的行数
function get_rows($level){

    if($level==0){

        return 0;

    }else{

        return get_rows($level-1)*2+1;
    }

}


//切图函数
function create_tile($source_path,$z){

    if(!file_exists($source_path)){

        exit($source_path.'文件不存在');

    }


    list($source_width, $source_height, $or_t) = getimagesize($source_path);

    $path = __DIR__.'/tile/' . $z.'/';


    if (!file_exists($path)) { //层级

        @mkdir($path);
    }


    $level=ceil($source_height/256);


    $rows=get_rows($z+1); //层级比行标号大1


    for ($x = 0; $x <$rows; $x++) {  //行


        $path = __DIR__ . '/tile/' . $z . '/' . $x . '/';

        if (!file_exists($path)) {

            @mkdir($path);
        }


        for ($y = 0; $y <=(pow(2,$z)-1); $y++) {  //瓦片图

            $img_path= __DIR__.'/tile/' . $z.'/'.$x.'/'.$y.'.png';

            $source_x=$x*256; //列宽
            $source_y=$y*256; //行高


            if($source_x>$source_width || $source_y > $source_height){ //在行或者列上已经没有可切的了

                copy('none.png',$img_path);

            }else{

                $target_x=$target_y=256;

                if(($x+1)*256 > $source_width ){ //宽度上不够了

                    $target_x=$source_width%256;

                }

                if(($y+1)*256 > $source_height){ //高度上不够了

                    $target_y=$source_height%256;

                }


                $source_image = imagecreatefrompng($source_path);
                $target_image  = imagecreatetruecolor(256,256); //创建图片对象

                imagesavealpha($target_image, true);
                $trans_colour = imagecolorallocatealpha($target_image, 0, 0, 0, 127);
                imagefill($target_image, 0, 0, $trans_colour);

                imagecopy($target_image, $source_image, 0, 0, $source_x, $source_y, $target_x, $target_y);
                imagepng($target_image,$img_path);

                imagedestroy($source_image);
                imagedestroy($target_image);
            }
        }

    }
}

function resize($dir, $newdir, $img, $newimg, $max_w, $max_h, $th_x = '', $th_y = '', $th_w = '', $th_h = '',$cut = FALSE,$center = FALSE)
{

    // set destination directory
    if (!$newdir) $newdir = $dir;
    if (!$newimg) $newimg = $img;

    // get original images width and height
    list($or_w, $or_h, $or_t) = getimagesize($dir.$img);



    switch($or_t){

        // original image
        case 1:
            $or_image = imagecreatefromgif($dir.$img);
            break;
        case 2:
            $or_image = imagecreatefromjpeg($dir.$img);
            break;
        case 3:
            $or_image = imagecreatefrompng($dir.$img);
            break;
        default:
            return '不支持的图像格式';
        break;

    }


    $ratio = ($max_h / $max_w);


    if ($or_w > $max_w || $or_h > $max_h) {

        // resize by height, then width (height dominant)
        if ($max_h < $max_w) {
            $rs_h = $max_h;
            $rs_w = $rs_h / $ratio;
        }

        // resize by width, then height (width dominant)
        else {
            $rs_w = $max_w;
            $rs_h = $ratio * $rs_w;
        }

        // copy old image to new image
        $rs_image = imagecreatetruecolor($rs_w, $rs_h);
        imagecopyresampled($rs_image, $or_image, 0, 0, 0, 0, $rs_w, $rs_h, $or_w, $or_h);

    } else { // image requires no resizing
        $rs_w = $or_w;
        $rs_h = $or_h;
        $rs_image = $or_image;
    }

    imagepng($rs_image,$newdir.$newimg);

    @ImageDestroy($rs_image);
}