<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_SamplesService
{
	private $_samplesModer;
	private $_samplesapplyModel;
	private $_samplesdetailedModel;
	private $_prosql_str;
	public function __construct()
	{
		$this->_samplesModer = new Default_Model_DbTable_Model('samples');
		$this->_samplesapplyModel = new Default_Model_DbTable_Model('samples_apply');
		$this->_samplesdetailedModel = new Default_Model_DbTable_Model('samples_detailed');
	}
	/*
	 * 计算允许申请数量
	 */
	public function getCanApplyNum($part_info){
/*
* 第一台阶单价                数量限制
小于RMB5              10pcs
RMB5~RMB20            5pcs
大于RMB20             2pcs
		*/
		$this->fun = new MyFun();
		$nowtotalrmb = $appnum = 0;
		if($part_info['break_price']){
			$pricearr = array_filter(explode(';',$part_info['break_price']));
			if($pricearr){
				$price = explode('|',$pricearr[0]);
				$nowtotalrmb = ($this->fun->getUSDToRMB()*$price[1])/$price[0];
			}
		}elseif($part_info['break_price_rmb']){
			$pricearr = array_filter(explode(';',$part_info['break_price_rmb']));
			if($pricearr){
				$price = explode('|',$pricearr[0]);
				$nowtotalrmb = $price[1]/$price[0];
			}
		}
		if($nowtotalrmb && $nowtotalrmb<5){
			$appnum = 10;
		}elseif($nowtotalrmb && $nowtotalrmb<=20){
			$appnum = 5;
		}else{
			$appnum = 2;
		}
		return $appnum;
	}
	public function getNum($sql=''){
		$sqlstr = "SELECT count(sp.id) as num FROM samples as sp
		LEFT JOIN product as pro ON sp.part_id=pro.id
		LEFT JOIN brand as br ON pro.manufacturer=br.id
		WHERE sp.status=1 AND br.status = 1 AND pro.status = 1 {$sql}";
		return $this->_samplesModer->QueryItem($sqlstr);
	}
	/*
	 * 根据sql条件获取产品
	*/
	public function getSamplesPage($sql,$offset,$perpage,$orderby=''){
		if(!$orderby) $orderby = 'ORDER BY  sp.id DESC';
		$sqlstr ="SELECT sp.*,pro.part_no,pro.supplier_device_package,pro.packaging,pro.description,pro.manufacturer,pro.part_level2,pro.part_level3,
		br.name as brandname,pc2.name as pcname2,pc3.name as pcname3
		FROM samples as sp
		LEFT JOIN product as pro ON sp.part_id=pro.id
		LEFT JOIN brand as br ON pro.manufacturer=br.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		WHERE sp.status=1 AND br.status = 1 AND pro.status = 1 {$sql} {$orderby} LIMIT {$offset},{$perpage}";
		return $this->_samplesModer->Query($sqlstr);
	}
	public function getSamplesByid($spid){
		$sqlstr ="SELECT sp.*,pro.part_no,pro.supplier_device_package,pro.packaging,pro.description,pro.manufacturer,pro.part_level2,pro.part_level3,
		br.name as brandname,pc2.name as pcname2,pc3.name as pcname3
		FROM samples as sp
		LEFT JOIN product as pro ON sp.part_id=pro.id
		LEFT JOIN brand as br ON pro.manufacturer=br.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		WHERE sp.id = '{$spid}' AND sp.status=1 AND br.status = 1 AND pro.status = 1 LIMIT 1";
		return $this->_samplesModer->QueryRow($sqlstr);
	}
	//样片头部
	public function getSamplesHottop(){
		$sqlstr ="SELECT pro.*,
		br.name as brandname,pc1.name as pcname1,pc2.name as pcname2,pc3.name as pcname3
		FROM samples as sp
		LEFT JOIN product as pro ON sp.part_id=pro.id
		LEFT JOIN brand as br ON pro.manufacturer=br.id
		LEFT JOIN prod_category as pc1 ON pro.part_level1=pc1.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		WHERE sp.hot_top = 1 AND sp.status=1 AND br.status = 1 AND pro.status = 1 LIMIT 1";
		return $this->_samplesModer->QueryRow($sqlstr);
	}
	//热推样片
	public function getSamplesHot(){
		$sqlstr ="SELECT pro.*,
		br.name as brandname,pc1.name as pcname1,pc2.name as pcname2,pc3.name as pcname3
		FROM samples as sp
		LEFT JOIN product as pro ON sp.part_id=pro.id
		LEFT JOIN brand as br ON pro.manufacturer=br.id
		LEFT JOIN prod_category as pc1 ON pro.part_level1=pc1.id
		LEFT JOIN prod_category as pc2 ON pro.part_level2=pc2.id
		LEFT JOIN prod_category as pc3 ON pro.part_level3=pc3.id
		WHERE sp.hot = 1 AND pro.samples=1 AND sp.hot_top != 1 AND sp.status=1 AND br.status = 1 AND pro.status = 1 ORDER BY sp.id DESC LIMIT 0,18";
		return $this->_samplesModer->Query($sqlstr);
	}
	public function getSamplesByWhere($where=''){
		$sqlstr ="SELECT pro.id,pro.part_no
		FROM product as pro
		WHERE pro.status = 1 AND pro.samples=1 {$where} ORDER BY pro.`part_no` ASC";
		return $this->_samplesModer->Query($sqlstr);
	}
	/*
	 * 添加申请记录
	 */
	public function addapply($data){
		return $this->_samplesapplyModel->addData($data);
	}
	/*
	 * 添加申请记录
	*/
	public function dataapply($data){
		$mo = new Default_Model_DbTable_Model('data_apply');
		return $mo->addData($data);
	}
	/*
	 * 添加申请详细
	*/
	public function adddetaileds($datas){
		return $this->_samplesdetailedModel->addDatas($datas);
	}
	/**
	 * 检查是否已经提交过申请
	 */
	public function checkApplyPartid($partid){
		$re = $this->_samplesapplyModel->QueryItem("SELECT spa.id  
				FROM samples_apply as spa
				LEFT JOIN samples_detailed as spd ON spa.id=spd.applyid
		WHERE spa.status=100 AND spa.uid='".$_SESSION['userInfo']['uidSession']."' AND spd.part_id='".$partid."'");
		if($re) return true;
		else return false;
	}
	/**
	 * 检查是否已经提交过申请
	 */
	public function checkDataApply($brandid){
		$re = $this->_samplesapplyModel->QueryItem("SELECT spa.id FROM data_apply as spa
		WHERE spa.status=100 AND spa.uid='".$_SESSION['userInfo']['uidSession']."' AND spa.brandid='".$brandid."'");
		if($re) return true;
		else return false;
	}
	/**
	 * 检查申请数量
	 */
	public function checkApplyNum($maxnum){
		$num = $this->_samplesapplyModel->QueryItem("SELECT count(spa.id) as num  
		FROM samples_apply as spa
		WHERE spa.uid='".$_SESSION['userInfo']['uidSession']."' AND spa.status=100");
		if($num >= $maxnum) return true;
		else return false;
	}
	/**
	 * 获取样片申请数
	 */
	public function getApplyNum($typestr='')
	{
		$sqlstr = "SELECT count(spa.id) as num  FROM samples_apply as spa
		WHERE spa.id!='' AND spa.uid='".$_SESSION['userInfo']['uidSession']."' $typestr" ;
		return $this->_samplesapplyModel->QueryItem($sqlstr);
	}
	/**
	 * 获取样片申请记录
	 */
	public function getApply($offset,$perpage,$typestr='')
	{
		$sqlstr ="SELECT spa.*,u.uname,up.companyname,
		p.province,c.city,e.area,
		a.name as sname,a.address,a.zipcode,a.mobile,a.tel,
		ch.cou_number,ch.track,cou.name as couname
		FROM samples_apply as spa
		LEFT JOIN user as u ON u.uid = spa.uid
		LEFT JOIN user_profile as up ON up.uid = spa.uid
	
		LEFT JOIN order_address as a ON spa.addressid=a.id
		LEFT JOIN province as p ON a.province=p.provinceid
		LEFT JOIN city as c ON a.city=c.cityid
		LEFT JOIN area as e ON a.area = e.areaid
		 
		LEFT JOIN courier_history as ch ON ch.id = spa.courierid
		LEFT JOIN courier as cou ON ch.cou_id = cou.id
		 
		WHERE spa.id!='' AND spa.uid='".$_SESSION['userInfo']['uidSession']."' $typestr
		ORDER BY spa.id DESC LIMIT $offset,$perpage";
		$re = $this->_samplesapplyModel->getBySql($sqlstr);
		foreach($re as $k=>$v){
			$re[$k]['detailed'] = $this->_samplesdetailedModel->getAllByWhere("applyid='".$v['id']."'");
		}
		return $re;
	}
	public function beginTransaction(){
		$this->_samplesapplyModel->beginTransaction();
	}
	public function commit(){
		$this->_samplesapplyModel->commit();
	}
	public function rollBack(){
		$this->_samplesapplyModel->rollBack();
	}
	/*
	 * 发邮件提醒
	 */
	public function emailalert($re,$uid){
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">有客户新提交了样片申请，请在及时处理。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">详细资料和询价信息请登录&nbsp;<a href="http://www.iceasy.com/icwebadmin/SampSqgl" target="_blank"><b>样片订单管理</b></a>&nbsp;查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$this->samples = new Icwebadmin_Service_SamplesService();
		$this->_samplesservice = new Icwebadmin_Service_SamplesService();
		$apply = $this->_samplesservice->getApplyById($re);
		$mess .= $this->samples->getTable($apply,$hi_mess);

		$fromname = 'IC易站';
		$title    = '客户新样片申请，请处理，编号：'.$re;
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('new_samples');
		
		//销售信息
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($uid);
		$emailto = array('0'=>$sellinfo['email']);
		$emailcc = array();
		$emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		$this->_fun =$this->view->fun= new MyFun();
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
		
	}
	/*
	 * 发邮件提醒
	*/
	public function dataemailalert(){
		$hi_mess = '<table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';text-align:left">
                            <tbody>
                                <tr>
                                    <td valign="middle" >
                                        <table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                                            <tr>
                                                <td>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">有客户新提交了选型资料申请，请在及时处理。</div>
                                                <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">详细资料和询价信息请登录&nbsp;<a href="http://www.iceasy.com/icwebadmin/SampSxsq" target="_blank"><b>选型资料申请</b></a>&nbsp;查看。</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>';
		$mess ='<tr>
                    <td valign="top" bgcolor="#ffffff" align="center">'.$hi_mess.'</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>';
	
		$fromname = 'IC易站';
		$title    = '客户提交选型资料申请，请处理';
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('new_dataapply');
	

		$emailto = array();
		$emailcc = array();
		$emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		$this->_fun =$this->view->fun= new MyFun();
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	
	}
}