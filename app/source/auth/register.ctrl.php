<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
/* 屏蔽手机号注册，先关注再绑定手机号，为了统一微信和手机端 */
message('请先关注公众号，绑定手机号登录',url('auth/focus'),'error');
