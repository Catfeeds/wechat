<?php 
/**
 * 
 * 
 */
defined('IN_IA') or exit('Access Denied');

define('REGULAR_EMAIL', '/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/i');
define('REGULAR_MOBILE', '/1\d{10}/');
define('REGULAR_USERNAME', '/^[\x{4e00}-\x{9fa5}a-z\d_\.]{3,15}$/iu');

define('TEMPLATE_DISPLAY', 0);
define('TEMPLATE_FETCH', 1);
define('TEMPLATE_INCLUDEPATH', 2);

define('ACCOUNT_SUBSCRIPTION', 1);
define('ACCOUNT_SUBSCRIPTION_VERIFY', 3);
define('ACCOUNT_SERVICE', 2);
define('ACCOUNT_SERVICE_VERIFY', 4);

define('ACCOUNT_OAUTH_LOGIN', 3);
define('ACCOUNT_NORMAL_LOGIN', 1);

define('WEIXIN_ROOT', 'https://mp.weixin.qq.com');

define('ACCOUNT_OPERATE_ONLINE', 1);
define('ACCOUNT_OPERATE_MANAGER', 2);
define('ACCOUNT_OPERATE_CLERK', 3);

/* 审核状态 */
define('CHECK_PASS',1);//通过审核
define('CHECK_NOT_PASS',0);//未通过审核

/* 显示状态 */
define('DISPLAY_NO',0);//隐藏
define('DISPLAY_YES',1);//显示

/* 导出状态 */
define('EXPORT_NO',0);//不导出
define('EXPORT_YES',1);//导出

/* 用户申请类型 */
define('APPLY_TYPE_PERSON',0);//个人申请
define('APPLY_TYPE_COMPANY',1);//公司申请

/* 商城类型 */
define('STORE_TYPE_BBC',0);//线上BBC商城类型
define('STORE_TYPE_OTO',1);//本地OTO类型

/* 商品类型 */
define('GOODS_TYPE_ACTUAL',0);//实物
define('GOODS_TYPE_VIRTUAL',1);//虚拟

/* 分类显示 */
define('CATEGORY_SHOW_ALL',1);//显示所有分类
define('CATEGORY_SHOW_PARENT',0);//显示所有父级分类

/* 是否推荐 */
define('RECOMMEND_YES',1);//推荐
define('RECOMMEND_NO',0);//不推荐

/* 是否包邮 */
define('POST_FREE_YES',1);//包邮
define('POST_FREE_NO',0);//不包邮

/* 是否开启关闭 */
define('OPEN_STATUS',1);//开启
define('CLOSE_STATUS',0);//关闭

/* 是否上架 */
define('PUT_AWAY_YES',1);//上架
define('PUT_AWAY_NO',0);//下架

/* 是否删除 */
define('DELETE_YES',1);//已删除
define('DELETE_NO',0);//未删除

define('IS_DEFAULT',1);//是默认
define('NOT_DEFAULT',0);//非默认

/* 订单提交方式 */
define('ORDER_PUSH_NOW',0);//立即购买
define('ORDER_PUSH_CART',1);//购物车转订单


/* 订单状态 */
define('ORDER_STATUS_NOT_PAY',0);//待付款
define('ORDER_STATUS_NOT_DELIVER',1);//待发货
define('ORDER_STATUS_NOT_CONFIRM',2);//待确认
define('ORDER_STATUS_COMPLETE',3);//已完成
define('ORDER_STATUS_NOT_RETURN',4);//待退款
define('ORDER_STATUS_RETURN',5);//已退款
define('ORDER_STATUS_CLOSE',6);//关闭取消


define('TALK_YES',1);//已评论
define('TALK_NO',0);//未评论

/* 支付状态 */
define('PAY_NO',0);//已支付
define('PAY_YES',1);//未支付


/* 订单编号长度 */
define('ORDER_NO_LENGTH',14);

/* 支付方式 */
define('PAY_METHOD_CREDIT',1);//余额支付
define('PAY_METHOD_WECHAT',2);//微信支付
define('PAY_METHOD_ALIPAY',3);//支付宝支付
define('PAY_METHOD_UNION',4);//银行卡支付
define('PAY_METHOD_FUIOU',5);//富有支付
define('PAY_METHOD_CASH',6);//货到付款

/* 订单核销 */
define('ORDER_VERIFY_YES',1);//已核销
define('ORDER_VERIFY_NO',0);//未核销


/* 分割符常量 */
define('SPLIT_ORDER_IDS','-');//支付记录，多个订单id分隔符，是中横线
define('SPLIT_GOODS_SKU_DESC','-');//商品规格项描述，分隔符，是中横线
define('SPLIT_GOODS_SKU_KEY','_');//商品规格项key，分隔符，是下划线
define('SPLIT_SCENE_STR','_');//场景关键字分隔符
define('SPLIT_RELATION','-');//关系分隔符
define('SPLIT_AUTH_PARAM','_');//参数加密间隔符号
define('SPLIT_FANGYUANBAO_IDS','-');//支付记录，多个订单id分隔符，是中横线

/* 搜索类型 */
define('SEARCH_TYPE_STORE',1);//搜索店铺
define('SEARCH_TYPE_GOODS',0);//搜索商品


define('ENCODE_STATUS',0);//加密标识
define('DECODE_STATUS',1);//解密标识

/* 同意不同意 */
define('AGREE_YES',1);//同意
define('AGREE_NO',0);//不同意

/* 运费模板类型 */
define('POSTAGE_TYPE_MONEY',0);//固定金额
define('POSTAGE_TYPE_TEMPLATE',1);//运费模板

/* 计费方式 */
define('CALC_BY_WEIGHT',0);//按重量计费
define('CALC_BY_NUM',1);//按数目计费

