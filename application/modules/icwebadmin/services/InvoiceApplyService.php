<?php
class Icwebadmin_Service_InvoiceApplyService
{
	private $_inqappModer;
	private $_orderModel;
	private $_soprodModel;
	public function __construct()
	{
		$this->_inqappModer = new Icwebadmin_Model_DbTable_InvoiceApply();
		$this->_orderModel  = new Icwebadmin_Model_DbTable_SalesOrder();
		$this->_soprodModel = new Icwebadmin_Model_DbTable_SalesProduct();
	}
	/**
	 * 获取数量
	 */
	public function getNum($sql)
	{
		$sqlstr = "SELECT count(ia.id) as num FROM invoice_apply as ia WHERE ia.id!='' {$sql}";
		$allnumarr = $this->_inqappModer->getByOneSql($sqlstr);
		return $allnumarr['num'];
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
        WHERE ia.id!=''  {$sql}
		ORDER BY sotmp.created DESC ,ia.status DESC  LIMIT $offset,$perpage";
		return $this->_inqappModer->getBySql($sqlstr);
	}
	/**
	 * 获取用户发票申请
	*/
	public function getInvoiceApply($offset,$perpage)
	{
		$sqlstr ="SELECT sotmp.*,ia.status as iastatus,ia.remark as iaremark 
		FROM invoice_apply as ia
		LEFT JOIN
		(SELECT so.salesnumber,so.uid,so.partnos,so.currency,so.total,so.paytype,so.consignee,so.so_type,so.created 
		FROM sales_order as so
		WHERE so.available!=0 AND so.status='301'  AND so.invoiceid='0'
		union all
		SELECT inqso.salesnumber,inqso.uid,inqso.partnos,inqso.currency,inqso.total,inqso.paytype,inqso.consignee,inqso.so_type,inqso.created 
		FROM inq_sales_order as inqso
		WHERE inqso.available!=0 AND inqso.status='301' AND inqso.invoiceid='0') as sotmp
        ON sotmp.salesnumber = ia.salesnumber
        WHERE ia.status=101
		ORDER BY sotmp.created DESC ,ia.status DESC  LIMIT $offset,$perpage";
		return $this->_inqappModer->getBySql($sqlstr);
	}
	/**
	 * 获取so信息
	 */
	public function geSoinfo($salesnumber){
		$salesorderModel   = new Default_Model_DbTable_SalesOrder();
		$sqlstr ="SELECT so.id,so.salesnumber,so.invoiceid,so.paytype,so.freight,so.currency,so.delivery_place,so.total,so.invoiceid,so.status,so.created,so.paytype,so.so_type,so.status,so.back_status,
        u.uname,u.email,up.companyname,
        p.province,c.city,e.area,
    	a.name,a.address,a.zipcode,a.mobile,a.tel,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name,
		ia.addressid as iaaddressid,ia.invoiceid as iainvoiceid,ia.status as iastatus,ia.remark as iaremark
    	FROM sales_order as so
        LEFT JOIN user as u ON so.uid=u.uid
    	LEFT JOIN user_profile as up ON so.uid=up.uid
    	LEFT JOIN order_address as a ON so.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	LEFT JOIN invoice as i ON so.invoiceid=i.id
    	LEFT JOIN courier_history as coh ON so.courierid=coh.id
    	LEFT JOIN courier as co ON coh.cou_id=co.id
		LEFT JOIN invoice_apply as ia ON ia.salesnumber=so.salesnumber
    	WHERE so.salesnumber=:sonum AND so.available='1'";
		$orderarr = $this->_orderModel->getByOneSql($sqlstr, array('sonum'=>$salesnumber));
		if(!empty($orderarr)){
			$pordarr = $this->_soprodModel->getAllByWhere("salesnumber='".$salesnumber."'");
			if(empty($pordarr)) return false;
			else{
				$orderarr['pordarr'] = $pordarr;
				return $orderarr;
			}
		}else return false;
	}
	/**
	 * 获取inqso信息
	 */
	public function geInqSoinfo($salesnumber){
		$sqlstr ="SELECT so.id,so.salesnumber,so.invoiceid,so.paytype,so.freight,so.currency,so.delivery_place,so.total,so.invoiceid,so.status,so.created,so.so_type,so.paytype,so.status,so.back_status,
        u.uname,u.email,up.companyname,
        p.province,c.city,e.area,
    	a.name,a.address,a.zipcode,a.mobile,a.tel,
    	i.type as itype,i.name as iname,i.contype,i.identifier,i.regaddress,i.regphone,i.bank,i.account,
    	coh.cou_number,co.name as cou_name,
		ia.addressid as iaaddressid,ia.invoiceid as iainvoiceid,ia.status as iastatus,ia.remark as iaremark
    	FROM inq_sales_order as so
        LEFT JOIN user as u ON so.uid=u.uid
    	LEFT JOIN user_profile as up ON so.uid=up.uid
    	LEFT JOIN order_address as a ON so.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	LEFT JOIN invoice as i ON so.invoiceid=i.id
    	LEFT JOIN courier_history as coh ON so.courierid=coh.id
    	LEFT JOIN courier as co ON coh.cou_id=co.id
		LEFT JOIN invoice_apply as ia ON ia.salesnumber=so.salesnumber
    	WHERE so.salesnumber=:sonum AND so.available='1'";
		$orderarr = $this->_orderModel->getByOneSql($sqlstr, array('sonum'=>$salesnumber));
		if(!empty($orderarr)){
			$pordarr = $this->_soprodModel->getAllByWhere("salesnumber='".$salesnumber."'");
			if(empty($pordarr)) return false;
			else{
				$orderarr['pordarr'] = $pordarr;
				return $orderarr;
			}
		}else return false;
	}
}