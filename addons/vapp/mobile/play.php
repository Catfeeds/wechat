<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018-01-09
 * Time: 15:45
 */

//会员登录TOKEN
$token = trim($_GPC['token']);

//直播会员
$live_uid = floor(trim($_GPC['live_uid']));

//播放地址
$play_url = "http://pullhls1948666e.live.126.net/live/35e5de0c6f6347059decd228efcccb24/playlist.m3u8";


include $this->template('play');