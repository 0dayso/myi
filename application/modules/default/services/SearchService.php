<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_SearchService
{
	private $_searchModer;
	public function __construct()
	{
		$this->_emailService = new Default_Service_EmailtypeService();
		$this->_searchModer = new Default_Model_DbTable_SearchInquiry();
		$this->sqlarr = array('uidtmp'=>$_SESSION['userInfo']['uidSession']);
		$this->fun = new MyFun();
	}
	
	/*
	 * 一个用户最多添加$maxnum个状态为0的记录
	*/
	public function checkNum($maxnum){
		$sqlstr = "SELECT count(id) as num FROM search_inquiry WHERE uid=:uidtmp AND status=0";
		$all = $this->_searchModer->getBySql($sqlstr,$this->sqlarr);
		$total = $all[0]['num'];
		if($total >= $maxnum) return true;
		else return false;
	}
	/**
	 * 检查用户是否已经提交过相同part no
	*/
	public function checkPart($part){
		$r = array('uid'=>$_SESSION['userInfo']['uidSession'],'part_no'=>$part);
		$re = $this->_searchModer->getRowByWhere("uid='".$_SESSION['userInfo']['uidSession']."' AND part_no='{$part}'");
		if($re) return true;
		else return false;
	}
	/*
	 * 用户提交查询提醒邮件
	 */
	public function sendAlertEmail($data){
		$mess ='</tbody>
        </table><tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <!--<tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的Annie.Liu,</div></td>
            </tr>-->
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有客户对如下产品感兴趣，在IC易站搜索该产品，但没有找到相关搜索结果。客户已提交了产品查询请求，请在24小时之内与客户联系，了解客户需求，为客户寻找该产品或根据客户情况推荐适合的产品。</div>
                      <div style="height:5px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                      <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">详细信息请登录&nbsp;<a href="http://www.iceasy.com/icwebadmin/QuoSear" target="_blank" style="color:#fd2323; font-family:\'微软雅黑\';font-size:13px;"><b>IC易站后台</b></a>&nbsp;查看。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>';
		$mess .=$this->getTable($data);
		
		$fromname = 'IC易站';
		$title    = '客户新建搜索查询：#'.$data['part_no'].'#，请及时跟进';
		
		$emailarr = $this->_emailService->getEmailAddress('new_search',$data['uid']);
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = $emailarr['cc'];
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		
		$this->fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	}
	/*
	 * 用户提交查询通知客户
	*/
	public function sendAlertUserEmail($data,$userinfo){
		$mess ='</tbody>
        </table><tr>
      <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
          <tbody>
            <tr>
              <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$data['contact_name'].',</div></td>
            </tr>
            <tr>
              <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                  <tr>
                    <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">感谢您对IC易站的惠顾！确认收到您的如下产品查询请求。</div>
                      <div style="padding:3px 0; margin:0; font-size:12px; font-family:\'微软雅黑\'; color:#5b5b5b">很抱歉目前IC易站上暂无该产品，我们的产品团队每日都会增加新产品，当该产品上线时，我们会发邮件通知您。</div></td>
                  </tr>
                </table></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
   ';
		$mess .=$this->getTable($data);

		$fromname = 'IC易站';
		$title    = 'IC易站已收到您的#'.$data['part_no'].'#查询请求，会尽快处理';
	
		$emailarr = $this->_emailService->getEmailAddress('new_search',$data['uid']);
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		//更改脚本联系方式和email为销售
		$staffservice = new Icwebadmin_Service_StaffService();
		$sellinfo = $staffservice->sellbyuid($data['uid']);
		$this->fun->sendemail($userinfo['email'], $mess, $fromname, $title,$emailcc,$emailbcc,array(),$sellinfo);
	}
	//获取table
	public function getTable($data){
		$mess ='<!-------------------------------------------------------内容------------------------------------------------------->
		<!--产品反馈-->
		<tr valign="top">
		<td ><table cellspacing="0" cellpadding="0" border="0" align="center" width="730" bgcolor="#f9f9f9"  style=" font-size:12px; line-height:20px; color:#5b5b5b;font-family:\'微软雅黑\'; padding:0 0 10px 0; margin:0; border-collapse:separate; border-spacing:0px" >
          <tr>
		          <td bgcolor="#f9f9f9"><table cellspacing="0" border="0" cellpadding="0" width="710" style="font-family:\'微软雅黑\';" >
		          		<tr>
		          		<td valign="middle" colspan="2" align="left" height="40" style="line-height:20px; font-size:14px; color:#565656;font-family:\'微软雅黑\';"><span style="font-size:14px;font-weight:bold; display:inline-block; padding:3px 0; background:#555555;color:#ffffff;font-family:\'微软雅黑\'">&nbsp;&nbsp;&nbsp;产品反馈&nbsp;&nbsp;&nbsp;</span> </td>
		          		</tr>
		          		<tr>
		          		<td width="10" style="font-size:10px; width:10px;">&nbsp;&nbsp;&nbsp;</td>
		          				<td valign="top" align="left" ><table width="710" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff" style="line-height:20px; font-size:12px; color:#565656;font-family:\'微软雅黑\'; border:1px solid #d6d6d6">
                      <tr>
		          						<td width="120" height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;产品型号：</td>
                        <td width="200" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6"><strong style="color:#ff6600;font-family:\'微软雅黑\'">&nbsp;&nbsp;'.$data['part_no'].'</strong></td>
		                        		<td width="120" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;品牌：</td>
                        <td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\';">'.$data['brand'].'</strong></td>
		                        		</tr>
		                        				<tr  bgcolor="#ffffff">
		                        				<td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系人：</td>
		                        						<td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$data['contact_name'].'</strong></td>
		                        						<td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;border-right:1px solid #d6d6d6">&nbsp;&nbsp;联系方式：</td>
		                        								<td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.$data['contact'].'</strong></td>
		                        								</tr>
		                        								<tr  bgcolor="#ffffff">
		                        								<td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;提交时间：</td>
		                        								<td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.date('Y/m/d H:i').'</strong></td>
		                        								<td style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6;border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;是否上线通知：</td>
		                        								<td style="background:#ffffff;font-family:\'微软雅黑\';border-bottom:1px solid #d6d6d6;">&nbsp;&nbsp;<strong style="color:#000000;font-family:\'微软雅黑\'">'.($data['notice']==1?'是':'否').'</strong></td>
		                        								</tr>
		                        								<tr  bgcolor="#ffffff">
		                        										<td height="30" style="background:#ffffff;font-family:\'微软雅黑\';border-right:1px solid #d6d6d6">&nbsp;&nbsp;说明：</td>
		                        										<td colspan="3" style="background:#ffffff;font-family:\'微软雅黑\';"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="7">&nbsp;</td><td style="font-family:\'微软雅黑\'; font-size:12px;" ><strong style="color:#000000;font-family:\'微软雅黑\'">'.nl2br($data['explanation']).'</strong></td></tr></table></td>
		                        										</tr>
		                        										</table></td>
		                        										</tr>
		              </table></td>
		          </tr>
		        </table></td>
		    </tr>';
		return $mess;
	}
	/**
	 * 热门推荐
	 */
	public function getSearchRight(){
		$rearr = array();
		//产品目录
		$frontendOptions = array('lifeTime' => 3600*24,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'search_search_right';
		if(!$rearr = $cache->load($cache_key)) {
			$rModer = new Default_Model_DbTable_Recommend();
			$sqlstr ="SELECT pro.* FROM recommend as re
						LEFT JOIN product as pro ON re.comid=pro.id
						WHERE re.type='hot' AND re.part='home' AND re.status = 1 AND pro.status = 1 ORDER BY Rand() LIMIT 6";
			$rearr['prat'] = $rModer->getBySql($sqlstr);
			
			$sqlstr =" SELECT id,title FROM news WHERE status = 1 ORDER BY Rand() LIMIT 3 ";
			$rearr['news'] = $rModer->getBySql($sqlstr);
			$sqlstr =" SELECT id,title FROM solution WHERE status = 1 ORDER BY Rand() LIMIT 3 ";
			$rearr['solution'] = $rModer->getBySql($sqlstr);
			$sqlstr =" SELECT id,title FROM seminar WHERE status = 1 ORDER BY Rand() LIMIT 3 ";
			$rearr['seminar'] = $rModer->getBySql($sqlstr);
			$sqlstr ="SELECT distinct(temp2),temp4 FROM `default_log` WHERE controller='search' AND temp3>0  ORDER BY Rand() LIMIT 10";
			$rearr['sq'] = $rModer->getBySql($sqlstr);
			$cache->save($rearr,$cache_key);
		}
		
		return $rearr;
	}
	/**
	 * 点击排行
	 */
	public function getClickPartNo(){
		$rearr = array();
		//产品目录
		$frontendOptions = array('lifeTime' => 3600*24,'automatic_serialization' => true);
		$backendOptions = array('cache_dir' => CACHE_PATH);
		//$cache 在先前的例子中已经初始化了
		$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		// 查看一个缓存是否存在:
		$cache_key = 'search_click_partno';
		if(!$rearr = $cache->load($cache_key)) {
			$rModer = new Default_Model_DbTable_Recommend();
			$sqlstr ="SELECT pro.* FROM default_view_log as vl
						LEFT JOIN product as pro ON vl.rel=pro.id
						WHERE vl.rev LIKE '%part_id%' AND pro.status = 1 ORDER BY Rand() LIMIT 10";
			$rearr['sq'] = $rModer->getBySql($sqlstr);
			$cache->save($rearr,$cache_key);
		}
	
		return $rearr;
	}
	public function getClickPartNokeywords(){
		$re = $this->getClickPartNo();
		$str = '';
		foreach($re['sq'] as $v){
			$str .=','.$v['part_no'];
		}
		return $str;
	}
}