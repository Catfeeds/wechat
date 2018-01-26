<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/3/21
 * Time: 21:36
 */
class Point{

    private $_tencent_js_key = "4Y5BZ-LYMCU-5XWVY-2R2FI-4VYJH-YLB45";

    private $_tencent_server_key = "Q5ZBZ-KOG34-EZMU7-DLEJS-SIHZJ-7FBVI";

    private $_baidu_js_ak = "qTGYEdtktZhfFqHh3C2h8ceN4uD77aeB";

    private  $_baidu_server_ak = "3kaj2ft2Za3MIhIfV7AsWK9GLcqCWmRR";

    public function __construct(){

    }

    /**
     * @return string
     * 获取腾讯JS key
     */
    public function getTencentJsKey(){
        return $this->_tencent_js_key;
    }

    /**
     * @return string
     * 获取腾讯server KEY
     */
    public function getTencentServerKey(){
        return $this->_tencent_server_key;
    }

    /**
     * @return string
     * 获取百度JS AK
     */
    public function getBaiduJsAk(){
        return $this->_baidu_js_ak;
    }

    /**
     * @return string
     * 获取百度服务端 AK
     */
    public function getBaiduServerAk(){
        return $this->_baidu_server_ak;
    }

    /**
     * @param string $ip
     * @return string
     *g 百度API，根据ip地址获取经纬度
     */
    public function getBaiduLocationByIP($ip = '127.0.0.1'){
        load()->func('communication');
        $param = array(
            'ak' => $this->_baidu_server_ak,
            'ip' => $ip,
            'coor' => 'bd09ll'
        );
        $res = ihttp_get('http://api.map.baidu.com/location/ip'.'?'.http_build_query($param));
        $location = json_decode($res['content'],true);
        if(!empty($location) && is_array($location)){
            if($location['status'] == 0){
                return array(
                    'address' => $location['content']['address'],
                    'city' => $location['content']['address_detail']['city'],
                    'district' => $location['content']['address_detail']['district'],
                    'province' => $location['content']['address_detail']['province'],
                    'lng' => $location['content']['point']['x'],
                    'lat' => $location['content']['point']['y'],
                    'is_default' => '1'
                );
            }
        }
        //失败时，默认返回值
        return array(
            'address' => '北京',
            'city' => '北京',
            'district' => '北京',
            'province' => '北京',
            'lng' => '116.395645',
            'lat' => '39.929986',
            'is_default' => '1'
        );
    }

    /**
     * @param string $keyword
     * @param string $region
     * @return array
     * 根据关键词获取所有地址
     */
    public function getTencentKeywordTips($keyword = '',$region = ''){
        load()->func('communication');
        if(empty($keyword) || !is_string($keyword)){
            return null;
        }
        $param = array(
            'keyword' => $keyword,
            'region' => $region,
            'key' => $this->getTencentServerKey()
        );
        $res = ihttp_get('http://apis.map.qq.com/ws/place/v1/suggestion'.'?'.http_build_query($param));
        $res = json_decode($res['content'],true);
        if($res['status'] == 0){
            return $res['data'];
        }
        return null;
    }

    /**
     * @param $lat
     * @param $lng
     * @return array
     * 根据经纬度获取位置信息
     */
    public function getTencentAddressByLatLng($lat,$lng){
        load()->func('communication');
        $param = array(
            'key' => $this->_tencent_server_key,
            'location' => $lat.','.$lng,
            'get_poi' => 1
        );
        $res = ihttp_get('http://apis.map.qq.com/ws/geocoder/v1'.'?'.http_build_query($param));
        $location = json_decode($res['content'],true);
        if(!empty($location) && is_array($location)){
            if($location['status'] == 0){
                return array(
                    'address' => $location['result']['address'],
                    'city' => $location['result']['address_component']['city'],
                    'district' => $location['result']['address_component']['district'],
                    'province' => $location['result']['address_component']['province'],
                    'lng' => $lng,
                    'lat' => $lat,
                    'is_default' => '1'
                );
            }
        }
        return array(
            'address' => '北京',
            'city' => '北京',
            'district' => '北京',
            'province' => '北京',
            'lng' => '116.395645',
            'lat' => '39.929986',
            'is_default' => '1'
        );
    }
}