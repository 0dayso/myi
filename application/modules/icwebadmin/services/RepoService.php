<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_RepoService
{
	private $_salesproductModel;
	private $_fun;
	public function __construct() {
		$this->_salesproductModel = new Icwebadmin_Model_DbTable_Model('sales_product');
		$this->_fun = new MyFun();
	}
	/*
	 * 销售业绩报告
	*/
	public function sellRepo($sql,$type,$sql2='',$ordertype=''){

		if($type!='normal' && $ordertype!='online'){
		$onlineorder = $this->_salesproductModel->Query("SELECT sp.*,inv.name as invname,inv.type as invtype,so.currency,u.uname,u.email as uemail,up.companyname,st.*,
				 dp.department,sapo.auart,sapo.order_no,sapo.kunnr,sapo.cname,sapo.bstnk
				 FROM sales_product as sp
    			 LEFT JOIN sales_order as so ON sp.salesnumber = so.salesnumber
    			 LEFT JOIN invoice as inv ON inv.id = so.invoiceid
    			 LEFT JOIN user_profile as up ON up.uid = so.uid
    			 LEFT JOIN user as u ON u.uid = so.uid
    			 LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			 LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				 LEFT JOIN product as po ON po.id = sp.prod_id
				 LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
    			 WHERE so.total >0 AND so.status IN('201','202','301','302') AND so.back_status IN('201','202') $sql ORDER BY so.created ASC");
		}else $onlineorder = array();
    	$inqorder = $this->_salesproductModel->Query("SELECT sp.*,inv.name as invname,inv.type as invtype,so.currency,u.uname,u.email as uemail,up.companyname,st.*,
    			 dp.department,sapo.auart,sapo.order_no,sapo.kunnr,sapo.cname,sapo.bstnk
    			 FROM sales_product as sp
    			 LEFT JOIN inq_sales_order as so ON sp.salesnumber = so.salesnumber
    			 LEFT JOIN invoice as inv ON inv.id = so.invoiceid
    			 LEFT JOIN user_profile as up ON up.uid = so.uid
    			 LEFT JOIN user as u ON u.uid = so.uid
    			 LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
    			 LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
  				 LEFT JOIN product as po ON po.id = sp.prod_id
    			 LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
    			 WHERE so.total >0 AND so.status IN('102','103','201','202','301','302') AND so.back_status IN('201','202') $sql $sql2 ORDER BY so.created ASC");
    	return array_merge($onlineorder,$inqorder);
	}
	/*
	 * 销售业绩报告，并导出操作记录
	*/
	public function sellRepo2($sql,$type,$sql2=''){
	
		if($type!='normal'){
			$onlineorder = $this->_salesproductModel->Query("SELECT sp.*,inv.name as invname,inv.type as invtype,
					so.pay_time,so.paytype,so.currency,u.uname,u.email as uemail,up.companyname,st.*,
					dp.department,sapo.auart,sapo.order_no,sapo.kunnr,sapo.cname,sapo.bstnk
					FROM sales_product as sp
					LEFT JOIN sales_order as so ON sp.salesnumber = so.salesnumber
					LEFT JOIN invoice as inv ON inv.id = so.invoiceid
					LEFT JOIN user_profile as up ON up.uid = so.uid
					LEFT JOIN user as u ON u.uid = so.uid
					LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
					LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
					LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
					WHERE so.total >0 AND so.status IN('201','202','301','302') AND so.back_status IN('201','202') $sql ORDER BY so.created ASC");
		    foreach($onlineorder as $k=>$v){
		    	$onlineorder[$k]['log1']=$this->_salesproductModel->Query("SELECT 
		    			log1.created as log1created,st1.lastname as st1lname,st1.firstname as st1fname
		    			FROM admin_log as log1
		    			LEFT JOIN admin_staff as st1 ON st1.staff_id = log1.staffid
		    			WHERE log1.temp2='".$v['salesnumber']."' AND log1.action='release'");
		    }
		    foreach($onlineorder as $k=>$v){
		    	$onlineorder[$k]['log2']=$this->_salesproductModel->Query("SELECT
		    			log2.created as log2created,st2.lastname as st2lname,st2.firstname as st2fname
		    			FROM admin_log as log2
		    			LEFT JOIN admin_staff as st2 ON st2.staff_id = log2.staffid
		    			WHERE log2.temp2='".$v['salesnumber']."' AND log2.action='delivery'");
		    }
		}else $onlineorder = array();
		$inqorder = $this->_salesproductModel->Query("SELECT sp.*,inv.name as invname,inv.type as invtype,
				so.pay_time,so.pay_2_time,so.paytype,so.currency,u.uname,u.email as uemail,up.companyname,st.*,
				dp.department,sapo.auart,sapo.order_no,sapo.kunnr,sapo.cname,sapo.bstnk
				FROM sales_product as sp
				LEFT JOIN inq_sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
				WHERE so.total >0 AND so.back_order=0 AND so.status IN('102','103','201','202','301','302') AND so.back_status IN('201','202') $sql $sql2 ORDER BY so.created ASC");
	       foreach($inqorder as $k=>$v){
		    	$inqorder[$k]['log1']=$this->_salesproductModel->Query("SELECT 
		    			log1.created as log1created,st1.lastname as st1lname,st1.firstname as st1fname
		    			FROM admin_log as log1
		    			LEFT JOIN admin_staff as st1 ON st1.staff_id = log1.staffid
		    			WHERE log1.temp2='".$v['salesnumber']."' AND log1.action='release'");
		    }
		    foreach($inqorder as $k=>$v){
		    	$inqorder[$k]['log2']=$this->_salesproductModel->Query("SELECT
		    			log2.created as log2created,st2.lastname as st2lname,st2.firstname as st2fname
		    			FROM admin_log as log2
		    			LEFT JOIN admin_staff as st2 ON st2.staff_id = log2.staffid
		    			WHERE log2.temp2='".$v['salesnumber']."' AND log2.action='delivery'");
		    }
		return array_merge($onlineorder,$inqorder);
	}
	/**
	 * 统计RMB总和
	 */
	public function orderSum($sql,$usdtormb,$usdtohkd){
		$hkdtormb = (1/$usdtohkd)*$usdtormb;
		$onlineorder = $this->_salesproductModel->Query("SELECT so.salesnumber,so.currency,so.total,up.staffid
				FROM sales_order as so
				LEFT JOIN user_profile as up ON up.uid = so.uid
				WHERE so.status IN('201','202','301','302') AND so.back_status IN('201','202') $sql");
		$inqorder = $this->_salesproductModel->Query("SELECT so.salesnumber,so.sqs_code,so.back_order,so.currency,so.total,up.staffid
				FROM inq_sales_order as so 
				LEFT JOIN user_profile as up ON up.uid = so.uid
				WHERE so.status IN('102','103','201','202','301','302') AND so.back_status IN('201','202') $sql");
		$orderarr = array_merge($onlineorder,$inqorder);
		//汇率
		$re =array('sqstotal'=>0,'linetotal'=>0,'unlinetotal'=>0,'total'=>0);
		foreach($orderarr as $v){
			//转货币
			if($v['currency']=='USD') $v['total'] = $usdtormb*$v['total'];
			if($v['currency']=='HKD') $v['total'] = $hkdtormb*$v['total'];
			//万元为单位
			$v['total'] = $v['total']*0.0001;
			//alina.shang 业务
			if($v['staffid']=='alina.shang' || $v['sqs_code']==1) $re['sqstotal'] += $v['total'];
			if($v['staffid']!='alina.shang' && $v['back_order']==0 && $v['sqs_code']!=1) $re['linetotal'] += $v['total'];
			if($v['staffid']!='alina.shang' && $v['back_order']==1 && $v['sqs_code']!=1) $re['unlinetotal'] += $v['total'];
			$re['total'] += $v['total'];
		}
		return $re;
	}
	/**
	 * 季度统计
	 */
	public function quarterlyCount($year){
		//统计 季度
		$q = $this->_fun->getQuarterly($year);
		$usdtormb  = $this->_fun->getUSDToRMB();
		$usdtohkd  = $this->_fun->getUSDToHKD();
		
		//订单 1小时
		$frontendOptions = array('lifeTime' => 3600,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'quarterly_count_'.$year;
		$quarterlytotal = array();
		if(!$quarterlytotal = $cache->load($cache_key)) {
			foreach($q as $k=>$v){
				$sql = " AND so.created BETWEEN '".$v['s']."' AND '".$v['e']."'";
				$quarterlytotal[$k] = $this->orderSum($sql,$usdtormb,$usdtohkd);
				$quarterlytotal['sqsall']   += $quarterlytotal[$k]['sqstotal'];
				$quarterlytotal['lineall']   += $quarterlytotal[$k]['linetotal'];
				$quarterlytotal['unlineall']   += $quarterlytotal[$k]['unlinetotal'];
				$quarterlytotal['totalall']  += $quarterlytotal[$k]['total'];
			}
			$cache->save($quarterlytotal,$cache_key);
		}
		return $quarterlytotal;
	}
	/**
	 * 获取季度计划
	 */
	public function quarterlyPlan($year){
		$configsModel = new Icwebadmin_Model_DbTable_Model('dictionary');
		$quarterly = $configsModel->getAllByWhere("type='quarterly' AND year = '{$year}'");
		$quarterly_tmp = array();
		foreach($quarterly as $v){
			if($v['tmp1']=='new'){
				$quarterly_tmp['new'][$v['tmp2']]=$v['value'];
			}
			if($v['tmp1']=='line'){
				$quarterly_tmp['line'][$v['tmp2']]=$v['value'];
			}
			if($v['tmp1']=='unline'){
				$quarterly_tmp['unline'][$v['tmp2']]=$v['value'];
			}
			if($v['tmp1']=='total'){
				$quarterly_tmp['total'][$v['tmp2']]=$v['value'];
			}
		}
		$quarterly_tmp['new']['total'] = $quarterly_tmp['new']?array_sum($quarterly_tmp['new']):0;
		$quarterly_tmp['line']['total'] = $quarterly_tmp['line']?array_sum($quarterly_tmp['line']):0;
		$quarterly_tmp['unline']['total'] = $quarterly_tmp['unline']?array_sum($quarterly_tmp['unline']):0;
		$quarterly_tmp['total']['total'] = $quarterly_tmp['total']?array_sum($quarterly_tmp['total']):0;
		return $quarterly_tmp;
	}
	/*
	 * 订单情况分析
	 */
	public function orderRepo($sql,$back_order){
		$onlineorder = array();
		$bsql = "";
		if($back_order==1) $bsql = "AND so.back_order=0";
		elseif($back_order==2) $bsql = "AND so.back_order=1";
		if($back_order!=2){
		$onlineorder = $this->_salesproductModel->Query("SELECT sp.prod_id,sp.part_no,sp.buynum,sp.buyprice,sp.created,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,p.manufacturer,
				so.salesnumber,so.currency,so.paytype,so.status as sostatus,b.name as brandname,
				inv.name as invname,inv.type as invtype,sapo.order_no,
				st.staff_id,st.lastname,st.firstname,dp.department
				FROM sales_product as sp
				LEFT JOIN sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.prod_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
				WHERE so.total >0 AND so.status !=401 AND so.back_status != 102 $sql ORDER BY so.created ASC");
		}
		$inqorder = $this->_salesproductModel->Query("SELECT sp.prod_id,sp.part_no,sp.buynum,sp.buyprice,sp.created,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,
				so.salesnumber,so.back_order,so.currency,so.paytype,so.status as sostatus,b.name as brandname,
				inv.name as invname,inv.type as invtype,sapo.order_no,p.manufacturer,
				st.staff_id,st.lastname,st.firstname,dp.department
				FROM sales_product as sp
				LEFT JOIN inq_sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.prod_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				LEFT JOIN sap_order as sapo ON so.salesnumber = sapo.salesnumber
				WHERE so.total >0 AND so.status !=401 AND so.back_status != 102 $bsql $sql  ORDER BY so.created ASC");
		return array_merge($onlineorder,$inqorder);
	}
	/**
	 * 询价情况分析 
	 */
	public function inqRepo($sql){
		$data = $this->_salesproductModel->Query("SELECT inq.id,inq.currency,inq.created,inq.modified,inqd.part_no,inqd.number,
				up.property,up.companyname,app.name as appname,up.personaldesc,
				b.name as brandname,pc.name as pcname,p.mpq,p.moq,
				sop.buynum,sop.buyprice,
				st.lastname,st.firstname
				FROM inquiry_detailed as inqd
				LEFT JOIN inquiry as inq ON inq.id = inqd.inq_id
				LEFT JOIN product as p ON p.id = inqd.part_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN prod_category as pc ON pc.id = p.part_level1
				LEFT JOIN sales_product as sop ON sop.inqdet_id = inqd.id
				LEFT JOIN user_profile as up ON up.uid = inq.uid
				LEFT JOIN app_category as app ON app.id = up.industry
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				WHERE inqd.id!='' AND inq.status!=0 AND up.companyname IS NOT NULL $sql ORDER BY inqd.created ASC");
		$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
		$inqid = array();
		foreach($data as $d){
			$inqid[$d['id']] = $d['id'];
		}
		foreach($inqid as $qid){
			$sql = "SELECT `created`,`description` FROM `admin_log`
			WHERE `controller` = 'QuoInq'
			AND `action` = 'reason'
			AND `temp2`= '{$qid}'
			AND `temp1` IS NULL";
			$data['log'][$qid] = $this->_adminlogService->getLogBySql($sql);
		}
		return $data;
	}
	/*
	 * 中电品牌订单情况分析
	*/
	public function ordercorpRepo($sql){
		$onlineorder = $this->_salesproductModel->Query("SELECT sp.part_no,sp.prod_id,sp.buynum,sp.buyprice,sp.created,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,
				so.salesnumber,so.currency,so.paytype,so.status as sostatus,b.name as brandname,
				inv.name as invname,inv.type as invtype,
				st.lastname,st.firstname,dp.department	
				FROM sales_product as sp
				LEFT JOIN sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.prod_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				WHERE p.manufacturer =42 AND so.status !=401 AND so.back_status != 102 $sql ORDER BY so.created ASC");
		//coup.code,coup.type as ctype,coup.buy_number,coup.money_rmb,coup.money_usd,coup.remark
		foreach($onlineorder as $k=>$v){
			$onlineorder[$k]['coupon'] = $this->_salesproductModel->QueryRow("SELECT coup.code,coup.type as ctype,coup.buy_number,coup.money_rmb,coup.money_usd,coup.remark
					FROM coupon as coup 
					WHERE coup.salesnumber = '".$v['salesnumber']."' AND coup.part_id = '".$v['prod_id']."'");
		}
		$inqorder = $this->_salesproductModel->Query("SELECT sp.part_no,sp.buynum,sp.buyprice,sp.created,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,
				so.salesnumber,so.currency,so.paytype,so.status as sostatus,b.name as brandname,
				inv.name as invname,inv.type as invtype,
				st.lastname,st.firstname,dp.department
				FROM sales_product as sp
				LEFT JOIN inq_sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.prod_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				WHERE p.manufacturer =42 AND so.back_order=0 AND so.status !=401 AND so.back_status != 102 $sql  ORDER BY so.created ASC");
		return array_merge($onlineorder,$inqorder);
	}
	/*
	 * 中电品牌订单情况分析
	*/
	public function ordercoreRepo($sql){
		$onlineorder = $this->_salesproductModel->Query("SELECT sp.part_no,sp.prod_id,sp.buynum,sp.buyprice,sp.created,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,
				so.salesnumber,so.currency,so.paytype,so.status as sostatus,b.name as brandname,
				inv.name as invname,inv.type as invtype,
				st.lastname,st.firstname,dp.department
				FROM sales_product as sp
				LEFT JOIN sales_order as so ON sp.salesnumber = so.salesnumber
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.prod_id
				LEFT JOIN brand as b ON b.id = p.manufacturer
				LEFT JOIN invoice as inv ON inv.id = so.invoiceid
				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id
				WHERE so.coupon_code!='' AND so.status !=401 AND so.back_status != 102 $sql ORDER BY so.created ASC");
		//coup.code,coup.type as ctype,coup.buy_number,coup.money_rmb,coup.money_usd,coup.remark
		foreach($onlineorder as $k=>$v){
			$onlineorder[$k]['coupon'] = $this->_salesproductModel->QueryRow("SELECT coup.code,coup.type as ctype,coup.buy_number,coup.money_rmb,coup.money_usd,coup.remark
					FROM coupon as coup
					WHERE coup.salesnumber = '".$v['salesnumber']."' AND coup.part_id = '".$v['prod_id']."'");
		}
		return $onlineorder;
	}
	/**
	 * 销售统计
	 */
	public function countByStaff($sql,$back_order){
		$arr=array();
		//获取品牌
		$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
		$brand = $this->_brandMod->getAllByWhere(array("type=1","status=1"));
		$brandarr = array();
		foreach($brand as $bv){
			$brandarr[] = $bv['id'];
		}
		$array = $this->orderRepo($sql,$back_order);
		
		foreach($array as $v){
			  $dlbrand = $nobrand = 0;
			  if(in_array($v['manufacturer'],$brandarr)){
			  	 $dlbrand = $v['buynum']*$v['buyprice'];
			  }else{
			  	 $nobrand = $v['buynum']*$v['buyprice'];
			  }
			   $arr[$v['staff_id']][] = array($v['currency'],($v['buynum']*$v['buyprice']),$dlbrand,$nobrand,$v['salesnumber']);
		}
		return $arr;
	}
	/**
	 * 订单走势
	 */
	public function orderstrend($sql,$back_order,$sdate,$edate,$type,$partarray){
		$re = array();
		$array = $this->orderRepo($sql,$back_order);
		if($edate>=$sdate){
			$tmp = $this->count_days($sdate,$edate)+1;
			$dtime = 60*60*24;
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$weekarr[date('W', $ftime)][] = date('Y-m-d',$ftime);
			}
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$montharr[date('m', $ftime)][] = date('Y-m-d',$ftime);
			}
			for($i=0;$i<$tmp;$i++){
				$num = $rmb = $usd = $tom_rmb = $tom_usd = array('BMP'=>0,'B&T'=>0);
				$ftime = $sdate+($i*$dtime);
				$ltime = $sdate+(($i+1)*$dtime);
			
				$or_tmp = array();
				foreach($array as $oarr){
					if($oarr['created']>=$ftime && $oarr['created']<$ltime){
						if(!isset($or_tmp[$oarr['department']]) || !in_array($oarr['salesnumber'],$or_tmp[$oarr['department']]))
						{
						   $num[$oarr['department']]++;
						   $or_tmp[$oarr['department']][] = $oarr['salesnumber'];
						}
						if($oarr['currency']=='RMB') {
							$rmb[$oarr['department']] += $oarr['buynum']*$oarr['buyprice'];
							if(in_array($oarr['prod_id'],$partarray)) $tom_rmb[$oarr['department']] += $oarr['buynum']*$oarr['buyprice'];
						}elseif($oarr['currency']=='USD')  {
							$usd[$oarr['department']] += $oarr['buynum']*$oarr['buyprice'];
							if(in_array($oarr['prod_id'],$partarray)) $tom_usd[$oarr['department']] += $oarr['buynum']*$oarr['buyprice'];
						}
					}
				}
			   if($type=='daywork'){
				  if($rmb['BMP'] && $rmb['B&T']){
				  	$re[date('Y-m-d',$ftime)]['num'] = $num;
				  	$re[date('Y-m-d',$ftime)]['rmb'] = $rmb;
				    $re[date('Y-m-d',$ftime)]['usd'] = $usd;
				    $re[date('Y-m-d',$ftime)]['tom_rmb'] = $tom_rmb;
				    $re[date('Y-m-d',$ftime)]['tom_usd'] = $tom_usd;
				  }
				}elseif($type=='day'){
				  $re[date('Y-m-d',$ftime)]['num'] = $num;
				  $re[date('Y-m-d',$ftime)]['rmb'] = $rmb;
				  $re[date('Y-m-d',$ftime)]['usd'] = $usd;
				  $re[date('Y-m-d',$ftime)]['tom_rmb'] = $tom_rmb;
				  $re[date('Y-m-d',$ftime)]['tom_usd'] = $tom_usd;
				}elseif($type=='week'){
				  $wn = date('W', $ftime);
				  $num_bmp_tmp[$wn] += $num['BMP'];$num_bnp_tmp[$wn] += $num['B&T'];
				  $rmb_bmp_tmp[$wn] += $rmb['BMP'];$rmb_bnp_tmp[$wn] += $rmb['B&T'];
				  $usd_bmp_tmp[$wn] += $usd['BMP'];$usd_bnp_tmp[$wn] += $usd['B&T'];
				  $tom_rmb_bmp_tmp[$wn] += $tom_rmb['BMP'];$tom_rmb_bnp_tmp[$wn] += $tom_rmb['B&T'];
				  $tom_usd_bmp_tmp[$wn] += $tom_usd['BMP'];$tom_usd_bnp_tmp[$wn] += $tom_usd['B&T'];
				  $key = date('W', $ftime).' Week'.'('.($weekarr[date('W', $ftime)][0].'至'.$weekarr[date('W', $ftime)][count($weekarr[date('W', $ftime)])-1]).')';
				  $re[$key]['num'] = array('BMP'=>$num_bmp_tmp[$wn],'B&T'=>$num_bnp_tmp[$wn]);
				  $re[$key]['rmb'] = array('BMP'=>$rmb_bmp_tmp[$wn],'B&T'=>$rmb_bnp_tmp[$wn]);
				  $re[$key]['usd'] = array('BMP'=>$usd_bmp_tmp[$wn],'B&T'=>$usd_bnp_tmp[$wn]);
				  $re[$key]['tom_rmb'] = array('BMP'=>$tom_rmb_bmp_tmp[$wn],'B&T'=>$tom_rmb_bnp_tmp[$wn]);
				  $re[$key]['tom_usd'] = array('BMP'=>$tom_usd_bmp_tmp[$wn],'B&T'=>$tom_usd_bnp_tmp[$wn]);
				}elseif($type=='month'){
					$mn = date('m', $ftime);
					$num_bmp_tmp[$mn] += $num['BMP'];$num_bnp_tmp[$mn] += $num['B&T'];
					$rmb_bmp_tmp[$mn] += $rmb['BMP'];$rmb_bnp_tmp[$mn] += $rmb['B&T'];
					$usd_bmp_tmp[$mn] += $usd['BMP'];$usd_bnp_tmp[$mn] += $usd['B&T'];
					$tom_rmb_bmp_tmp[$mn] += $tom_rmb['BMP'];$tom_rmb_bnp_tmp[$mn] += $tom_rmb['B&T'];
					$tom_usd_bmp_tmp[$mn] += $tom_usd['BMP'];$tom_usd_bnp_tmp[$mn] += $tom_usd['B&T'];
					$key = date('m', $ftime).'月'.'('.($montharr[date('m', $ftime)][0].'至'.$montharr[date('m', $ftime)][count($montharr[date('m', $ftime)])-1]).')';
					$re[$key]['num'] = array('BMP'=>$num_bmp_tmp[$mn],'B&T'=>$num_bnp_tmp[$mn]);
					$re[$key]['rmb'] = array('BMP'=>$rmb_bmp_tmp[$mn],'B&T'=>$rmb_bnp_tmp[$mn]);
					$re[$key]['usd'] = array('BMP'=>$usd_bmp_tmp[$mn],'B&T'=>$usd_bnp_tmp[$mn]);
					$re[$key]['tom_rmb'] = array('BMP'=>$tom_rmb_bmp_tmp[$mn],'B&T'=>$tom_rmb_bnp_tmp[$mn]);
					$re[$key]['tom_usd'] = array('BMP'=>$tom_usd_bmp_tmp[$mn],'B&T'=>$tom_usd_bnp_tmp[$mn]);
				}
			}
		}
		return $re;
	}
	//获取相差天数
	public function count_days($a,$b){
		$a_dt=getdate($a);
		$b_dt=getdate($b);
		$a_new=mktime(12,0,0,$a_dt['mon'],$a_dt['mday'],$a_dt['year']);
		$b_new=mktime(12,0,0,$b_dt['mon'],$b_dt['mday'],$b_dt['year']);
		return round(abs($a_new-$b_new)/86400);
	}
	/*
	 * 订单情况分析
	*/
	public function samplesRepo($sql){
		$onlineorder = array();

		$inqorder = $this->_salesproductModel->Query("SELECT sp.*,
				u.uname,u.email as uemail,up.truename,up.companyname,up.tel,up.mobile,up.fax,
				so.salesnumber,so.projectname,so.remark,
				st.staff_id,st.lastname,st.firstname,dp.department
				FROM samples_detailed as sp
				LEFT JOIN samples_apply as so ON sp.applyid = so.id
			
				LEFT JOIN user_profile as up ON up.uid = so.uid
				LEFT JOIN user as u ON u.uid = so.uid
				LEFT JOIN product as p ON p.id = sp.part_id

				LEFT JOIN admin_staff as st ON st.staff_id = up.staffid
				LEFT JOIN admin_department as dp ON dp.department_id = st.department_id

				WHERE  so.status IN ('300','301')  $sql  ORDER BY so.created ASC");
		return $inqorder;
	}
	/**
	 * 订单金额走势
	 */
	public function orderTrend($sql,$usdrmb,$sdate,$edate,$type){
		$online = $outline = array();
		$array = $this->orderMoney($sql);
		
	    if($edate>=$sdate){
			$rs = new Icwebadmin_Service_RepoService();
			$tmp = $rs->count_days($sdate,$edate)+1;
			
			$dtime = 60*60*24;
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$weekarr[date('W', $ftime)][] = date('y/m/d',$ftime);
			}
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$montharr[date('m', $ftime)][] = date('y/m/d',$ftime);
			}
			echo $tmp;
			for($i=0;$i<$tmp;$i++){
				$ftime = $sdate+($i*$dtime);
				$ltime = $sdate+(($i+1)*$dtime);
				if($type=='day'){
				   foreach($array as $oarr){
					  if($oarr['created']<=$ltime){
					     if(isset($oarr['back_order']) && $oarr['back_order']==1){
				            if($oarr['currency']=='RMB'){
				            	$outline[date('Y-m-d',$ftime)]['RMB'] += $oarr['total'];
				            	$outline[date('Y-m-d',$ftime)]['ALL'] += $oarr['total'];
				            }elseif($oarr['currency']=='USD'){
				            	$outline[date('Y-m-d',$ftime)]['USD'] += $oarr['total'];
				            	$outline[date('Y-m-d',$ftime)]['ALL'] += $oarr['total']*$usdrmb;
				            }
			             }else{
			                if($oarr['currency']=='RMB'){
				            	$online[date('Y-m-d',$ftime)]['RMB'] += $oarr['total'];
				            	$online[date('Y-m-d',$ftime)]['ALL'] += $oarr['total'];
				            }elseif($oarr['currency']=='USD'){
				            	$online[date('Y-m-d',$ftime)]['USD'] += $oarr['total'];
				            	$online[date('Y-m-d',$ftime)]['ALL'] += $oarr['total']*$usdrmb;
				            }
			             }
					  }
				   }
				}elseif($type=='week'){
					$key = date('y', $ltime).'年'.date('W', $ltime).' 周'.'('.($weekarr[date('W', $ltime)][0].'-'.$weekarr[date('W', $ltime)][count($weekarr[date('W', $ltime)])-1]).')';
					foreach($array as $oarr){
						if($oarr['created']<=$ltime){
							if(isset($oarr['back_order']) && $oarr['back_order']==1){
								if($oarr['currency']=='RMB'){
									$outline[$key]['RMB'] += $oarr['total'];
									$outline[$key]['ALL'] += $oarr['total'];
								}elseif($oarr['currency']=='USD'){
									$outline[$key]['USD'] += $oarr['total'];
									$outline[$key]['ALL'] += $oarr['total']*$usdrmb;
								}
							}else{
								if($oarr['currency']=='RMB'){
									$online[$key]['RMB'] += $oarr['total'];
									$online[$key]['ALL'] += $oarr['total'];
								}elseif($oarr['currency']=='USD'){
									$online[$key]['USD'] += $oarr['total'];
									$online[$key]['ALL'] += $oarr['total']*$usdrmb;
								}
							}
						}
					}
				}elseif($type=='month'){
					$key = date('y', $ltime).'年'.date('m', $ltime).'月'.'('.($montharr[date('m', $ltime)][0].'-'.$montharr[date('m', $ltime)][count($montharr[date('m', $ltime)])-1]).')';
				    foreach($array as $oarr){
						if($oarr['created']<=$ltime){
							if(isset($oarr['back_order']) && $oarr['back_order']==1){
								if($oarr['currency']=='RMB'){
									$outline[$key]['RMB'] += $oarr['total'];
									$outline[$key]['ALL'] += $oarr['total'];
								}elseif($oarr['currency']=='USD'){
									$outline[$key]['USD'] += $oarr['total'];
									$outline[$key]['ALL'] += $oarr['total']*$usdrmb;
								}
							}else{
								if($oarr['currency']=='RMB'){
									$online[$key]['RMB'] += $oarr['total'];
									$online[$key]['ALL'] += $oarr['total'];
								}elseif($oarr['currency']=='USD'){
									$online[$key]['USD'] += $oarr['total'];
									$online[$key]['ALL'] += $oarr['total']*$usdrmb;
								}
							}
						}
					}
				}
			}
		}
		return array('online'=>$online,'outline'=>$outline);
	}
	/**
	 * 获取订单金额
	 */
	public function orderMoney($sql){
		$onlineorder = $inqorder = $orderarr = array();
		$onlineorder = $this->_salesproductModel->Query("SELECT so.salesnumber,so.currency,so.total,so.created
				FROM sales_order as so
				WHERE so.status IN('201','202','301','302') AND so.back_status='202' $sql");
		$inqorder = $this->_salesproductModel->Query("SELECT so.salesnumber,so.back_order,so.currency,so.total,so.created
				FROM inq_sales_order as so
				WHERE so.status IN('102','103','201','202','301','302') AND so.back_status ='202' $sql");
		$orderarr = array_merge($onlineorder,$inqorder);
		
		return $orderarr;
	}
}