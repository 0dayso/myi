<?php
class Icwebadmin_Service_UninquiryService
{
	private $_uninqModer;
	private $_uninqdetModer;
	private $_unsoModer;
	public function __construct()
	{
		$this->_uninqModer        = new Icwebadmin_Model_DbTable_Uninquiry();
		$this->_uninqdetModer     = new Icwebadmin_Model_DbTable_UninquiryDetailed();
		$this->_unsoModer         = new Icwebadmin_Model_DbTable_UninqSalesorder();
	}
	/**
	 * 添加报价记录
	 */
	public function addUninq($data)
	{
		return $this->_uninqModer->addData($data);
	}
	/**
	 * 添加报价详细记录
	 */
	public function addUninqDets($datas)
	{
		return $this->_uninqdetModer->addDatas($datas);
	}
	/**
	 * 获取报价等待下单数量
	 */
	public function getInqWaitNum()
	{
		$allver = $this->_uninqModer->getByOneSql("SELECT count(id) as num FROM un_inquiry WHERE status!='0'");
		return $allver['num'];
	}
	/**
	 * 获取报价已经下单数量
	 */
	public function getInqAlreadyNum()
	{
		$allver = $this->_uninqModer->getByOneSql("SELECT count(id) as num FROM un_inquiry WHERE status='4'");
		return $allver['num'];
	}
	/**
	 * 获取搜索数量
	 */
	public function getOrderSelectNum($keyword)
	{
		$where="`salesnumber` LIKE '%".$keyword. "%' OR `internal_inqid` LIKE '%".$keyword. "%'";
		$sqlstr ="SELECT count(id) as num FROM `un_inq_sales_order` WHERE available!=0 AND {$where}";
		$allver = $this->_uninqModer->getByOneSql($sqlstr);
		return $allver['num'];
	}
	
	/**
	 * 获取报价等待下单记录
	*/
	public function getWaitUninquiry($offset,$perpage)
	{
		$sqlstr = "SELECT uinq.*,u.companyname,u.companyname_en,st.lastname,st.firstname
		FROM un_inquiry as uinq
		LEFT JOIN un_user as u ON uinq.un_uid =u.un_uid
		LEFT JOIN admin_staff as st ON uinq.staffid=st.staff_id
		WHERE uinq.status!='0'  ORDER BY uinq.status ASC LIMIT $offset,$perpage";
		return $this->getUninquiryBySql($sqlstr);
	}
	/**
	 * 获取报价已下单记录
	 */
	public function getAlreadyUninquiry($offset,$perpage)
	{
		$sqlstr =  "SELECT uinq.*,u.companyname,u.companyname_en,st.lastname,st.firstname,unso.sap_so,unso.total 
		FROM un_inquiry as uinq
		LEFT JOIN un_user as u ON uinq.un_uid =u.un_uid
		LEFT JOIN admin_staff as st ON uinq.staffid=st.staff_id
		LEFT JOIN un_inq_sales_order as unso ON uinq.id=unso.un_inq_id
		WHERE uinq.status='4' ORDER BY uinq.created DESC LIMIT $offset,$perpage";
		return $this->getUninquiryBySql($sqlstr);
	}
	/**
	 * 获取搜索订单
	 */
	public function getSelectOrder($keyword,$offset,$perpage)
	{
		$where="(unso.salesnumber LIKE '%".$keyword. "%' OR unso.`internal_inqid` LIKE '%".$keyword. "%')";
		$sqlstr =  "SELECT uinq.*,u.companyname,u.companyname_en,st.lastname,st.firstname,unso.sap_so,unso.total
		FROM un_inquiry as uinq
		LEFT JOIN un_user as u ON uinq.un_uid =u.un_uid
		LEFT JOIN admin_staff as st ON uinq.staffid=st.staff_id
		LEFT JOIN un_inq_sales_order as unso ON uinq.id=unso.un_inq_id
		WHERE {$where} ORDER BY uinq.status ASC LIMIT $offset,$perpage";
		return $this->getUninquiryBySql($sqlstr);
	}
	/**
	 *根据id获取记录
	 */
	public function getUninqByid($id)
	{
		$sqlstr =  "SELECT uinq.*,un.companyname,un.companyname_en,st.lastname,st.firstname,u.uname FROM un_inquiry as uinq
		LEFT JOIN admin_staff as st ON uinq.staffid=st.staff_id
		LEFT JOIN user as u ON uinq.uid=u.uid
		LEFT JOIN un_user as un ON uinq.un_uid =un.un_uid
		WHERE uinq.id='{$id}'";
		$uninq = $this->getUninquiryBySql($sqlstr);
		return $uninq[0];
	}
	/**
	 * 获取记录bysql
	*/
	private function getUninquiryBySql($sqlstr){
		$inqArr = $this->_uninqModer->getBySql($sqlstr);
		if(!empty($inqArr)){
			foreach($inqArr as $k=>$inq){
				$inqArr[$k]['detaile']=$this->getDetailed($inq['id']);
			}
		}
		return $inqArr;
	}
	/**
	 * 获取用户询价详细记录
	*/
	private function getDetailed($inqid)
	{
		$sqlstr = "SELECT * FROM un_inquiry_detailed WHERE un_inq_id='{$inqid}'";
		return $this->_uninqdetModer->getBySql($sqlstr);
	}
	/**
	 * 更新报价单
	 */
	public function updateUninq($data,$uninqid)
	{
		$this->_uninqModer->updateById($data,$uninqid);
	}
	/**
	 * 添加订单
	 */
	public function addOrder($data)
	{
		return $this->_unsoModer->addData($data);
	}
	/**
	 * 获取订单号
	 */
	public function getSaleso($uninqid)
	{
		return $this->_unsoModer->getRowByWhere("un_inq_id='{$uninqid}'");
	}
	/**
	 * 获取ic易订单号和内部订单号
	 */
	public function getAjaxAllso($keyword)
	{
		$where="`salesnumber` LIKE '%".$keyword. "%' OR `internal_inqid` LIKE '%".$keyword. "%'";
		$sqlstr ="SELECT `salesnumber`,`internal_inqid` FROM `un_inq_sales_order` WHERE available!=0 AND {$where}";
		return $this->_unsoModer->getBySql($sqlstr);
	}
	/**
	 * 获取公司名称
	 */
	public function getCompany($keyword)
	{
		$upModel = new Icwebadmin_Model_DbTable_UserProfile();
		$where="`companyname` LIKE '%".$keyword."%'";
		$sqlstr ="SELECT `companyname` FROM `un_user` WHERE {$where}";
		return $upModel->getBySql($sqlstr);
	}
	/**
	 * 根据公司名称获取uid
	 */
	public function getUidByCompany($company)
	{
		$upModel = new Icwebadmin_Model_DbTable_UserProfile();
		$where="`companyname` = '$company'";
		$up = $upModel->getRowByWhere($where);
		return $up['uid'];
	}
	
}