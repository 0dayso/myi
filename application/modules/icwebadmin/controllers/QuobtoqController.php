<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_QuoBtoqController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
	private $_bomService;
	private $_productService;
	private $_adminlogService;
    public function init(){
    	/*************************************************************
    	 ***		创建区域ID               ***
    	**************************************************************/
    	$controller            = $this->_request->getControllerName();
    	$controllerArray       = array_filter(preg_split("/(?=[A-Z])/", $controller));
    	$this->Section_Area_ID = $this->view->Section_Area_ID = $controllerArray[1];
    	$this->Staff_Area_ID   = $this->view->Staff_Area_ID = $controllerArray[2];
    	
    	/*************************************************************
    	 ***		创建一些通用url             ***
    	**************************************************************/
    	$this->indexurl = $this->view->indexurl = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}";
    	$this->addurl   = $this->view->addurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/add";
    	$this->editurl  = $this->view->editurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/edit";
    	$this->deleteurl= $this->view->deleteurl= "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/delete";
    	$this->logout   = $this->view->logout   = "/icwebadmin/index/LogOff/";
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->areaService = new Icwebadmin_Service_AreaService();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	$this->_bomService      = new Icwebadmin_Service_BomService();
    	$this->_productService = new Icwebadmin_Service_ProductService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();

    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	$this->_fun = $this->view->fun = new MyFun();
    }
    public function indexAction(){
    	$xswhere = '';
    	$wait_sql = " AND bo.status=2";
    	$total = $this->view->waitnum = $this->_bomService->getNumber($wait_sql.$xswhere);
    	$perpage=20;
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->inquiry = $this->_bomService->getBom($offset,$perpage,$wait_sql.$xswhere);
    }
    public function editAction(){
    	$this->view->id = $id = $this->_getParam('id');
    	$this->_helper->layout->disableLayout();
    	//询价者信息
    	$this->view->user = $user = $this->_bomService->getUserById($id);
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$id         = $data['id'];
    		$changeinq  = $data['changeinq'];
    		$bom_number = $data['bom_number'];
    		$error = 0;$message='';
    		$upData = array();
    		if(!$bom_number){
    			$message .='询价编号为空';
    			$error++;
    		}
    		if(empty($changeinq)){
    			$message .='请选择需要转询价的型号';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->_bomModer = new Icwebadmin_Model_DbTable_Bom();
    			$bom = $this->_bomService->getBomByID($id);
    			$dids = implode(',',$changeinq);
    			$tdids= time().';'.$dids;
    			$changeinq = ($bom['changeinq']?$bom['changeinq'].'<>'.$tdids:$tdids);
    			$upData    = array('status'=>2,'changeinq'=>$changeinq,'modified'=>time(),'modified_by'=>$_SESSION['staff_sess']['staff_id']);
    			$re = $this->_bomModer->updateBom($id,$upData);
    			if($re){//日志
    				//发邮件通知
    				//负责销售
    				$staffinfo = $this->_bomService->getSellByBomid($id);
    				$email = $this->_bomService->emailaddprod($bom,$staffinfo,$data['changeinq']);
    				if($email){
    					$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$bom_number,'temp4'=>'BOM转询价提醒添加型号邮件成功'));
    				}else{
    					$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价提醒添加型号邮件失败'));
    				}
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$bom_number,'temp4'=>'BOM转询价提交成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交成功'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价提交失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'提交失败'));
    				exit;
    			}
    		}
    	}
    	//询价详细
    	$this->view->bom = $this->_bomService->getBomByID($id);
    }
    /**
     * 检查型号是否存在
     */
    public function checkprodAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$did   = $data['did'];
    		$part_no   = $data['part_no'];
    	    $part_brand = $this->_productService->getPid($part_no);
    	    if($part_brand['id']){
    	    	//更新
    	    	$bomdModel = new Icwebadmin_Model_DbTable_BomDetailed();
    	    	$re = $bomdModel->updateBomdet($did, array('part_id'=>$part_brand['id']));
    	    	echo Zend_Json_Encoder::encode(array("code"=>0,"part_id"=>$part_brand['id'],"brand"=>$part_brand['brand'],"message"=>"型号：".$part_no."，已经存在IC易站上"));
    	    	exit;
    	    }else{
    	    	echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'没有匹配到'));
    	    	exit;
    	    }
    	}
    }
    /**
     * BOM转询价
     */
    public function changetoinqAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$data         = $this->getRequest()->getPost();
    		$id           = $this->_fun->decryptVerification($data['id']);
    		$bom_number   = $data['bom_number'];
    		$bomModel    = new Icwebadmin_Model_DbTable_Model('bom');
    		$inqModer    = new Default_Model_DbTable_Inquiry();
    		$inqdetModer = new Default_Model_DbTable_InquiryDetailed();
    		$bom = $this->_bomService->getBomByID($id);
    		$error = 0;$message='';
    		$changeinq = explode('<>',$bom['changeinq']);
    		if(empty($changeinq)){
    			$message ='操作失败，BOM单没有需要转询价单的型号。';
    			$error++;
    		}
    		$last      = $changeinq[count($changeinq)-1];
    		$lastchangeinq = array();
    		if($last){
    			$tmp = explode(';',$last);
    			$lastchangeinq = explode(',',$tmp[1]);
    		}
    		if(empty($bom['detaile'])){
    			$message ='操作失败，BOM单型号为空。';
    			$error++;
    		}
    		foreach($bom['detaile'] as $k=>$v){
    		   if(in_array($v[id],$lastchangeinq)){
    		   	   if(!$v['part_id']){
    		   	   	   $message ='操作失败，BOM单中有型号不在IC易站上，请添加型号。';
    		   	   	   $error++;
    		   	   }
    		   }
    		}
    		if($error){
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价失败','description'=>$message));
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//事务
    			$bomModel->beginTransaction();
    			$data = array('uid'=>$bom['uid'],
    					'bom_number'=>$bom['bom_number'],
    					'currency'=>$bom['currency'],
    					'delivery'=>$bom['delivery'],
    					'status'=>1,
    					'created'=>time()
    			);
    			$new_inqid = $inqModer->addData($data);
    			if(!$new_inqid){
    				$bomModel->rollBack();
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价失败','description'=>'询价ID为空'));
    				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'操作失败，系统错误'));
    				exit;
    			}
    			//更新询价编号
    			$inq_number = 'RFQ'.$new_inqid.substr(microtime(),2,4);
    			$inqModer->update(array("inq_number"=>$inq_number), "id='{$new_inqid}'");
    			//添加询价详细
    			$datas = array();
    			foreach($bom['detaile'] as $k=>$v){
    		        if(in_array($v[id],$lastchangeinq)){
    					$datas[] = array('inq_id'=>$new_inqid,
    							'part_id'=>$v['part_id'],
    							'part_no'=>$v['part_no'],
    							'number' =>$v['number'],
    							'price'  =>$v['price'],
    							'created'=>time());
    				}
    			}
    			if(empty($datas)){
    				$bomModel->rollBack();
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价失败','description'=>'询价详细数组为空'));
    				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'操作失败，系统错误'));
    				exit;
    			}
    			$inqdetModer->addDatas($datas);
    			//更新bom
    			$inq_numbers = ($bom['inq_numbers']?$bom['inq_numbers'].','.$inq_number:$inq_number);
    			$bomupre = $bomModel->update(array('status'=>3,'inq_numbers'=>$inq_numbers), "id={$id}");
    			if(!$bomupre){
    				$bomModel->rollBack();
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>404,'temp2'=>$bom_number,'temp4'=>'BOM转询价失败','description'=>'更新BOM单的询价编号失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'操作失败，系统错误'));
    				exit;
    			}
    			//记录日志
    			$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$bom_number,'temp4'=>'BOM转询价成功','description'=>$inq_number));
    			$bomModel->commit();
    			//发邮件通知销售
    			$inqService = new Icwebadmin_Service_InquiryService();
    			$user    =  $this->_bomService->getUserById($id);
    			$inqinfo = $inqService->getInquiryByID($new_inqid);
    			//负责销售
    			$staffinfo = $this->_bomService->getSellByBomid($id);

    			$emailre = $this->_bomService->emailinqtosell($inqinfo,$user,$staffinfo,$bom);
    			if($emailre){
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>0,'temp2'=>$bom_number,'temp4'=>'BOM转询价邮件通知销售成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'BOM单转询价单成功，并成功发邮件通知销售'));
    				exit;
    			}else{
    				$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>0,'temp2'=>$bom_number,'temp4'=>'BOM转询价邮件通知销售失败'));
    				echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'BOM单转询价单成功，但发邮件通知销售失败'));
    				exit;
    			}
    		}
    	}
    }
}