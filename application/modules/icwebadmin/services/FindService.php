<?php
class Icwebadmin_Service_FindService
{
	private $_staffservice;
	private $_inqservice;
	private $_inqsoservice;
	private $_soservice;
	public function __construct()
	{
		$this->_staffservice = new Icwebadmin_Service_StaffService();
		$this->_inqservice = new Icwebadmin_Service_InquiryService();
		$this->_inqsoservice = new Icwebadmin_Service_InqOrderService();
		$this->_soservice = new Icwebadmin_Service_OrderService();
	}
	public function getInqByInq($inq_number){
		return $this->getInqByWhere("iq.inq_number LIKE '%".$inq_number."%'");
	}
	public function getInqByProd($part_no){
		$this->_inqModer = new Icwebadmin_Model_DbTable_Inquiry();
		//1.询价记录
		$inqwhere = "  ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$inqwhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(iq.id),iq.uid,iq.inq_number,inq.part_no,inq.pmpq,iq.created,iq.status,inq.inq_id,inq.part_id,inq.part_no,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM inquiry_detailed as inq
    			LEFT JOIN inquiry as iq ON iq.id = inq.inq_id
    			LEFT JOIN user as u ON u.uid = iq.uid
    			LEFT JOIN user_profile as up ON up.uid = iq.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE iq.inq_number!='' AND inq.part_no LIKE '%".$part_no."%' $inqwhere";
		return $this->_inqModer->getBySql($sqlstr);
	}
	public function getInqByOrder($inqid){
		return $this->getInqByWhere("iq.id = '".$inqid."'");
	}
	public function getInqByWhere($where){
		$this->_inqModer = new Icwebadmin_Model_DbTable_Inquiry();
		//1.询价记录
		$inqwhere = " ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$inqwhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(iq.id),iq.uid,iq.inq_number,iq.created,iq.status,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM inquiry as iq
    			LEFT JOIN user as u ON u.uid = iq.uid
    			LEFT JOIN user_profile as up ON up.uid = iq.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE iq.inq_number!='' AND $where $inqwhere";
		$inqArr = $this->_inqModer->getBySql($sqlstr);
		if(!empty($inqArr)){
			foreach($inqArr as $k=>$inq){
				$inqArr[$k]['detaile']=$this->_inqservice->getDetailedInquiry($inq['id']);
			}
		}
		return $inqArr;
	}
	
	public function getSoByOrder($salesnumber){
		return $this->getSo("so.salesnumber LIKE '%".$salesnumber."%'");
	}
	public function getSoByProd($part_no){
		$this->_soModer = new Icwebadmin_Model_DbTable_SalesOrder();
		//1.订单记录
		$sowhere = " ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(so.id),so.uid,so.salesnumber,so.created,so.status,sp.prod_id,sp.part_no,sp.buynum,
		u.uname,u.email,up.companyname,st.lastname,st.firstname
		FROM sales_product as sp
		LEFT JOIN sales_order as so ON so.salesnumber = sp.salesnumber
		LEFT JOIN user as u ON u.uid = so.uid
		LEFT JOIN user_profile as up ON up.uid = so.uid
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
		WHERE so.salesnumber!='' AND sp.part_no LIKE '%".$part_no."%' $sowhere";
		$Arr = $this->_soModer->getBySql($sqlstr);
		if(!empty($Arr)){
			foreach($Arr as $k=>$inq){
				$Arr[$k]['detaile']=$this->_inqservice->getDetailedInquiry($inq['prod_id']);
			}
		}
		return $Arr;
	}
	
	public function getSo($where){
		$this->_soModer = new Icwebadmin_Model_DbTable_SalesOrder();
		//1.订单记录
		$sowhere = " ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(so.salesnumber),so.id,so.uid,so.created,so.status,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM sales_order as so
    			LEFT JOIN user as u ON u.uid = so.uid
    			LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE so.salesnumber!='' AND $where $sowhere";
		$Arr = $this->_soModer->getBySql($sqlstr);
		if(!empty($Arr)){
			foreach($Arr as $k=>$so){
				$Arr[$k]['detaile']=$this->_soservice->getSoPart($so['salesnumber']);
			}
		}
		return $Arr;
	}
	public function getInqSoByOrder($salesnumber){
		return $this->getInqSo("so.salesnumber LIKE '%".$salesnumber."%'");
	}
	public function getInqSoByProd($part_no){
			$this->_inqsoModer = new Icwebadmin_Model_DbTable_InqSalesOrder();
			//1.订单记录
			$sowhere = " ";
			//如果销售只能看到自己负责的询价
			$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
			if($staffinfo['level_id']=='XS'){
				$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
			}
			$sqlstr = "SELECT distinct(so.salesnumber),so.back_order,so.id,so.uid,so.inquiry_id,so.created,so.status,sp.prod_id,sp.part_no,sp.buynum,
			u.uname,u.email,up.companyname,st.lastname,st.firstname
			FROM sales_product as sp
			LEFT JOIN inq_sales_order as so ON so.salesnumber = sp.salesnumber
			LEFT JOIN user as u ON u.uid = so.uid
			LEFT JOIN user_profile as up ON up.uid = so.uid
			LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
			WHERE so.salesnumber!='' AND sp.part_no LIKE '%".$part_no."%' $sowhere";
			return $this->_inqsoModer->getBySql($sqlstr);
	}
	public function getInqSoByInq($inqid){
		return $this->getInqSo("so.inquiry_id=$inqid");
	}
	public function getInqSo($where){
		$this->_inqsoModer = new Icwebadmin_Model_DbTable_InqSalesOrder();
		//1.订单记录
		$sowhere = " ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(so.salesnumber),so.back_order,so.id,so.uid,so.inquiry_id,so.created,so.status,
		u.uname,u.email,up.companyname,st.lastname,st.firstname
		FROM inq_sales_order as so
		LEFT JOIN user as u ON u.uid = so.uid
		LEFT JOIN user_profile as up ON up.uid = so.uid
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
		WHERE so.salesnumber!='' AND $where $sowhere";
		$Arr = $this->_inqsoModer->getBySql($sqlstr);
		if(!empty($Arr)){
			foreach($Arr as $k=>$inqso){
				$Arr[$k]['detaile']=$this->_inqsoservice->getSoPart($inqso['salesnumber']);
			}
		}
		return $Arr;
	}
	
