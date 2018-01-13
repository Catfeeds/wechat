<?php

//企业付款，需要证书
class WxPay{

	protected $values = array();
	public $SSLCERT_PATH = "";
	public $SSLKEY_PATH = "";

	public function __construct($data = array()) {
		global $_W;
		if (empty($data)) {
			return error(-1,'系统维护中：-1，请联系客服');
		}
		$this->SSLCERT_PATH = $_SERVER["DOCUMENT_ROOT"]."/payment/wechat/cert/{$_W['uniacid']}/apiclient_cert.pem";
		$this->SSLKEY_PATH = $_SERVER["DOCUMENT_ROOT"]."/payment/wechat/cert/{$_W['uniacid']}/apiclient_key.pem";
		if(!file_exists($this->SSLCERT_PATH) || !file_exists($this->SSLKEY_PATH)){
			return error(-2,'系统维护中：-2，请联系客服');
		}
		
		$setting = uni_setting($_W['uniacid'], array('payment', 'recharge'));
		$rsPay = $setting['payment'];
		$rs = pdo_get('account_wechats',array('uniacid'=>$_W['uniacid']));
		$rsUsers = pdo_fetch("SELECT * FROM " .tablename("mc_mapping_fans") . " WHERE uniacid='{$_W['uniacid']}' AND uid='{$data['uid']}' ORDER BY fanid DESC LIMIT 1");
		if(empty($rsUsers)){
			return error(-3,'系统维护中：-3，请联系客服');
		}
		$this->setMch_appid($rs['key']);
		$this->setMchid($rsPay['wechat']["mchid"]);
		$this->getNonceStr();
		$this->setPartner_trade_no($data['Record_Sn']); //订单号
		$this->setOpenid($rsUsers['openid']);//openid
		$this->values['check_name'] = "FORCE_CHECK"; //强制校验用户姓名选项
		$this->setRe_user_name($data['realname']);//用户微信绑定的真实名字
		$this->setAmount($data['Record_Money']*100); //金额
		$this->setDesc("商城提现付款");
		$this->getSpbill_create_ip(); //用户IP地址
		$this->getSign($rsPay['wechat']["signkey"]); //签名	
	}
	//开始处理支付信息
	public function startPay() {
		$xml = $this->ToXml();
		if(is_error($xml)){
			return $xml;
		}
		$returnRes = $this->postXmlCurl($xml);
		if(is_error($returnRes)){
			return $returnRes;
		}
		$res = $this->FromXml($returnRes);
		if(is_error($res)){
			return $res;
		}
		//记录日志
		$this->logs($xml,$res);
		if($res['result_code'] === "SUCCESS"){
			return true;
		}else{
			return error(-4,$res['return_msg']);
		}
	}
	//logs
	public function logs($xml,$res){
		global $_W;
		$fp = fopen(IA_ROOT . "/data/logs/".$_W['uniacid']."-"."wechatPay.log", "a+");
		fwrite($fp,"时间:".date("Y-m-d H:i:s",time())."\r\n");
		fwrite($fp,"payType:wechatPay\r\n");
		foreach ($this->values as $key => $val) {
			fwrite($fp, $key."=>".$val."\r\n");
		}
		fwrite($fp,"数据:". var_export($xml,true)."\r\n");
		fwrite($fp,"结果:". var_export($res,true)."\r\n");
		fclose($fp);
	}
	//商户号公众账号appid
	public function setMch_appid($value) {
		$this->values['mch_appid'] = $value;
	}

	//商户号
	public function setMchid($value) {
		$this->values['mchid'] = $value;
	}

	/**
	 *
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	public function getNonceStr($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		$this->values['nonce_str'] = $str;
	}

	//商户订单号
	public function setPartner_trade_no($value) {
		$this->values['partner_trade_no'] = $value;
	}

	//用户openid
	public function setOpenid($value) {
		$this->values['openid'] = $value;
	}

	//收款用户姓名
	public function setRe_user_name($value) {
		$this->values['re_user_name'] = $value;
	}

	//金额
	public function setAmount($value) {
		$this->values['amount'] = $value;
	}

	//企业付款描述信息
	public function setDesc($value) {
		$this->values['desc'] = $value;
	}

	//获取用IP地址
	public function getSpbill_create_ip() {
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
			$ip = $_SERVER['HTTP_CDN_SRC_IP'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
			foreach ($matches[0] AS $xip) {
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
					$ip = $xip;
					break;
				}
			}
		}
		$this->values['spbill_create_ip'] = $ip;
	}

	/**
	 * 输出xml字符
	 * @throws WxPayException
	 * */
	public function ToXml() {
		if (!is_array($this->values) || count($this->values) <= 0 || count(array_filter($this->values)) !== count($this->values)) {
			return error(-1,'缺少参数！');
		}

		$xml = "<xml>";
		foreach ($this->values as $key => $val) {
			if (is_numeric($val)) {
				$xml.="<" . $key . ">" . $val . "</" . $key . ">";
			} else {
				$xml.="<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}

	/**
	 * 将xml转为array
	 * @param string $xml
	 * @throws WxPayException
	 */
	public function FromXml($xml) {
		if (!$xml) {
			return error('-1','数据转换失败');
		}
		//将XML转为array
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $this->values;
	}

	/**
	 * 格式化参数格式化成url参数
	 */
	public function ToUrlParams() {
		$buff = "";
		foreach ($this->values as $k => $v) {
			if ($k != "sign" && $v != "" && !is_array($v)) {
				$buff .= $k . "=" . $v . "&";
			}
		}

		$buff = trim($buff, "&");
		return $buff;
	}

	public function getSign($KEY) {
		//签名步骤一：按字典序排序参数
		ksort($this->values);
		$string = $this->ToUrlParams();
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=" . $KEY;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		$this->values['sign'] = $result;
	}
	private function postXmlCurl($xml, $useCert = true, $second = 30) {
		$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);//严格校验
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else { 
			$error = curl_errno($ch);
			curl_close($ch);
			return error('-1','curl出错');
		}
	}
}
