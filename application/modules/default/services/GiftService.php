<?php
require_once 'Iceaclib/common/fun.php';
class Default_Service_GiftService
{
	private $_giftModel;
	private $_uid;
	private $_prizModel;
	public function __construct() {
		$this->_uid = $_SESSION['userInfo']['uidSession'];
		$this->_giftModel = new Icwebadmin_Model_DbTable_Model("gift");
		$this->_prizModel = new Icwebadmin_Model_DbTable_Model("prize");
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getGiftExchangeNum($typestr='')
	{
		$sqlstr = "SELECT count(ge.id) as num  FROM gift_exchange as ge
		WHERE ge.uid='{$this->_uid}'  $typestr";
		return $this->_giftModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	 */
	public function getGiftExchange($offset,$perpage,$typestr='')
	{
		$sqlstr ="SELECT g.images,ge.*,u.uname,up.companyname,p.province,c.city,e.area,
    	a.name,a.companyname,a.address,a.zipcode,a.mobile,a.tel,
    	ch.cou_number,ch.track,cou.name as couname
		FROM gift_exchange as ge
		LEFT JOIN gift as g ON ge.giftid = g.id
		LEFT JOIN user as u ON u.uid = ge.uid
		LEFT JOIN user_profile as up ON up.uid = ge.uid
		
		LEFT JOIN order_address as a ON ge.addressid=a.id
    	LEFT JOIN province as p ON a.province=p.provinceid
    	LEFT JOIN city as c ON a.city=c.cityid
    	LEFT JOIN area as e ON a.area = e.areaid
    	
    	LEFT JOIN courier_history as ch ON ch.id = ge.courierid
    	LEFT JOIN courier as cou ON ch.cou_id = cou.id
		WHERE ge.uid='{$this->_uid}' $typestr
		ORDER BY ge.status ASC,ge.id DESC LIMIT $offset,$perpage";
		return $this->_giftModel->getBySql($sqlstr);
	}
	/**
	 * 获取by id
	 */
	public function getGiftExchangeByid($id)
	{
		return $this->_giftModel->QueryRow("SELECT ge.* FROM gift_exchange as ge
				WHERE ge.id='{$id}' AND ge.uid='{$this->_uid}'");
	}
	/**
	 * 恢复被占库存
	 */
	public function resstockcover($giftid,$stock){
		$stock = (int)$stock;
		$re = $this->_giftModel->updateBySql("UPDATE gift SET stock_cover =stock_cover - {$stock} WHERE id='$giftid'");
		if($re){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 获取抽奖礼品
	 */
	public function getPriz($type){
		$re = $this->_prizModel->Query("SELECT p.* FROM prize as p
				WHERE p.type='{$type}' ORDER BY p.awards ASC");
		$re[] = $re[0];unset($re[0]);
		return $re;
	}
	/**
	 * 获取抽奖数组
	 */
	public function getPrizArray($type){
		$data = array();
		$priz = $this->getPriz($type);
		foreach($priz as $v){
			$prob = 0;
			if($v['limitstock']){
				if($v['stock']>$v['stock_cover']){
					$prob = $v['probability'];
				}
			}else $prob = $v['probability'];
			$prizemessage = '';
			if($v['awards']==1) $prizemessage="恭喜您，抽中一等奖";
			elseif($v['awards']==2) $prizemessage="恭喜您，抽中二等奖";
			elseif($v['awards']==3) $prizemessage="恭喜您，抽中三等奖";
			elseif($v['awards']==4) $prizemessage="恭喜您，抽中积分奖励";
			elseif($v['awards']==5) $prizemessage="恭喜您，抽中积分奖励";
			elseif($v['awards']==6) $prizemessage="恭喜您，抽中积分奖励";
			elseif($v['awards']==7) $prizemessage="恭喜您，抽中积分奖励";
			elseif($v['awards']==0) $prizemessage="很遗憾，这次您未抽中奖";
			$data[] = array(
					"prizeid"=>$v['id'],
					"prize" => $v['awards'],
					"prob" => $prob,
					"prizemessage"=>$prizemessage,
					"mess2"=>$v['name']);
		}
		return $data;
	}
	/**
	 * 更新奖品中奖数
	 */
	public function updatePrizCover($id){
		return $this->_prizModel->updateBySql("UPDATE prize SET stock_cover=stock_cover+1 WHERE id='{$id}'");
	}
	/**
	 * 获取参加抽奖人数
	 */
	public function getjoinNum($where=''){
		return $this->_prizModel->QueryItem("SELECT count(id) FROM score_log WHERE (action='lotteryaction' or action='lottery') {$where}");
	}
	/**
	 * 获取分享已经获得的积分
	 */
	public function getshareNum($uid){
		$stime = strtotime(date("Y-m-d 00:00:00"));
		$etime = strtotime(date("Y-m-d 23:59:59"));
		$re=$this->_prizModel->QueryItem("SELECT count(id) FROM score_log WHERE action='bdshare' AND uid='{$uid}' AND temp3=1 AND (created BETWEEN '{$stime}' AND '{$etime}')");
		return $re?$re:0;
	}
	/**
	 * 获奖名单
	 */
	public function getWinners($where='',$orderby='ORDER BY sl.`temp5` ASC'){
		return $this->_prizModel->Query("SELECT sl.*,u.uname
		FROM `score_log` as sl
	   LEFT JOIN user as u ON u.uid = sl.uid
		WHERE sl.action='lottery' AND sl.temp4!='' AND sl.temp2='jifen_winners' $where $orderby");
		
	}
	//兑换申请邮件通知
	public function mailalert(){
		$this->_fun = new MyFun();
		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
 
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">有客户提交了积分兑换礼品申请，请处理。详情请到“<a href="www.iceasy.com/icwebadmin/GiftDhgl">兑换管理</a>”查看。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
	
		$fromname = '盛芯电子';
		$title    = '客户提交了礼品申请，请处理';
	
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('giftapply');
			
		$emailto = $emailcc = $emailbcc = array();
		if(!empty($emailarr['to'])){
			$emailto = array_merge($emailto,$emailarr['to']);
		}
		if(!empty($emailarr['cc'])){
			$emailcc = array_merge($emailarr['cc'],$emailcc);
		}
		if(!empty($emailarr['bcc'])){
			$emailbcc = $emailarr['bcc'];
		}
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),0);
	
	}
}