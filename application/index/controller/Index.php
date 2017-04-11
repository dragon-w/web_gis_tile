<?php

namespace app\index\controller;

define('DEFAULT_LEVEL', 5);

class Index
{

    private $source_path;
    private $root;
    private $tile_level;
    private $tile_path;


    public function __construct()
    {
        $this->source_path = "./static/gis.png";
        $this->root = './static/temp/';  //临时图层目录处理
        $this->tile_path='./static/tile/';
        $this->tile_level = 5;
    }


    /**
     * 1.读取源图,计算出原图的缩放等级:$level=>ceil(高度/256),
     * 2.循环缩放原图$level次,得到每层的原图
     * 3.分别读取每层的原图,获取其的宽度和高度, 得到 网格的行与列的数量,$row = ceil(width/256),$col=ceil(height/256)
     * 4.使用两个for循环,裁剪该层图片,并存储到对应的文件夹中
     */
    public function create_level_img()
    {

//        phpinfo();die();

        set_time_limit(0);

        if (!file_exists($this->source_path)) {

            exit('源文件不存在!');
        }

        $source_info = getimagesize($this->source_path);

        $source_width = $source_info[0];
        $source_height = $source_info[1];
        $source_ratio = $source_height / $source_width; //高宽比


        if (!file_exists($this->root)) {

            mk_dir($this->root);

        } else {

            if ($handle = opendir($this->root)) {

                while (false !== ($item = readdir($handle))) {

                    if ($item == "." || $item == "..") {

                        continue;
                    }

                    $item = $this->root . $item;

                    if (is_dir($item)) {
                        continue;
                    }
                    @unlink($item);
                }
            }
        }


        //对原图进行缩放处理
        for ($i = 0; $i < $this->tile_level; $i++) {

            $target_path = $this->root . $i . '.png';

            if ($i == 0) {

                if ($source_ratio > 1) {
                    $target_height = 256;
                } else {
                    $target_width = 256;
                }

            } elseif ($i == 1) {

                if ($source_ratio > 1) {
                    $target_height = 512;
                } else {
                    $target_width = 512;
                }


            } else {

                $l = pow(2, $i);

                if ($source_ratio > 1) {
                    $target_height = 256 * $l;
                } else {
                    $target_width = 256 * $l;
                }

            }


            if ($source_ratio > 1) {

                $target_width = $source_width * $target_height / $source_height;

            } else {

                $target_height = $source_height * $target_width / $source_width;
            }

            $this->resize($this->source_path, $this->root, $i . '.png', $target_width, $target_height);
        }

        echo "创建层级图片完成";

    }


    /**
     * 创建瓦片图
     */
    public function create_tile_img(){

        $tmp = array();
        $work_tile_map = array();

        //生成层级切图任务列表
        for ($z = 0; $z < $this->tile_level; $z++) {

            $this->source_path = $this->root . $z . '.png';

            $work_tile_map[] = array(
                'z' => $z,
                'sourse_path' => $this->source_path
            );
        }


        //循环切图
        foreach ($work_tile_map as $val) {

            $this->create_tile($val['sourse_path'], $val['z']);

//            echo $val."done...";

        }

    }

    /**
     * @param $dir
     * @param $newdir
     * @param $img
     * @param $newimg
     * @param $max_w
     * @param $max_h
     * @param string $th_x
     * @param string $th_y
     * @param string $th_w
     * @param string $th_h
     * @param bool $cut
     * @param bool $center
     */
    private function resize($img_path, $newdir, $newimg, $max_w, $max_h, $th_x = '', $th_y = '', $th_w = '', $th_h = '', $cut = FALSE, $center = FALSE)
    {

        //图片的宽，高，类型
        list($or_w, $or_h, $or_t) = getimagesize($img_path);

        switch ($or_t) {

            // original image
            case 1:
                $or_image = imagecreatefromgif($img_path);
                break;
            case 2:
                $or_image = imagecreatefromjpeg($img_path);
                break;
            case 3:
                $or_image = imagecreatefrompng($img_path);
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
            } // resize by width, then height (width dominant)
            else {
                $rs_w = $max_w;
                $rs_h = $ratio * $rs_w;
            }

            // copy old image to new image
            $rs_image = imagecreatetruecolor($rs_w, $rs_h);


            imagesavealpha($rs_image, true);
            $trans_colour = imagecolorallocatealpha($rs_image, 0, 0, 0, 127);
            imagefill($rs_image, 0, 0, $trans_colour);

            imagecopyresampled($rs_image, $or_image, 0, 0, 0, 0, $rs_w, $rs_h, $or_w, $or_h);

        } else { // image requires no resizing
            $rs_w = $or_w;
            $rs_h = $or_h;
            $rs_image = $or_image;
        }

        imagepng($rs_image, $newdir . $newimg);

        @ImageDestroy($rs_image);
    }

    /**
     * 得到某一层级的行数
     * @param $level
     * @return int
     */
    private function get_rows($level)
    {

        if ($level == 0) {

            return 0;

        } else {

            return $this->get_rows($level - 1) * 2 + 1;
        }

    }

    /**
     * //切图函数
     * @param $this ->source_path
     * @param $z
     */
    private function create_tile($source_path, $z)
    {

        set_time_limit(0);

//        echo $source_path;die();

        if (!file_exists($source_path)) {

            exit($source_path . '文件不存在');

        }


        list($source_width, $source_height, $or_t) = getimagesize($source_path);

        $path = $this->tile_path . $z . '/';

        if (!file_exists($path)) { //层级

            @mk_dir($path);
        }

        $level = ceil($source_height / 256);


        $rows = $this->get_rows($z + 1); //层级比行标号大1


        for ($x = 0; $x < $rows; $x++) {  //行


            $path = $this->tile_path. $z . '/' . $x . '/';

            if (!file_exists($path)) {

                @mk_dir($path);
            }


            for ($y = 0; $y <= (pow(2, $z) - 1); $y++) {  //瓦片图

                $img_path = $this->tile_path. $z . '/' . $x . '/' . $y . '.png';

                $source_x = $x * 256; //列宽
                $source_y = $y * 256; //行高


                if ($source_x > $source_width || $source_y > $source_height) { //在行或者列上已经没有可切的了

                    copy('./static/none.png', $img_path);

                } else {

                    $target_x = $target_y = 256;

                    if (($x + 1) * 256 > $source_width) { //宽度上不够了

                        $target_x = $source_width % 256;

                    }

                    if (($y + 1) * 256 > $source_height) { //高度上不够了

                        $target_y = $source_height % 256;

                    }


                    $source_image = imagecreatefrompng($this->source_path);
                    $target_image = imagecreatetruecolor(256, 256); //创建图片对象

                    imagesavealpha($target_image, true);
                    $trans_colour = imagecolorallocatealpha($target_image, 0, 0, 0, 127);
                    imagefill($target_image, 0, 0, $trans_colour);

                    imagecopy($target_image, $source_image, 0, 0, $source_x, $source_y, $target_x, $target_y);
                    imagepng($target_image, $img_path);

                    imagedestroy($source_image);
                    imagedestroy($target_image);
                }
            }

        }
    }
}