/* 场景二维码类型 */
define('QR_MODEL_SHORT_TIME',1);//临时
define('QR_MODEL_LONG_TIME',2);//永久

/* 海报宽高 */
define('POSTER_WIDTH','369');
define('POSTER_HEIGHT','504');
define('POSTER_CODE_AVATAR_WIDTH','30');//二维码头像宽
define('POSTER_CODE_AVATAR_HEIGHT','30');//二维码头像高

/* 默认头像地址 */
define('AVATAR_DEFAULT_SRC','images/global/default_avatar.png');

/* 关系树级别 */
define('RELATION_ONE_LEVEL',1);//一级会员
define('RELATION_SECOND_LEVEL',2);//二级会员
define('RELATION_THIRD_LEVEL',3);//三级会员
define('RELATION_THIRD_LEVEL_UP',4);//三级以上，包括三级


/* 收藏类型 */
define('COLLECT_TYPE_GOODS',0);//收藏商品
define('COLLECT_TYPE_STORE',1);//收藏店铺

/* 评价类型 */
define('TALK_TYPE_GOOD',0);//好评
define('TALK_TYPE_COMMON',1);//中评
define('TALK_TYPE_BAD',2);//差评


/* 是否匿名 */
define('ANONYMOUS_YES',1); //匿名
define('ANONYMOUS_NO',0);//不匿名

/* 性别类型 */
define('GENDER_TYPE_SAFE',0);//保密
define('GENDER_TYPE_MALE',1);//男
define('GENDER_TYPE_FEMALE',2);//女


/* 商家结算方式 */
define('STORE_BALANCE_TYPE_ALIPAY',1);//支付宝
define('STORE_BALANCE_TYPE_BANK',2);//银联支付
define('STORE_BALANCE_TYPE_WECHAT',3);//微信


/* 结算状态 */
define('STORE_BALANCE_STATUS_YES',1);//结算完成
define('STORE_BALANCE_STATUS_NO',0);//未结算


/* 登录状态 */
define('LOGIN_NO',0);//未登录
define('LOGIN_YES',1);//已登录

/* 分销状态 */
define('DISTRIBUTION_STATUS_YES',1);//已分销
define('DISTRIBUTION_STATUS_NO',0);//未分销

/* 用户购买商品数目类型 */
define('GOODS_BUY_NUM_FULL',0);//所有
define('GOODS_BUY_NUM_NOW',1);//当前

/* 模块名 */
define('MODULE_NAME_OTO','oto');//本地OTO
define('MODULE_NAME_LOCATION','location');//定位
define('MODULE_NAME_SHOP','shop');//商城
define('MODULE_NAME_ALCHEMY','alchemy');//炼金坊

/* 支付日志,订单类型 */
define('ORDER_TYPE_OTO_GOODS',1);//oto商城订单
define('ORDER_TYPE_OFFLINE',2);//线下支付
define('ORDER_TYPE_PERSON',3);//个人转款
define('ORDER_TYPE_OLD_FEE',4);//二手服务费
define('ORDER_TYPE_DEVELOP_SHOP',6);//开店
define('ORDER_TYPE_SJ_NEWS_RENEW',7);//续费订单
define('ORDER_TYPE_SJ_NEWS_AD',8); //新晋传媒购买广告

//vapp商城订单
define('ORDER_TYPE_VAPP_GOODS',9);//vapp商品支付


/* 返佣类型 */
define('REBATE_TYPE_MONEY',0);//返现金
define('REBATE_TYPE_CREDIT1',1);//返积分

/* 红包发放方式 */
define('RED_BAG_COMPANY_PAY',0);//企业付款
define('RED_BAG_WECHAT_PAY',1);//微信红包

/* 是否取消 */
define('CANCEL_NO',0);//未取消
define('CANCEL_YES',1);//已取消

/* 方圆宝 */
define('FANGYUANBAO_CREDIT_EXCHANGE',0);
define('FANGYUANBAO_RED_BAG_SEND',1);

/* 是否状态 */
define('IS_STATUS',1);//是
define('NO_STATUS',0);//不是

/* 分销商条件 */
define('DISTRIBUTOR_CONDITION_NO',0);//无条件
define('DISTRIBUTOR_CONDITION_APPLY',1);//申请
define('DISTRIBUTOR_CONDITION_GOODS',2);//购买指定商品

/* 新旧程度 */
define('GOODS_SECOND_HAND_NO',0);//全新
define('GOODS_SECOND_HAND_YES',1);//二手

/* 发放状态 */
define('SEND_YES',1);//已发放
define('SEND_NO',0);//未发放

/* 装修类型 */
define('SHOP_DIY_TYPE_PLATFORM',0);//平台
define('SHOP_DIY_TYPE_STORE',1);//商家

/* 二手搜索区域 */
define('SEARCH_CITY',0);//同城
define('SEARCH_COUNTRY',1);//全国

/* 文章类型 */
define('ARTICLE_STORE_APPLY',1);
define('ARTICLE_OLD_PUSH',2);


/* 短信发送类型 */
define('SMS_TYPE_CODE',0);//验证码
define('SMS_TYPE_NOTICE',1);//系统通知


/* 用户交互标识 */
define('INTERACTION_TYPE_LOOK',0);//浏览
define('INTERACTION_TYPE_LIKE',1);//点赞
define('INTERACTION_TYPE_HATE',2);//讨厌
define('INTERACTION_TYPE_SHARE',3);//分享




/* jwt加密常量 */
define('JWT_AUTH_KEY','TBUKzpzdHmJ43Oro');



/* 调试控制 */
define('DEBUG_CUSTOM_PAY',true);
