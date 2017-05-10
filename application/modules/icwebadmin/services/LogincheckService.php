<?php
class Icwebadmin_Service_LogincheckService
{
	/*****************************************************************
	 ***			Session Checking!       		***
	*****************************************************************/
	public function sessionChecking()
	{
		$actionname = rawurlencode($_SERVER['REQUEST_URI']);
		//是否登录
		if(!$_SESSION['staff_sess']['staff_id'])
		{
			header("Location: /icwebadmin/index/loginout?url=".$actionname);
			exit();
		}
		$staff = new Icwebadmin_Model_DbTable_Staff();
		//是否被禁用
		$un_mess=$staff->getRowByWhere("(status=0) AND (staff_id='".$_SESSION['staff_sess']['staff_id']."')");
		if($un_mess)
		{
			header("Location: /icwebadmin/index/loginout");
			exit();
		}
		/*****************************************************************
			***			Session Area Update!       		***
		*****************************************************************/
		$rulesarr=$staff->getRowByWhere("(staff_id='".$_SESSION['staff_sess']['staff_id']."')");
		//注册session
		$staff_area = new Zend_Session_Namespace('staff_area');//使用SESSION存储数据时要设置命名空间
		$right_rule = new Zend_Session_Namespace('right_rule');//使用SESSION存储数据时要设置命名空间
		$statistics_rule = new Zend_Session_Namespace('statistics_rule');//使用SESSION存储数据时要设置命名空间
		$staff_area->value = $areatmp = explode (",", $rulesarr['staff_area_rule']);
		$ruletmp = explode (",", $rulesarr['right_rule']);
		for($i=0;$i<count($areatmp);$i++){
			$right_rule->$areatmp[$i] = $ruletmp[$i];
		}
		$statistics_rule->value = ($rulesarr['statistics']?explode(",",$rulesarr['statistics']):array());
	}
	/*****************************************************************
	 ***			Staff_Area Checking!       		***
	*****************************************************************/
	public function staffareaCheck($Staff_Area_ID)
	{
		//检查是否staff area是否已经被禁用
		$sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
		$re = $sectionarea->getRowByWhere("staff_area_id='{$Staff_Area_ID}'");
		if($re['status']!= 1){
			header("Location: /icwebadmin/index/loginout");
			exit();
		}
		if(!in_array($Staff_Area_ID,$_SESSION['staff_area']['value'])){
			header("Location: /icwebadmin/index/loginout");
			exit();
		}
	}
	
}