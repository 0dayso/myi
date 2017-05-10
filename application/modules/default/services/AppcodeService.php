<?php
class Default_Service_AppcodeService
{
	private $_appcodeModer;
	public function __construct()
	{
		$this->_appcodeModer = new Default_Model_DbTable_Model('app_code');
	}
	
	/**
	 * 获取数量
	 */
	public function getNumber($sqltmp){
		return $this->_appcodeModer->QueryItem("SELECT count(ac.id) as num FROM app_code as ac WHERE ac.status=1 AND ac.published<=".(time())." {$sqltmp}");
	}
	/**
	 * 获取记录
	 */
	public function getAllCode($sqltmp,$offset,$perpage,$orderby=''){
		return $this->_appcodeModer->Query("SELECT ac.*,apc.name as appname FROM app_code as ac 
				LEFT JOIN app_category as apc ON apc.id = ac.app_level1
				WHERE ac.status=1 AND ac.published<=".(time())." {$sqltmp} {$orderby} LIMIT {$offset},{$perpage}");
	}
	/**
	 * 获取推荐
	 */
	public function getPushCode(){
		return $this->_appcodeModer->Query("SELECT ac.*,apc.name as appname FROM app_code as ac
				LEFT JOIN app_category as apc ON apc.id = ac.app_level1
				WHERE ac.status=1 AND ac.published<=".(time())." AND ac.push=1");
	}
	/**
	 * 获取单条记录
	 */
	public function getCodeByid($id){
		return $this->_appcodeModer->QueryRow("SELECT ac.*,apc.name as appname FROM app_code as ac
				LEFT JOIN app_category as apc ON apc.id = ac.app_level1
				WHERE ac.status=1 AND ac.published<=".(time())." AND ac.id = '{$id}'");
	}
	/**
	 * 获取单条记录
	 */
	public function getCodeByidPreview($id){
		return $this->_appcodeModer->QueryRow("SELECT ac.*,apc.name as appname FROM app_code as ac
				LEFT JOIN app_category as apc ON apc.id = ac.app_level1
				WHERE ac.id = '{$id}'");
	}
	/**
	 * 检查是否有足够积分
	 */
	public function checkscore($id){
		$this->_jifenService = new Default_Service_JifenService();
		$myscore = $this->_jifenService->getSurplusScore();
		$arr = $this->getCodeByid($id);
		if($myscore >= $arr['spendpoints']) return true;
		else return false;
	}
	/**
	 * 更新下载次数
	 */
	public function updnumber($id){
		$re = $this->_appcodeModer->updateBySql("UPDATE app_code SET downloadnumber =downloadnumber +1 WHERE id='$id'");
	}
	public function domlog($id){
		$re = array();
		$a = $this->_appcodeModer->Query("SELECT distinct(temp5)
				FROM `score_log` WHERE controller='appcode' AND action='download' AND uid IN (SELECT uid
				FROM `score_log` WHERE controller='appcode' AND action='download' AND temp5='{$id}') AND temp5!='{$id}' LIMIT 0 , 5");
		if($a){
		  foreach($a as $v){
		  	$tmp = $this->getCodeByid($v['temp5']);
		  	if($tmp) $re[] =  $tmp;
		  }
		}
		return $re;
	}
}