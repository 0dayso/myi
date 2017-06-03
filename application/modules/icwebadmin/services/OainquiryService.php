<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_OainquiryService
{
	private $_inqservice;
	private $_emailService;
	private $_adminlogService;
	public function __construct() {
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->fun = new MyFun();
		$this->_inqservice = new Icwebadmin_Service_InquiryService();
		$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
	    
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			//测试
			//oa用户
			$this->oauserwsdl = "http://myloy.ceacsz.com.cn/OAWEB/Sevices/CustomerService.svc?wsdl";
			//oa产品线
			$this->productlinewsdl = "http://myloy.ceacsz.com.cn/OAWEB/Sevices/ProductLineSVC.svc?wsdl";
			//oa询价 RFQ Budgetary
			$this->qutationwsdl = "http://myloy.ceacsz.com.cn/OAWEB/Sevices/QutationSVC.svc?wsdl";
			//oa询价 bpp
			$this->bppqutationwsdl = "http://myloy.ceacsz.com.cn/OAWEB/Sevices/BPPQutationService.svc?wsdl";
			//oa销售
			$this->employeewsdl = "http://myloy.ceacsz.com.cn/OAWEB/Sevices/EmployeeService.svc?wsdl";
			//sap产品
			$this->sapwsdl = "http://192.168.36.190/CeacszSAPWebService/websapdata.asmx?wsdl";
			//ATS 查询
			$this->atswsdl = "http://192.168.36.190/OAWEB/Sevices/ATSInventoryService.svc?wsdl";
			//SAP库存查询地址
			$this->dwwsdl        ="http://192.168.0.12/DwWebService/Service1.asmx?WSDL";

		} else {
			//正式
			//oa用户
			$this->oauserwsdl = "http://192.168.0.38/OAService/Sevices/CustomerService.svc?wsdl";
			//oa产品线
			$this->productlinewsdl = "http://192.168.0.38/OAService/Sevices/ProductLineSVC.svc?wsdl";
			//oa询价 RFQ Budgetary
			$this->qutationwsdl = "http://192.168.0.38/OAService/Sevices/QutationSVC.svc?wsdl";
			//oa询价 bpp
			$this->bppqutationwsdl = "http://192.168.0.38/OAService/Sevices/BPPQutationService.svc?wsdl";
			//oa销售
			$this->employeewsdl = "http://192.168.0.38/OAService/Sevices/EmployeeService.svc?wsdl";
			//sap产品
			$this->sapwsdl = "http://192.168.0.54/CeacszSAPWebService/websapdata.asmx?wsdl";
			//ATS 查询
			$this->atswsdl = "http://192.168.0.38/OAService/Sevices/ATSInventoryService.svc?wsdl";
			//SAP库存查询地址
			$this->dwwsdl        ="http://192.168.0.12/DwWebService/Service1.asmx?WSDL";
		}

	}
	/**
	 * 根据型号查库存
	 * @param unknown_type $LogName
	 * @param unknown_type $LogPwd
	 * @return Ambigous <>|boolean
	 */
	public function GetDwByPartNo($Mantr){
		try{
			$objSoapClient = new SoapClient($this->dwwsdl);
	        $param = array('Mantr'=>$Mantr);
            $out = $objSoapClient->__call('GetDataAtsByMatnr', array('parameters' => $param));
	        $atsAll = get_object_vars($out);
	        $atsAllResult = get_object_vars($atsAll['GetDataAtsByMatnrResult']);
	        $allResultXml = new SimpleXMLElement($atsAllResult['any']);
	        $instance = $allResultXml->NewDataSet->Table;
	        $instance = get_object_vars($instance);
	        return $instance;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 
	 */
	public function GetLoginID($LogName,$LogPwd='C@EA#C20#IC'){
		try{
			$objSoapClient = new SoapClient($this->oaloginwsdl);
			$CustomerInfo= array('LogName'=>$LogName,'LogPwd'=>$LogPwd);

			$out = $objSoapClient->__call('GetLoginID', array('parameters' => $CustomerInfo));
			$atsAll = get_object_vars($out);
			if(isset($atsAll['GetLoginIDResult'])){
			   return $atsAll['GetLoginIDResult'];
			}else return false;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 向OA提交查询
	 */
	public function FindByConditionResult($ClientCName){
		try{
		  $objSoapClient = new SoapClient($this->oauserwsdl);
		  $CustomerInfo= array('condition'=>"ClientCName='$ClientCName'");
		  $out = $objSoapClient->__call('FindByCondition', array('parameters' => $CustomerInfo));
		  $atsAll = get_object_vars($out);
		  $FindByConditionResult = get_object_vars($atsAll['FindByConditionResult']);
		  if(is_array($FindByConditionResult) && $FindByConditionResult['ClientListID']){
			 return $FindByConditionResult;
		  }else return false;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 向OA提交模糊查询
	 */
	public function Find($ClientCName){
		try{
			$objSoapClient = new SoapClient($this->oauserwsdl);
			$CustomerInfo= array('condition'=>"ClientCName LIKE '%{$ClientCName}%'");
			$out = $objSoapClient->__call('Find', array('parameters' => $CustomerInfo));
			$atsAll = get_object_vars($out);
			$FindResult = get_object_vars($atsAll['FindResult']);
			$rearray = array();
			if(is_array($FindResult['CustomerEntity'])){
				foreach($FindResult['CustomerEntity'] as $customerobj){
					$rearray[] = get_object_vars($customerobj);
				}
			}elseif(is_object($FindResult['CustomerEntity'])){
				$rearray[] = get_object_vars($FindResult['CustomerEntity']);
			}
			return $rearray;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 向OA提交客户申请
	 */
	public function SubmitClientListInfo($CustomerInfo,$ClientContactPersonInfo,$ClientBusinessCopeList){
		try{
			 $objSoapClient = new SoapClient($this->oauserwsdl);
			 $param       = array('CustomerInfo' => $CustomerInfo,
			 		'ClientContactPersonInfo'=>$ClientContactPersonInfo,
			 		'ClientBusinessCopeList'=>$ClientBusinessCopeList);
			 
		     $out = $objSoapClient->__call('SubmitClientListInfo', array('parameters' => $param));
		     return get_object_vars($out); 
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 检查part no是否在SAP里
	 */
	public function CheckPartInSAP($partno)
	{
		try{
			$arr = array();
			$objSoapClient = new SoapClient($this->sapwsdl);
			$out = $objSoapClient->__call('GetProduct', array('parameters' => array('ProductCode'=>$partno)));
			$atsAll = get_object_vars($out);
			
			$atsAll = $this->fun->xml_to_array($atsAll['GetProductResult']);
			if(!is_array($atsAll) || !$atsAll['NewDataSet'] || !$atsAll['NewDataSet']['ZMM01Table']) return false;
			else $ZMM01Table = $atsAll['NewDataSet']['ZMM01Table'];
			if(is_array($ZMM01Table[0])){
				foreach($ZMM01Table as $partarr){
					if($partarr['Mfrpn']==$partno) return true;
				}
				return false;
			}else{
				if($ZMM01Table['Mfrpn']==$partno) return true;
				else return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 查询part no ATS
	 */
	public function QueryATSInventoryData($partno)
	{
		try{
			$arr = array();
			$objSoapClient = new SoapClient($this->atswsdl);
			
			$CustomerInfo= array('ProductCode'=>$partno);
			$out = $objSoapClient->__call('QueryATSInventoryData', array('parameters' => $CustomerInfo));
			$atsAll = get_object_vars($out);
			$atsAll = $this->fun->xml_to_array($atsAll['QueryATSInventoryDataResult']);
			if(!is_array($atsAll) || !$atsAll['Root'] || !$atsAll['Root']['MaterialList']) return false;
			else $ZMM01Table = $atsAll['Root']['MaterialList'];
			if(is_array($ZMM01Table[0])){
				foreach($ZMM01Table as $rearr){
					$arr[] = $rearr;
				}
			}else{
				$arr[] = $ZMM01Table;
			}
			return $arr;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 获取OA产品线
	 */
	public function FindProductLine()
	{
		try{
			$arr = array();
		    $objSoapClient = new SoapClient($this->productlinewsdl);
		    $parameters = array('condition'=>"TypeID=1");
		    $out = $objSoapClient->__call('Find',array('parameters' => $parameters));
		    $atsAll = get_object_vars($out);
		    $atsAll = get_object_vars($atsAll['FindResult']);
		    foreach($atsAll['ProductLineEntity'] as $v){
		    	$arr[] = get_object_vars($v);
		    }
		    return $arr;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 获取OA销售
	 */
	public function FindEmployee()
	{
		try{
			$arr = array();
			$objSoapClient = new SoapClient($this->employeewsdl);
			$CustomerInfo = array('RoleID'=>3);
	        $out = $objSoapClient->__call('FindEmpsByRoleID', array('parameters' => $CustomerInfo));
			$atsAll = get_object_vars($out);
			$atsAll = get_object_vars($atsAll['FindEmpsByRoleIDResult']);
			foreach($atsAll['EmployeeEntity'] as $v){
				$arr[] = get_object_vars($v);
			}
			return $arr;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 向OA提交询价 RFQ Budgetary
	 */
	public function SubmitProductRFQInfo($ProductRFQInfo,$ProductRFQDetailInfoList){
		try{
			$objSoapClient = new SoapClient($this->qutationwsdl);
	        $param = array('ProductRFQInfo' => $ProductRFQInfo,'ProductRFQDetailInfoList'=>$ProductRFQDetailInfoList);
	        $out = $objSoapClient->__call('SubmitProductRFQInfo', array('parameters' => $param));
	        $atsAll = get_object_vars($out);
	        $atsAll = get_object_vars($atsAll['SubmitProductRFQInfoResult']);
		    $re = array();
	        if($atsAll['ProductRFQDetailEntity']){
	           if(is_array($atsAll['ProductRFQDetailEntity'])){
			       foreach($atsAll['ProductRFQDetailEntity'] as $v){
			           $re[] = is_array($v)?$v:get_object_vars($v);
		           }
		       }else{
		           $re[] = get_object_vars($atsAll['ProductRFQDetailEntity']);
		       }
	        }
	        return $re;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * 向OA提交询价BPPRFQ
	 */
	public function SubmitProductBPPRFQInfo($ProductRFQInfo,$ProductRFQDetailInfoList){
		try{
			$objSoapClient = new SoapClient($this->bppqutationwsdl);
			$param = array('ProductBPPRFQInfo' => $ProductRFQInfo,'ProductBPPRFQDetailInfoList'=>$ProductRFQDetailInfoList);
			$out = $objSoapClient->__call('SubmitProductBPPRFQInfo', array('parameters' => $param));
			$atsAll = get_object_vars($out);
			$atsAll = get_object_vars($atsAll['SubmitProductBPPRFQInfoResult']);
			$re = array();
			if($atsAll['ProductBPPRFQDetailEntity']){
				if(is_array($atsAll['ProductBPPRFQDetailEntity'])){
					foreach($atsAll['ProductBPPRFQDetailEntity'] as $v){
						$re[] = is_array($v)?$v:get_object_vars($v);
					}
				}else{
					$re[] = get_object_vars($atsAll['ProductBPPRFQDetailEntity']);
				}
			}
			return $re;
		} catch (Exception $e) {
			return false;
		}
	}
	/**
	 * OA反馈报价
	 */
	public function feedbackInquiry($ProductRFQDetail) {
		if(!$ProductRFQDetail->RFQId || !$ProductRFQDetail->RFQDetailID || !$ProductRFQDetail->RFQType) $restr = '-100';
		else{
			$oa_rfq = $ProductRFQDetail->RFQId;
			$inqinfo = $this->_inqservice->getInqByOarfq($oa_rfq,$ProductRFQDetail->RFQType);
			if($inqinfo){
			//更新主表
			$re = $this->_inqservice->updateInquiry($inqinfo['id'], array('oa_status'=>102,'oa_rfqnumber'=>$ProductRFQDetail->RFQNumber));
			$this->_inqdetailedModer = new Icwebadmin_Model_DbTable_InquiryDetailed();
			//询价类型，RFQType
			//如果美元报价，港币询价，需要转换
			if($inqinfo['delivery']=='HK' && $inqinfo['currency']=='HKD'){
				//港币兑美元汇率
				$rateModel = new Default_Model_DbTable_Rate();
				$arr = $rateModel->getRowByWhere("currency='USD' AND to_currency='HKD' AND status='1'");
				if(!$arr['rate_value']) return '-110';
				$rate_value = $arr['rate_value'];
			}else $rate_value=1;
			//oa_rdtype用来区分不同的询价类型
			$result_price = $ProductRFQDetail->ResultPrice*$rate_value;
			//'pmpq'=>$ProductRFQDetail->PMPQ,
			$updata = array('oa_rfqnumber'=>$ProductRFQDetail->RFQNumber,
					'oa_result_price'=>$result_price,
					'oa_expiration_time'=>strtotime($ProductRFQDetail->ExpirationTime),
					'oa_inqd_remark'=>$ProductRFQDetail->ResultRemark,
					'oa_pmsc_name' =>$ProductRFQDetail->PMSCName,
					'oa_status'=>$ProductRFQDetail->Status);
			$re_det = $this->_inqdetailedModer->update($updata,"oa_rfq_id = '".$ProductRFQDetail->RFQId."' AND oa_inqd_id='".$ProductRFQDetail->RFQDetailID."'");
			$restr = $inqinfo['id'];
			if($restr>0){
			    $inqdModel = new Default_Model_DbTable_InquiryDetailed();
			    $inqd = $inqdModel->getBySql("SELECT inqd.*,b.name as bname  
			       FROM inquiry_detailed as inqd
		           LEFT JOIN product as p ON inqd.part_id = p.id
			       LEFT JOIN brand as b ON b.id = p.manufacturer
		           WHERE inqd.inq_id='".$inqinfo['id']."'");
			    $mailalert = true;
				foreach($inqd as $inqdarr){
					if($inqdarr['oa_inqd_id']){
					  if(!$inqdarr['oa_result_price'] && !$inqdarr['oa_pmsc_name']){
						 $mailalert = false;
						 break;
					  }
					}
				}
				//全部报价后才发邮件
				if($mailalert) $emailre = $this->sendEmail($inqinfo,$inqd,$ProductRFQDetail->RFQNumber);
				if($emailre){
					$this->_adminlogService->addLog(array('log_id'=>'M',
							'temp2'=>$inqinfo['id'],
							'temp4'=>'PMSC通过报价邮件通知销售成功'));
				}
			}
			}else{
				$restr = '-200';
			}
		}
		//日志
		$des = 'RFQDetailID:'.$ProductRFQDetail->RFQDetailID.', oa_rdtype:'.$ProductRFQDetail->RFQType.', result_price:'.$result_price.', result_remark:'.$ProductRFQDetail->ResultRemark.'， name:'.$ProductRFQDetail->PMSCName;
		$this->_adminlogService->addLog(array('log_id'=>'E',
				'temp1'=>($restr>0?'':400),
				'temp2'=>$inqinfo['id'],
				'temp4'=>'PMSC通过OA反馈报价',
				'description'=>$des.';'.$restr));
		return array("GetResult"=>$restr);
	}
	/**
	 * OA报价
	 */
public function sendEmail($inqinfo,$inqdArray,$rfqnumber)
	{
		$deliveryArr = array('HK'=>'香港','SZ'=>'国内');
		$oardtypeArr = array('1'=>'RFQ','2'=>'Budgetary','5'=>'BPPRFQ');
		$title = 'PMSC通过OA报价成功 - 询价号:'.$inqinfo['inq_number'];
		//负责销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($inqinfo['uid']);
		$mess .='</tbody>
        </table> <!--hi-->
    <tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$sellinfo['lastname'].$sellinfo['firstname'].',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">PMSC已经通过OA报价成功，请登录 <a href="http://www.iceasy.com/icwebadmin/QuoInq" target="_blank" style="color:#fd2323;font-family:\'微软雅黑\';font-size:13px;"><b>盛芯电子后台</b></a> 查看详情。</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';line-height:20px;">为了确保您的客户能以所报的优惠价格购买，请引导客户在报价有效期内下单。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <!--内容-->
    <!--订单信息-->
    <tr valign="top">
      <td valign="bottom"  align="center" height="40"><div style="margin:0; padding:0; font-size:16px; color:#333333; font-weight:bold;font-family:\'微软雅黑\'; ">PMSC报价单</div></td>
    </tr>
    <!--订单详情-->
    <tr valign="top">
      <td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:collapse;" >
          <tr>
            <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
                <tr>
                  <td valign="middle" colspan="2" align="left" height="40"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;报价详情&nbsp;&nbsp;&nbsp;</span> </td>
                </tr>
                <tr>
                  <td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
                  <td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6; border-collapse:collapse;">
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;询价方：</td>
                        <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$inqinfo['companyname'].'</strong><span style="color:#ff6600">（贸易商）</span></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6 ">&nbsp;&nbsp;盛芯电子询价编号#：</td>
                        <td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#ff6600;font-family:\'微软雅黑\';font-size:14px;">&nbsp;&nbsp;'.$inqinfo['inq_number'].'</strong></td>
                      </tr>
                      <tr>
                        <td width="120" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;OA BQ#：</td>
                        <td width="300" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#03b000;font-family:\'微软雅黑\';font-size:14px;">&nbsp;&nbsp;'.$rfqnumber.'</strong></td>
                        <td width="100" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;询价类型：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$oardtypeArr[$inqinfo['oa_rdtype']].'</strong></td>
                      </tr>
                      <tr  bgcolor="#ffffff">
                        <td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;交货地：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$deliveryArr[$inqinfo['delivery']].'</strong></td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;交易货币：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';"><strong style="color:#000000;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$inqinfo['currency'].'</strong></td>
                      </tr>
                    </table></td>
                </tr>
              </table></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td valign="top" align="left" ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:10px 0; margin:0; border-collapse:collapse;" >  
          <tr>
            <td width="10" bgcolor="#f9f9f9" style="line-height:1px; font-size:10px; ">&nbsp;&nbsp;</td>
            <td bgcolor="#f9f9f9"><table width="710" border="0" cellspacing="0" bgcolor="#d6d6d6" cellpadding="0" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:2px solid #fd2323; text-align:center; border-collapse:collapse">  
                <tr bgcolor="#f3f3f3">
                  <th width="35" height="50">项次</th>
                  <th>产品型号</th>
                  <th>品牌</th>
                  <th>采购<br /> 数量</th>
                  <th>目标单价<br />
                    ('.$inqinfo['currency'].')</th>
                  <th bgcolor="#daefe0">PMSC报价<br />
                    ('.$inqinfo['currency'].')</th>
                  <th bgcolor="#daefe0">有效期</th>
                  <th bgcolor="#daefe0">报价备注</th>
                  <th bgcolor="#daefe0">报价<br/>专员</th>
                </tr>';
		    foreach($inqdArray as $k=>$inqd){
                $mess .='<tr bgcolor="#FFFFFF" >
                  <td width="35" height="30" style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.($k+1).'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#0055aa; ">'.$inqd['part_no'].'</strong></td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$inqd['bname'].'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6">'.$inqd['number'].'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($inqd['oa_target_price']>0?$inqinfo['currency'].' '.$inqd['oa_target_price']:'--').'</strong></td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6"><strong style="color:#fd2323;font-family:\'微软雅黑\'">'.($inqd['oa_result_price']>0?($inqinfo['currency'].' '.$inqd['oa_result_price']):'--').'</strong></td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;color:#3fa156">'.($inqd['expiration_time']?date("Y-m-d",$inqd['expiration_time']):'--').'</td>
                  <td style="border-right:1px solid #d6d6d6;border-top:1px solid #d6d6d6;color:#3fa156">'.($inqd['oa_inqd_remark']?$inqd['oa_inqd_remark']:'--').'</td>
                  <td style="border-top:1px solid #d6d6d6;color:#3fa156">'.($inqd['oa_pmsc_name']?$inqd['oa_pmsc_name']:'--').'</td>
                </tr>';
		    }
              $mess .='</table></td>
          </tr>
        </table></td>
      </td>
    </tr>';
		
		$fromname = '盛芯电子';
		$emailarr = $this->_emailService->getEmailAddress('oa_inquiry_back');
		
		$emailto = $sellinfo['email'];
		$emailcc = $emailbcc = array();
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	}
}