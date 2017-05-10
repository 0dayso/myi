<?php
class Icwebadmin_Service_FinanceService
{
	/*************************************************************
	 ***		公共函数                                    ***
	**************************************************************/
	private $_saporderModel;
	public function __construct(){
		$this->_saporderModel = new Icwebadmin_Model_DbTable_Model('sap_order');
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getOrderNum($typestr='')
	{
		$sqlstr = "SELECT count(sotmp.salesnumber) as num  FROM (
		SELECT so.salesnumber,so.created FROM sales_order as so
	    WHERE so.available!=0 AND so.paytype='online' 
		      AND so.total>0 AND so.status IN ('201','202','301','302')  AND so.back_status='202'
		union all
		SELECT inqso.salesnumber,inqso.created FROM inq_sales_order as inqso
		WHERE  inqso.available!=0 AND inqso.paytype='online' 
		      AND inqso.total>0 AND inqso.status IN ('103','201','202','301','302') AND inqso.back_status='202') as sotmp
		LEFT JOIN sap_order as sapo ON sotmp.salesnumber = sapo.salesnumber 
		WHERE sotmp.salesnumber!='' $typestr";
		return $this->_saporderModel->QueryItem($sqlstr);
	}
	/*
	 * 获取记录 
	 */
	public function getSapOrder($offset,$perpage,$typestr='')
	{
		if($offset || $perpage) $limt = " LIMIT $offset,$perpage";
		$sqlstr ="SELECT sotmp.*,sapo.check,sapo.ic_ordertype,sapo.auart,sapo.order_no,
		sapo.kunnr,sapo.cname,sapo.bstnk,sapo.invoice_no,u.uname,up.companyname
		FROM
		(SELECT so.salesnumber,so.so_type,so.currency,so.delivery_type,so.uid,so.paytype,so.total,so.status,so.back_status,so.created 
		FROM sales_order as so
		WHERE so.available!=0 AND so.paytype='online' 
		      AND so.total>0 AND so.status IN ('201','202','301','302')  AND so.back_status='202'
		union all
		SELECT inqso.salesnumber,inqso.so_type,inqso.currency,inqso.delivery_type,inqso.uid,inqso.paytype,inqso.total,inqso.status,inqso.back_status,inqso.created 
		FROM inq_sales_order as inqso
		WHERE inqso.available!=0 AND inqso.paytype='online' 
		      AND inqso.total>0 AND inqso.status IN ('103','201','202','301','302') AND inqso.back_status='202') as sotmp
		
		LEFT JOIN sap_order as sapo ON sotmp.salesnumber = sapo.salesnumber
		LEFT JOIN user as u ON sotmp.uid=u.uid
		LEFT JOIN user_profile as up ON up.uid=u.uid
		WHERE sotmp.salesnumber!='' {$typestr}
		ORDER BY sapo.order_no DESC,sotmp.created ASC $limt";

		return $this->_saporderModel->getBySql($sqlstr);
	}
	//更新
	public function update($data, $where){
		return $this->_saporderModel->update($data, $where);
	}
}