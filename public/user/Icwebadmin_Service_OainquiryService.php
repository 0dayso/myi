<?php
class Icwebadmin_Service_OainquiryService
{
	public function chackLogin($string) {
		//$string->inputJosn. 
		$arr = json_decode($string->arg0);
		
		$snsuserid = "1";
		if($snsuserid!="" &&  $snsuserid  == $arr->snsuserid){
			$reArray['responseCode'] = 0;
			$reArray['responseMsg']  = iconv("gbk", "UTF-8//IGNORE","�Ѿ���½");
		}else{
			$reArray['responseCode'] = 401;
			$reArray['responseMsg']  = iconv("gbk", "UTF-8//IGNORE","��½ʧ��");
		}
		return array("return"=>iconv("gbk", "UTF-8//IGNORE",json_encode($reArray)));
	}
}