	public function getInqByCompanyname($companyname){
		$this->_userModer = new Icwebadmin_Model_DbTable_User();
		//1.询价记录
		$inqwhere = "  ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$inqwhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(iq.id),iq.uid,iq.inq_number,iq.created,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM user_profile as up
    			LEFT JOIN inquiry as iq ON iq.uid = up.uid
    			LEFT JOIN user as u ON u.uid = up.uid 
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE iq.inq_number!='' AND up.companyname LIKE '%".$companyname."%' $inqwhere";
		$inqArr = $this->_userModer->getBySql($sqlstr);
		if(!empty($inqArr)){
			foreach($inqArr as $k=>$inq){
				$inqArr[$k]['detaile']=$this->_inqservice->getDetailedInquiry($inq['id']);
			}
		}
		return $inqArr;
	}
	public function getSoByCompanyname($companyname){
		$this->_soModer = new Icwebadmin_Model_DbTable_SalesOrder();
		//1.订单记录
		$sowhere = "  ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(so.id),so.uid,so.salesnumber,so.created,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM user_profile as up
    			LEFT JOIN sales_order as so ON so.uid = up.uid
    			LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE so.salesnumber!='' AND up.companyname LIKE '%".$companyname."%' $sowhere";
		$Arr = $this->_soModer->getBySql($sqlstr);
		if(!empty($Arr)){
			foreach($Arr as $k=>$so){
				$Arr[$k]['detaile']=$this->_soservice->getSoPart($so['salesnumber']);
			}
		}
		return $Arr;
	}
	public function getInqSoByCompanyname($companyname){
		$this->_inqsoModer = new Icwebadmin_Model_DbTable_InqSalesOrder();
		//1.订单记录
		$sowhere = "  ";
		//如果销售只能看到自己负责的询价
		$staffinfo = $this->_staffservice->getStaffInfo($_SESSION['staff_sess']['staff_id']);
		if($staffinfo['level_id']=='XS'){
			$sowhere .= " AND up.staffid='".$_SESSION['staff_sess']['staff_id']."'";
		}
		$sqlstr = "SELECT distinct(so.id),so.uid,so.back_order,so.salesnumber,so.created,
    			u.uname,u.email,up.companyname,st.lastname,st.firstname
    			FROM user_profile as up
    			LEFT JOIN inq_sales_order as so ON so.uid = up.uid
    			LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			WHERE so.salesnumber!='' AND up.companyname LIKE '%".$companyname."%' $sowhere";
		$Arr = $this->_inqsoModer->getBySql($sqlstr);
		if(!empty($Arr)){
			foreach($Arr as $k=>$inqso){
				$Arr[$k]['detaile']=$this->_inqsoservice->getSoPart($inqso['salesnumber']);
			}
		}
		return $Arr;
	}
	/**
	 * 样片订单
	 */
	public function getSamples($sqrstr){
		$samplesservice = new Icwebadmin_Service_SamplesService();
		return $this->getApply($sqrstr);
	}
	public function getApply($typestr='')
	{
		$this->_samplesapplyModel = new Icwebadmin_Model_DbTable_Model("samples_apply");
		$sqlstr ="SELECT spa.*,u.uname,up.companyname,up.oa_code,
		sd.part_no,sd.approvenum,
		st.staff_id,st.email,st.tel,st.ext,st.phone,st.lastname,st.firstname,dp.department
		FROM samples_apply as spa
		LEFT JOIN samples_detailed as sd ON sd.applyid = spa.id
		LEFT JOIN user as u ON u.uid = spa.uid
		LEFT JOIN user_profile as up ON up.uid = spa.uid
		LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
		LEFT JOIN admin_department as dp ON st.department_id = dp.department_id
		WHERE spa.salesnumber!=''  $typestr ";
		$re = $this->_samplesapplyModel->getBySql($sqlstr);
		return $re;
	}
}