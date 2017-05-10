<?php
require_once 'Iceaclib/api/weibo/sina/saetv2.ex.class.php';
class Icwebadmin_Service_SinaweiboService
{
	public function __construct($token=array())
	{
		if(empty($token)){
		//IC易站token
		$token = array('access_token' => '2.00CBo4PD0Wwdo69fe98cb0a7X194DD',
				'remind_in' => 157679999,
				'expires_in' => 157679999,
				'uid' => 2975717344);
		}
		$this->c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );	
	}
	//发文字微博
	public function update( $status, $lat = NULL, $long = NULL, $annotations = NULL ){
		$ret = $this->c->update( $status, $lat, $long, $annotations);	//发送微博
		if ( isset($ret['error_code']) && $ret['error_code'] > 0 ) {
			return false;
		} else {
			return true;
		}
	}
	//发图片微博
	function upload( $status, $pic_path, $lat = NULL, $long = NULL )
	{
		$ret = $this->c->upload( $status, $pic_path, $lat , $long);	//发送微博
		if ( isset($ret['error_code']) && $ret['error_code'] > 0 ) {
			return false;
		} else {
			return true;
		}
	}
	
}