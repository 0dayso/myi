<?php
require_once 'Iceaclib/api/weibo/qq/Tencent.php';
class Icwebadmin_Service_TencentweiboService
{
	public function __construct($token=array())
	{
		if(empty($token)){
		//盛芯电子token
		$token = array('t_access_token' => 'bf8e6f0049560257ea1f2457d89e2ea5',
				't_openid' => '665A9D850AF8F260153D4E135ACA8838',
				't_openkey' => '5EEDC4C926E792A5C565B8A619BA9B65');
		}
	   OAuth::init(QQ_CLIENT_ID, QQ_CLIENT_SECRET);
       Tencent::$debug = false;
       $_SESSION['t_access_token']=$token['t_access_token'];
       $_SESSION['t_openid']      =$token['t_openid'];
       $_SESSION['t_openkey']     =$token['t_openkey'];
       $r = Tencent::api('user/info');
       
      
       
	}
	//发文字微博
	public function add( $content ){
		$params = array(
				'content' => $content
		);
		$ret = Zend_Json_Decoder::decode(Tencent::api('t/add', $params, 'POST'));
		if ( isset($ret['errcode']) && $ret['errcode'] > 0 ) {
			return false;
		} else {
			return true;
		}
	}
	//发图片微博
	public function add_pic( $content, $pic_url='' ){
		 $params = array(
        'content' => $content,
        'pic_url' => $pic_url
        );
		$ret = Zend_Json_Decoder::decode(Tencent::api('t/add_pic_url', $params, 'POST'));
		if ( isset($ret['errcode']) && $ret['errcode'] > 0 ) {
			return false;
		} else {
			return true;
		}
	}
}