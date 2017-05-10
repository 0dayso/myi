<?php
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_Service_GiftService
{
	private $_giftModel;
	public function __construct() {
		$this->_giftModel = new Icwebadmin_Model_DbTable_Model("gift");
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getGiftNum($typestr='')
	{
		$sqlstr = "SELECT count(g.id) as num  FROM gift as g
		LEFT JOIN gift_category as gc ON gc.id = g.category
		WHERE g.id!='' $typestr";
		return $this->_giftModel->QueryItem($sqlstr);
	}
	/**
	 * 获取记录
	*/
	public function getGift($offset,$perpage,$typestr='')
	{
		$sqlstr ="SELECT g.*,gc.name as cname FROM gift as g
		LEFT JOIN gift_category as gc ON gc.id = g.category
		WHERE g.id!='' $typestr
		ORDER BY g.score DESC,g.home DESC,g.status DESC,g.stock DESC,g.id DESC LIMIT $offset,$perpage";
		return $this->_giftModel->getBySql($sqlstr);
	}
	/**
	 * 获取礼品类别
	 */
	public function getGiftCategory(){
		return $this->_giftModel->getBySql("SELECT * FROM gift_category WHERE status=1 ORDER BY displayorder ASC,id DESC");
	}
	/**
	 * 获取查询订单数（包括在线和询价订单）
	 */
	public function getGiftExchangeNum($typestr='')
	{
		$sqlstr = "SELECT count(ge.id) as num  FROM gift_exchange as ge
		WHERE ge.id!='' $typestr";
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
		WHERE ge.id!='' $typestr
		ORDER BY ge.status ASC,ge.id DESC LIMIT $offset,$perpage";
		return $this->_giftModel->getBySql($sqlstr);
	}
	/**
	 * 获取by id
	 */
	public function getGiftExchangeByid($id)
	{
		return $this->_giftModel->QueryRow("SELECT ge.* FROM gift_exchange as ge
				WHERE ge.id='{$id}'");
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
	 * 更新库存
	 */
	public function updatestock($giftid,$stock){
		$stock = (int)$stock;
		$re = $this->_giftModel->updateBySql("UPDATE gift SET stock =stock - {$stock},stock_cover =stock_cover - {$stock} WHERE id='$giftid'");
		if($re){
			return true;
		}else{
			return false;
		}
	}
	//兑换申请邮件通知
	public function mailalertuser($userinfo){
		$this->_fun = new MyFun();
		$mess ='</tbody>
        </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
					<tr>
                      <td valign="top"  height="30" ><div style="margin:0; font-size:16px; font-weight:bold; color:#fd2323 ;font-family:\'微软雅黑\'; ">尊敬的'.$userinfo['uname'].',</div></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您提交的兑换礼品申请已经处理，详细处理结果请到 我的IC易站-》<a herf="http://www.iceasy.com/center/exchange">礼品兑换</a> 查看。</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
	
		$fromname = 'IC易站';
		$title    = '您提交的兑换礼品申请已经处理';
	
		$this->_emailService = new Default_Service_EmailtypeService();
		$emailarr = $this->_emailService->getEmailAddress('giftapply_user');
			
		$emailto = array($userinfo['email']);
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
		return $this->_fun->sendemail($emailto, $mess, $fromname, $title,$emailcc,$emailbcc,array(),array(),1);
	
	}
}