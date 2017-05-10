<?php
class Icwebadmin_Service_UserruleService
{
	private $_userruledetailedModel;
	public function __construct() {
		$this->_userruledetailedModel = new Icwebadmin_Model_DbTable_Model("user_rule_detailed");
	}
	/**
	 * 获取用户申请权限数量
	 */
	public function getRuleDetailedNum($wherestr){
		$sqlstr = "SELECT count(urd.id) as allnum FROM user_rule_detailed as urd
		WHERE urd.uid!='' {$wherestr}";
		return $this->_userruledetailedModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	 */
	public function getRuleDetailed($offset,$perpage,$sql){
		$sqlstr = "SELECT urd.*,sol.title,up.companyname,st.lastname,st.firstname
    	           FROM  user_rule_detailed as urd
				   LEFT JOIN solution as sol ON sol.id =urd.did
				   LEFT JOIN user_profile as up ON urd.uid=up.uid
				   LEFT JOIN admin_staff as st ON st.staff_id = urd.modified_by
    	           WHERE type='solution' $sql
		    	   ORDER BY urd.id DESC
		    	   LIMIT $offset,$perpage ";
		return $this->_userruledetailedModel->getBySql($sqlstr);
	}
	/**
	 * 获取单条
	 */
	public function getRuleDetailedByid($id){
		$sqlstr = "SELECT urd.*,sol.title,up.companyname
		FROM  user_rule_detailed as urd
		LEFT JOIN solution as sol ON sol.id =urd.did
		LEFT JOIN user_profile as up ON urd.uid=up.uid
		WHERE type='solution' AND urd.id='{$id}'";
		return $this->_userruledetailedModel->QueryRow($sqlstr);
	}
	/**
	 * 更新
	 */
	public function updateRuleDetailed($data,$where){
		return $this->_userruledetailedModel->update($data, $where);
	}
}