<?php
class Default_Service_UserruleService
{
	private $_userruleModel;
	private $_userruledetailedModel;
	public function __construct() {
		$this->_userruleModel = new Default_Model_DbTable_Model("user_rule");
		$this->_userruledetailedModel = new Default_Model_DbTable_Model("user_rule_detailed");
	}
	/**
	 * 获取用户权限
	 */
	public function getRuleByType($type)
	{
		$sqlstr = "SELECT ur.*,urg.group_name
				   FROM user_rule as ur 
				   LEFT JOIN user_rule_group as urg ON ur.group_id = urg.id
				   LEFT JOIN user_profile as up ON up.group_id = ur.group_id
				   WHERE up.uid='".$_SESSION['userInfo']['uidSession']."' 
		           AND ur.type='{$type}' AND ur.status=1 AND urg.status=1
		           ORDER BY ur.displayorder ASC";
		return $this->_userruleModel->Query($sqlstr);
	}
	/**
	 * 获取应用方案权限
	 */
	public function getSolutionRule($solutionRule){
		$re = array();
		//访问权限
		foreach($solutionRule as $srv){
			$re[$srv['area']] = $srv['right'];
		}
		return $re;
	}
	/**
	 * 获取应用方案权限
	 */
	public function getSolutionRuleBys($areas,$rights){
		$re = array();
		//访问权限
		if($areas && $rights){
		  $areaarr = explode(',', $areas);
		  $rightarr = explode(',', $rights);
		  foreach($areaarr as $k=>$a){
		  	if($a && $rightarr[$k])
			   $re[$a] = $rightarr[$k];
		  }
		}
		return $re;
	}
	/**
	 * 获取单个权限
	 */
	public function getRuleDetailed($did,$type){
		return $this->_userruledetailedModel->getRowByWhere("status = 1 AND uid=".$_SESSION['userInfo']['uidSession']." AND did='$did' AND type='$type'");
	}
}