<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_ScoreService
{
	private $_scoreruleModel;
	public function __construct()
	{
		$this->_scoreruleModel = new Default_Model_DbTable_Model('score_rule');
		$this->_fun  = new MyFun();
	}
	/**
	 * 添加积分
	 * @param unknown_type $type
	 */
	public function addScore($type,$multiplier=1,$temp5='',$adduid=''){
		$this->_uid = $adduid?$adduid:$_SESSION['userInfo']['uidSession'];
		$rescore = $this->getScore($type,$this->_uid);
		$description = array('-101'=>'uid无效',
				'-201'=>'规则为空，查找不到',
				'-202'=>'此类型积分没有',
				'-203'=>'此类型积分被禁用',
				'-301'=>'已经获得，不能再获取',
				'-302'=>'超过最大总积分',
				'-303'=>'超过每天最大积分');
		$rule = $this->getRuleByType($type);
		//立即获取
		if($rule['addtype']=='now'){
		  //获取积分成功
		  if($rescore>0){
		  	//向上舍入为最接近的整数。
			$rescore =ceil($rescore*$multiplier);
			//最多单次获取积分不要超过2000
			$rescore = ($rescore>2000?2000:$rescore);
			//更新用户积分
			$re = $this->_scoreruleModel->updateBySql("UPDATE user_profile SET score =score + {$rescore} WHERE uid='$this->_uid'");
			if($re){
			   if($rule['caps_day']){$this->totalscoreday($type,$rescore,$this->_uid,'add');}
			   $this->addLog(array('log_id'=>'A','temp2'=>$type,'temp3'=>$rescore,'temp4'=>'获取积分成功','temp5'=>$temp5,'description'=>$description[$rescore]));
			   return $rescore;
			}else{
				$this->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$type,'temp3'=>$rescore,'temp4'=>'获取积分失败，更新数据失败','temp5'=>$temp5,'description'=>$description[$rescore]));
				return false;
			}
		  }else{
			$this->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$type,'temp3'=>$rescore,'temp4'=>'获取积分失败','temp5'=>$temp5,'description'=>$description[$rescore])); 
			return false;
		  }
		}else{//需要审核
			$this->addLog(array('log_id'=>'N','temp2'=>$type,'temp3'=>$rescore,'temp4'=>'记录获取积分,需要审核获取','temp5'=>$temp5));
		}
	}
	/**
	 * 获取积分
	 */
	 public function getScore($type,$uid){
		if(!isset($uid) || !$uid) return -101;//没有登录
		$rule = $this->getRule();
		if(!$rule) return -201;//规则为空
		$r = $rule[$type];
		if(!$r) return -202;//此类型积分没有
		if(!$r['status']) return -203;//此类型积分被禁用
		$score = $r['score']*$r['percentage'];
		//获取总积分记录
		if($r['caps']){
			$totalscor = $this->totalscore($type,$score,$uid);
			if(($totalscor[$type]) >= $r['caps']) return -302;//超过最大积分
		}
		//每天
		if($r['caps_day']){
			$totaldayscor = $this->totalscoreday($type,$score,$uid);
			if(($totaldayscor[$type]) >= $r['caps_day']) return -303;//每天超过最大积分
		}
		//0 操作获得
		if($r['rule']==0){
			return $score;
		}elseif($r['rule']==1){//只有一次
			$isexist = $this->cachefactory(null,$type.$uid);
			if(!$isexist) return $score; //返回积分
			else return -301;//已经获得，不能再获取
		}elseif($r['rule']==2){//每天一次
			//获取记录
			$todytime = strtotime(date("Y-m-d 23:59:59")) - time();
			$isexist = $this->cachefactory($todytime,$type.$uid);
			if(!$isexist){//返回积分
				return $score;
			}else return -301;//已经获得，不能再获取
		}elseif($r['rule']==3){//每周一次
			//获取记录
			$isexist = $this->cachefactory(3600*24*7,$type.$uid);
			if(!$isexist){//返回积分
				return $score;
			}else return -301;//已经获得，不能再获取
		}
	}
	/**
	 * 检查是否已经获得积分，不保存
	 */
	public function checkgetscore($type,$uid){
		if(!isset($uid) || !$uid) return -101;//没有登录
		$rule = $this->getRule();
		if(!$rule) return -201;//规则为空
		$r = $rule[$type];
		if(!$r) return -202;//此类型积分没有
		if(!$r['status']) return -203;//此类型积分被禁用
		$score = $r['score']*$r['percentage'];
		//获取总积分记录
		if($r['caps']){
			$totalscor = $this->totalscore($type,$score,$uid);
			if(($totalscor[$type]) > $r['caps']) return -302;//超过最大积分
		}
		//每天
		if($r['caps_day']){
			$totaldayscor = $this->totalscoreday($type,$score,$uid,'get');
			if(($totaldayscor[$type]) > $r['caps_day']) return -303;//每天超过最大积分
		}
		//0 操作获得
		if($r['rule']==0){
			return $score;
		}elseif($r['rule']==1){//只有一次
			if($this->checkcachefactory(null,$type.$uid)) return -301;//已经获得，不能再获取
			else return $score;
		}elseif($r['rule']==2){//每天一次
			$todytime = strtotime(date("Y-m-d 23:59:59")) - time();
			if($this->checkcachefactory($todytime,$type.$uid)) return -301;//已经获得，不能再获取
			else return $score;
		}elseif($r['rule']==3){//每周一次
			if($this->checkcachefactory(3600*24*7,$type.$uid)) return -301;//已经获得，不能再获取
			else return $score;
		}else return -501;
	}
	public function checkcachefactory($lifeTime,$cache_key,$aut=true){
		$frontendOptions = array('lifeTime' => $lifeTime,'automatic_serialization' => $aut);
		$backendOptions = array('cache_dir' => CACHE_PATH.'score/');
		// $cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在
		$cache_re = $cache->load($cache_key);
		if(!$cache_re) {
		   return false;
		}else {return true;}
	}
	/**
	 * 创建或获取缓存
	 */
	public function cachefactory($lifeTime,$cache_key,$aut=true){
		$frontendOptions = array('lifeTime' => $lifeTime,'automatic_serialization' => $aut);
		$backendOptions = array('cache_dir' => CACHE_PATH.'score/');
		// $cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在
		$cache_re = $cache->load($cache_key);
		if(!$cache_re) {
		   $cache->save(1,$cache_key);
		   return false;
		}else return true;
	}
	/**
	 * 记录总分值
	 */
	private function totalscore($type,$score,$uid){
		$frontendOptions = array('lifeTime' => null,'automatic_serialization' => ture);
		$backendOptions = array('cache_dir' => CACHE_PATH.'score/');
		// $cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在
		$cache_key = 'scoretotal'.$type.$uid;
		$cache_re = $cache->load($cache_key);
		$cache_re[$type] +=$score;
		$cache->save($cache_re,$cache_key);
		return $cache_re;
	}
	/**
	 * 记录当天总分值
	 */
	private function totalscoreday($type,$score,$uid,$tmp=''){
		$todytime = strtotime(date("Y-m-d 23:59:59")) - time();
		$frontendOptions = array('lifeTime' => $todytime,'automatic_serialization' => ture);
		$backendOptions = array('cache_dir' => CACHE_PATH.'score/');
		// $cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在
		$cache_key = 'scoredaytotal'.$type.$uid;
		$cache_re = $cache->load($cache_key);
		if($tmp=='add'){
		  $cache_re[$type] +=$score;
		  $cache->save($cache_re,$cache_key);
		}
		return $cache_re;
	}
	/**
	 * 获取规则
	 */
	private function getRule(){
	   $re = array();
	   $rule = $this->_scoreruleModel->getAllByWhere("id!=''");
	   foreach($rule as $v){
	   	  $re[$v['type']] = $v;
	   }
	   return $re;
	}
	/**
	 * 获取规则
	 */
	public function getRuleByType($type){
		return $this->_scoreruleModel->getRowByWhere("type='$type'");
	}
	/**
	 * 获取用户积分
	 */
	public function getUserScore(){
		$re = $this->_scoreruleModel->QueryRow("SELECT score FROM user_profile WHERE uid='".$_SESSION['userInfo']['uidSession']."'");
		if($re) {
			$s = $re['score'];
			return ($s>0?$s:0);
		}else return 0;
	}
	/**
	 * 扣除用户积分
	 */
	public function destore($score,$temp5='',$description='',$uid=''){
		$uid = $uid?$uid:$_SESSION['userInfo']['uidSession'];
		$re = $this->_scoreruleModel->updateBySql("UPDATE user_profile SET score =score - {$score} WHERE uid='$uid'");
		if($re){
			return $this->addLog(array('log_id'=>'R','temp2'=>'restore_system','temp3'=>$score,'temp4'=>'扣除积分成功','temp5'=>$temp5,'description'=>$description));
		}else{
			$this->addLog(array('uid'=>$uid,'log_id'=>'R','temp1'=>400,'temp2'=>'restore_system','temp3'=>$score,'temp4'=>'扣除积分失败','temp5'=>$temp5,'description'=>$description));
			return false;
		}
	}
	/**
	 * 恢复用户积分
	 */
	public function restore($score,$temp5='',$description='',$uid=''){
		$uid = $uid?$uid:$_SESSION['userInfo']['uidSession'];
		$re = $this->_scoreruleModel->updateBySql("UPDATE user_profile SET score =score + {$score} WHERE uid='$uid'");
		if($re){
			$this->addLog(array('uid'=>$uid,'log_id'=>'R','temp2'=>'restore_system','temp3'=>$score,'temp4'=>'恢复积分成功','temp5'=>$temp5,'description'=>$description));
			return true;
		}else{
			$this->addLog(array('uid'=>$uid,'log_id'=>'R','temp1'=>400,'temp2'=>'restore_system','temp3'=>$score,'temp4'=>'恢复积分失败','temp5'=>$temp5,'description'=>$description));
			return false;
		}
	}
	/**
	 * 添加日志记录
	 */
	public function addLog($data=array()){
		try {
			$scorelogModel = new Default_Model_DbTable_Model('score_log');
			$front = Zend_Controller_Front::getInstance();
			$add_data = array('session_id'=>session_id(),
					'uid'=>$data['uid']?$data['uid']:$_SESSION['userInfo']['uidSession'],
					'ip'=>$this->_fun->getIp(),
					'controller'=>$front->getRequest()->getControllerName(),
					'action'=>$front->getRequest()->getActionName(),
					'created'=>time(),
					'log_id'=>$data['log_id'],
					'temp1'=>$data['temp1'],
					'temp2'=>$data['temp2'],
					'temp3'=>$data['temp3'],
					'temp4'=>$data['temp4'],
					'temp5'=>$data['temp5'],
					'description'=>$data['description']);
			return $scorelogModel->addData($add_data);
		}catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 获取用户积分
	 */
	public function getScoreByUid($uid=''){
		$uid = $uid?$uid:$_SESSION['userInfo']['uidSession'];
		return $this->_scoreruleModel->QueryItem("SELECT (score - score_consume) as score FROM `user_profile` WHERE `uid` ='{$uid}' LIMIT 1");
	}
	/**
	 * 检查用户获取询价积分总计
	 */
	public function getAllScoreBom($uid){
		return $this->_scoreruleModel->QueryItem("SELECT sum(bomscore) FROM bom WHERE uid='{$uid}'");
	}
	/**
	 * 更新用户积分
	 */
	public function upScore($rescore,$uid){
		//更新用户积分
		$re = $this->_scoreruleModel->updateBySql("UPDATE user_profile SET score =score + {$rescore} WHERE uid='$uid'");
		if($re){
			$this->addLog(array('log_id'=>'A','uid'=>$uid,'temp2'=>$uid,'temp3'=>$rescore,'temp4'=>'获取积分成功','description'=>'BOM发放积分成功'));
			return $rescore;
		}else{
			$this->addLog(array('log_id'=>'A','uid'=>$uid,'temp1'=>400,'temp2'=>$uid,'temp3'=>$rescore,'temp4'=>'获取积分失败，更新数据失败','description'=>'BOM发放积分失败'));
			return false;
		}
	}
}