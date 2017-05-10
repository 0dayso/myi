<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_InvoiceService
{
	private $_invoiceModel;
	private $_invoiceappModel;
	public function __construct() {
		$this->_invoiceModell = new Default_Model_DbTable_Invoice();
		$this->_invoiceappModel = new Default_Model_DbTable_InvoiceApply();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
	}
	
	/**
	 * 获取没有发票总数
	 */
    public function getNoNum()
	{
		$sqlstr = "SELECT so.salesnumber  FROM sales_order as so 
				WHERE so.uid='".$_SESSION['userInfo']['uidSession']."' AND so.available!=0 AND so.status IN('201','202','301','302') AND so.back_status=202 AND so.invoiceid='0' 
				union all 
				SELECT inqso.salesnumber FROM inq_sales_order as inqso 
				WHERE inqso.uid='".$_SESSION['userInfo']['uidSession']."' AND inqso.available!=0 AND inqso.status IN('201','202','301','302') AND inqso.back_status=202 AND inqso.invoiceid='0'";
		$allrel = $this->_invoiceModell->getBySql($sqlstr);
		return count($allrel);
	}
	/**
	 * 获取通过审批数
	 */
	public function getNum($sql)
	{
		$sqlstr = "SELECT count(ia.id) as num FROM invoice_apply as ia WHERE ia.uid='".$_SESSION['userInfo']['uidSession']."' {$sql}";
		$allnumarr = $this->_invoiceappModel->getByOneSql($sqlstr);
		return $allnumarr['num'];
	}
	/**
	 * 获取没有发票订单信息（包括在线和询价订单）
	 */
	public function getNoRecord($offset,$perpage)
	{
		$sqlstr ="SELECT sotmp.*,ia.status as iastatus,ia.remark as iaremark FROM
		(SELECT so.salesnumber,so.uid,so.partnos,so.currency,so.total,so.paytype,so.consignee,so.so_type,so.created 
		FROM sales_order as so
		WHERE so.uid='".$_SESSION['userInfo']['uidSession']."' AND so.available!=0 AND so.status IN('201','202','301','302')  AND so.back_status=202 AND so.invoiceid='0'
		union all
		SELECT inqso.salesnumber,inqso.uid,inqso.partnos,inqso.currency,inqso.total,inqso.paytype,inqso.consignee,inqso.so_type,inqso.created 
		FROM inq_sales_order as inqso
		WHERE inqso.uid='".$_SESSION['userInfo']['uidSession']."' AND inqso.available!=0 AND inqso.status IN('201','202','301','302') AND inqso.back_status=202 AND inqso.invoiceid='0') as sotmp
	    LEFT JOIN invoice_apply as ia ON ia.salesnumber=sotmp.salesnumber
		ORDER BY ia.status ASC,sotmp.created DESC  LIMIT $offset,$perpage";
		return $this->_invoiceModell->getBySql($sqlstr);
	}
	/**
	 * 获取通过和不通过的申请
	 */
	public function getRecord($offset,$perpage,$sql)
	{
		$sqlstr ="SELECT sotmp.*,ia.status as iastatus,ia.remark as iaremark 
		FROM invoice_apply as ia
		LEFT JOIN
		(SELECT so.salesnumber,so.uid,so.partnos,so.currency,so.total,so.paytype,so.consignee,so.so_type,so.created 
		FROM sales_order as so
		WHERE so.available!=0 AND so.status IN('201','202','301','302')
		union all
		SELECT inqso.salesnumber,inqso.uid,inqso.partnos,inqso.currency,inqso.total,inqso.paytype,inqso.consignee,inqso.so_type,inqso.created 
		FROM inq_sales_order as inqso
		WHERE inqso.available!=0 AND inqso.status IN('201','202','301','302')) as sotmp
        ON sotmp.salesnumber = ia.salesnumber
        WHERE ia.uid='".$_SESSION['userInfo']['uidSession']."'  {$sql}
		ORDER BY sotmp.created DESC ,ia.status DESC  LIMIT $offset,$perpage";
		return $this->_invoiceappModel->getBySql($sqlstr);
	}
}