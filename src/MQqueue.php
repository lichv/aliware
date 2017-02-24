<?php
namespace Aliware;

class MQqueue{
	public $openurl='http://publictest-rest.ons.aliyun.com';
	public function __construct($config){
		$this->AccessKey=$config['ak'];
		$this->SecretKey=$config['sk'];
		$this->Topic=$config['topic'];
		$this->ProducerId=$config['pid'];
		$this->ConsumerId=$config['cid'];
		$this->time=time()."000";
	}
	/**
	 * 发送队列信息
	 * @param type $post_Body
	 * @return type
	 */	
	public function sendmsg($post_Body,$key='http',$tag='http',$type=''){
		$post_Body=$this->gbktoutf8($post_Body);
		$sign2=sprintf("%s\n%s\n%s\n%s", $this->Topic, $this->ProducerId, md5($post_Body), $this->time);
		$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2),$this->SecretKey, true)); 
		$header_arr= array(
			"Content-Type: text/plain;charset=UTF-8",
			"AccessKey:" . $this->AccessKey, 
			"ProducerId:" . $this->ProducerId,
			"ConsumerId:" . $this->ConsumerId,
			"Signature:" . $sign
			);
		$msg = 'it is a test';
		return  $return=$this->curl_post($this->openurl.'/message/',$post_Body,$header_arr,$key,$tag,$type);
	}
	 /**
	 * 接收队列信息
	 * @return type
	 */
	 public function Responsemsg($key='http',$tag='http',$type=''){
		$sign2=sprintf("%s\n%s\n%s", $this->Topic, $this->ConsumerId,$this->time);
		$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2),$this->SecretKey, true)); 
		$header_arr= array(
			"Content-Type: text/plain;charset=UTF-8",
			"AccessKey:" . $this->AccessKey, 
			"ConsumerId:" . $this->ConsumerId,
			"Signature:" . $sign
			);
		return  $return=$this->curl_post($this->openurl.'/message/','',$header_arr,$key,$tag,$type);
	}
	/**
	 * 删除队列
	 * @param type $msgHandle
	 * @return type
	 */
	public function deleteMsg($msgHandle,$key='http',$tag='http') {
		$sign2 = sprintf("%s\n%s\ns\n%s", $this->Topic, $this->ConsumerId, $msgHandle, $this->time);
		$sign = base64_encode(hash_hmac('sha1', htmlentities($sign2), $this->SecretKey, true));
		$header_arr = array(
			"Content-Type: text/plain;charset=UTF-8",
			"AccessKey:" . $this->AccessKey,
			"ConsumerId:" . $this->ConsumerId,
			"Signature:" . $sign
			);
		return $return = $this->curl_post($this->openurl . '/message/', '', $header_arr,$key,$tag, 'DELETE');
	}
	/**
	 * 队列请求
	 * @param string $url
	 * @param type $post_Body
	 * @param type $header_arr
	 * @return type
	 */
	public function curl_post($url, $post_Body="",$header_arr,$key='http',$tag='http',$type='') {
		$cookie_file = "./";
		$post_str = '';
		$post_str = substr($post_str, 0, - 1);
		$curl = curl_init();
		$url.="?topic=".$this->Topic."&time=".$this->time."&tag=".$tag."&key=".$key;
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_arr);
		if(empty($type) || strtolower($type)=='post'){
			if($post_Body){
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_Body);
			}
		}else{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$type);
		}
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:9.0.1) Gecko/20100101 Firefox/9.0.1");
		curl_setopt($curl, CURLOPT_HEADER, false);
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}

	/**
	 * GBK 转化 UTF8
	 * @param string $s
	 * @return string
	 */
	public function gbktoutf8($s) {
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
}