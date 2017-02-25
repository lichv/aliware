<?php
namespace Aliware;

class MQqueue{
	protected static $_instance = null;
	public $openurl='http://publictest-rest.ons.aliyun.com';

	protected function __construct($config){
		$this->AccessKey=$config['ak'];
		$this->SecretKey=$config['sk'];
		$this->Topic=$config['topic'];
		$this->ProducerId=$config['pid'];
		$this->ConsumerId=$config['cid'];
		$this->time=self::microtime();
	}
	/**
	 * 发送队列信息
	 * @param type $data
	 * @return type array
	 */	
	public function sendMsg($data,$key='http',$tag='http',$timeout=30){
		$data=self::gbktoutf8($data);
		$sign2=sprintf("%s\n%s\n%s\n%s", $this->Topic, $this->ProducerId, md5($data), $this->time);
		$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2),$this->SecretKey, true)); 
		$header= array(
			"AccessKey:" . $this->AccessKey, 
			"ProducerId:" . $this->ProducerId,
			"Signature:" . $sign
			);
		$url = $this->openurl . "/message/?topic=" . $this->Topic . "&time=" . $this->time . "&tag=".$tag . "&key=".$key;
		return  $this->request_curl($url, $data, $header, $status, 'POST',$timeout);
	}
	 /**
	 * 接收队列信息
	 * @return type array
	 */
	 public function getMsg($count=32,$key='http',$tag='http',$timeout=30){
	 	$sign2=sprintf("%s\n%s\n%s", $this->Topic, $this->ConsumerId,$this->time);
	 	$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2),$this->SecretKey, true)); 
	 	$header= array(
	 		"AccessKey:" . $this->AccessKey, 
	 		"ConsumerId:" . $this->ConsumerId,
	 		"Signature:" . $sign
	 		);

	 	$url = $this->openurl . "/message/?topic=" . $this->Topic . "&time=" . $this->time . "&num=" . $count. "&tag=".$tag . "&key=".$key;
	 	return  $this->request_curl($url, false, $header, $status, 'GET',$timeout);
	 }
	/**
	 * 删除队列
	 * @param type $msgHandle
	 * @return type array
	 */
	public function deleteMsg($msgHandle,$key='http',$tag='http',$timeout=30) {
		$sign2 = sprintf("%s\n%s\n%s\n%s", $this->Topic, $this->ConsumerId, $msgHandle, $this->time);
		$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2), $this->SecretKey, true));
		$header = array(
			"AccessKey:" . $this->AccessKey,
			"ConsumerId:" . $this->ConsumerId,
			"Signature:" . $sign
			);
		$url = $this->openurl . "/message/?msgHandle=" . $msgHandle . "&topic=" . $this->Topic . "&time=" . $this->time;
		return  $this->request_curl($url, false, $header, $status, 'DELETE',$timeout);
	}
	/**
	 * 队列请求
	 * @param string $url
	 * @param type $data
	 * @param type $header
	 * @param type $status
	 * @return type array
	 */
	public function request_curl($url,$data, $header = false, &$status = 200, $type='POST', $timeout = 20) {
		$ch = curl_init();
		if (empty($ch)){
			return false;
		}
		curl_setopt($ch, CURLOPT_URL, $url);

		if (strtolower($type)=='post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['a'], '', '&'));
			if(!empty($data)){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			
		}else{
			curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, strtoupper($type) );
		}

		if (false !== $header) {
			$header[] = 'Content-type: text/plain;charset=utf-8';
			curl_setopt ($ch, CURLOPT_HTTPHEADER , $header ); 
		}			

		//https
		if (trim(substr($url, 0, 5)) == 'https') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		if(!empty($timeout)){
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1");

		$content = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if (!empty($content)) {
			$temp = json_decode($content,true);
			if(!empty($temp)&& is_array($temp)){
				$content = $temp;
			}
		}

		return ['state'=>$status,'data'=>$content];
	}

	/**
	 * 获取实例
	 * @param array $config
	 * @return type MQqueue
	 */
	public static function getInstance($config){
		if (!isset(self::$_instance)) {
			self::$_instance = new self($config);
		}
		return self::$_instance;
	}

	/**
	 * GBK 转化 UTF8
	 * @param string $s
	 * @return string
	 */
	public static function gbktoutf8($s) {
		if (is_array($s)) {
			$sn = array();
			foreach ($s as $k => $v) {
				$sn[gbktoutf($k)] = gbktoutf($v);
			}
			return $sn;
		} else {
			return iconv('utf-8', 'gbk//IGNORE', $s);
		}
	}

	/**
	 * 获取当前毫秒数
	 * @return string
	 */
	public static function microtime() {
		$time = explode ( " ", microtime () );
		$time = $time [1] . sprintf("%03d", ($time [0] * 1000));
		$time2 = explode ( ".", $time );
		$time = $time2 [0];
		return $time;	
	}
}