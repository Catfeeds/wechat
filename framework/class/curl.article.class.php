<?php
load()->func('communication');
load()->func('check');
load()->func('file');
class CurlArticle{
    private $_url = "";//文章地址
    private $_content = "";//文章内容html

    public function __construct($url){
        $this->_url = $url;
        $result = ihttp_get($this->_url);
        if(!empty($result['content'])){
            $this->_content = $result['content'];
        }
    }


    /* 微信文章抓取 */
    public function getContent(){
        if(empty($this->_content)){
            return null;
        }
        $images = getImagesFromHtml($this->_content);
        if(check_data($images)){
            foreach($images as $k => $image){
                $src = $this->_imagesSaveByUrl($image);
                if(!empty($src)){
                    $this->_content = str_replace($image,$src,$this->_content);
                }
            }
        }
        return $this->_content;
    }


    /**
     * @param $url
     * @return null|string
     * 保存远程图片
     */
    private function _imagesSaveByUrl($url){
        load()->func('communication');
        $resp = ihttp_get($url);
        $pathname = base64ToImage(base64_encode($resp['content']));
        return tomedia($pathname);
    }
}