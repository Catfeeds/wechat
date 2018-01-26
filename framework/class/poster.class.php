<?php
/**
 * 图像处理类
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/4/16
 * Time: 19:32
 */
class Poster{
    /**
     * @var null
     * 海报配置信息
     */
    private $_platformConfig = null;

    //初始化函数
    public function __construct($poster_config){
        $this->_platformConfig = $poster_config;
    }

    /* 创建平台关注二维码 */
    public function getPlatformPosterImage($avatar,$nickname,$focus_code){
        global $_W;
        if(empty($this->_platformConfig['bg'])){
            message('背景图片不存在','','error');
        }
        header('Content-Type: image/png');
        $panel = $this->_createDrawPanel(POSTER_WIDTH,POSTER_HEIGHT);
        if($panel === false){
            message('画布创建失败，请联系管理员','','error');
        }

        //合并背景
        $bg_file = $this->_getImageTrueSrc($this->_platformConfig['bg']);
        $this->_mergeImageToPanel($bg_file,$panel,0,0,POSTER_WIDTH,POSTER_HEIGHT);

        //合并头像
        if($_W['uniacid'] != 3){
            $avatar_file = $this->_getImageTrueSrc($avatar);
            $this->_mergeImageToPanel($avatar_file,$panel,$this->_platformConfig['avatar_left'],$this->_platformConfig['avatar_top'],$this->_platformConfig['avatar_size'],$this->_platformConfig['avatar_size']);
            $this->_mergeImageToPanel(
                $avatar_file,
                $panel,
                $this->_platformConfig['code_left'] + ($this->_platformConfig['code_size'] - POSTER_CODE_AVATAR_WIDTH) / 2,
                $this->_platformConfig['code_top'] + ($this->_platformConfig['code_size'] - POSTER_CODE_AVATAR_HEIGHT) / 2,
                POSTER_CODE_AVATAR_WIDTH,
                POSTER_CODE_AVATAR_HEIGHT
            );

            //输出昵称
            $this->_writeStringToPanel(
                $panel,
                $this->_platformConfig['color'],
                $this->_platformConfig['size'],
                $this->_platformConfig['nickname_left'],
                $this->_platformConfig['nickname_top'],
                !$nickname?'':$nickname
            );

            //输出广告语
            $this->_writeStringToPanel(
                $panel,
                $this->_platformConfig['color'],
                $this->_platformConfig['size'],
                $this->_platformConfig['ad_left'],
                $this->_platformConfig['ad_top'],
                $this->_platformConfig['ad']
            );
        }

        //合并二维码
        $code_file = $this->_getImageTrueSrc($focus_code);
        $this->_mergeImageToPanel($code_file,$panel,$this->_platformConfig['code_left'],$this->_platformConfig['code_top'],$this->_platformConfig['code_size'],$this->_platformConfig['code_size']);

        //输出资源
        imagepng($panel);
        imagedestroy($panel);
    }

    /**
     * @param $panel
     * @param $color
     * @param int $font_size
     * @param $x
     * @param $y
     * @param $text
     * @param string $font_type
     * 在面板上写字符串
     */
    private function _writeStringToPanel($panel,$color,$font_size,$x,$y,$text,$font_type = 'msyh'){
        list($red,$green,$blue) = colorHex2Rgb($color);
        //imagestring($panel, $font_size, $x, $y, $text, imagecolorallocate($panel,$red,$green,$blue));
        imagettftext($panel,$font_size,0,$x,$y,imagecolorallocate($panel,$red,$green,$blue),$this->_getFontFamilyPath($font_type),$text);
    }

    /**
     * @param string $type
     * @return string
     * 返回字体路径
     */
    private function _getFontFamilyPath($type = 'msyh'){
        if($type == 'msyh'){
            return IA_ROOT."/assets/common/font/msyh.ttc";
        }
        return IA_ROOT."/assets/common/font/huawen/{$type}.TTF";
    }

    /**
     * @param string $path
     * @return string
     * 获取文件真实路径
     */
    private function _getImageTrueSrc($path = ''){
        if(strpos($path,'images') === 0){
            return IA_ROOT.'/attachment/'.$path;
        }
        return $path;
    }


    /**
     * @param $img_file
     * @param $panel
     * @param $from_x
     * @param $from_y
     * @param $from_w
     * @param $from_h
     * 合并画布
     */
    private function _mergeImageToPanel($img_file,$panel,$from_x,$from_y,$from_w,$from_h){
        $img = $this->_getImageCurlByString($img_file);
        if($img === false){
            message('图片读取失败'.$img_file,'','error');
        }
        list($bg_width, $bg_height) = getimagesize($img_file);
        $merge_status = $this->_copySampled($panel,$img,$from_x,$from_y,0,0,$from_w,$from_h,$bg_width,$bg_height);
        if(!$merge_status){
            message('图像合并失败','','error');
        }
        imagedestroy($img);
    }



    /**
     * @param int $width
     * @param int $height
     * @return bool|resource
     * 创建画布
     */
    private function _createDrawPanel($width =0 ,$height = 0){
        $img = @imagecreatetruecolor($width, $height);
        if(!$img){
            return false;
        }
        return $img;
    }

    /**
     * @param string $filename
     * @return bool|resource
     * 根据地址或者URL获取图像操作句柄
     */
    private function _getImageCurlByString($filename = ''){
        if(empty($filename) || !is_string($filename)){
            return false;
        }
        if(strpos($filename,'http') === 0 || strpos($filename,'https') === 0){
            load()->func('communication');
            $resp = ihttp_request($filename);
            if (($resp['code'] == 200) && !(empty($resp['content']))) {
                return imagecreatefromstring($resp['content']);
            }
            $i = 0;
            while ($i < 3) {
                $resp = ihttp_request($filename);
                if (($resp['code'] == 200) && !(empty($resp['content']))) {
                    return imagecreatefromstring($resp['content']);
                }
                ++$i;
            }
            return '';
        }else{
            //本地文件
            $content = file_get_contents($filename);
        }

        $img = @imagecreatefromstring($content);
        if(!$img){
            return false;
        }
        return $img;
    }

    /**
     * @param $from_img
     * @param $to_image
     * @param $from_x
     * @param $from_y
     * @param $to_x
     * @param $to_y
     * @param $from_w
     * @param $from_h
     * @param $to_w
     * @param $to_h
     * @return bool
     * 重采样拷贝部分图像并调整大小
     */
    private function _copySampled($from_img,$to_image,$from_x,$from_y,$to_x,$to_y,$from_w,$from_h,$to_w,$to_h){
        $img = @imagecopyresampled ($from_img , $to_image , $from_x, $from_y , $to_x , $to_y , $from_w , $from_h , $to_w , $to_h );
        if(!$img){
            return false;
        }
        return $img;
    }
}