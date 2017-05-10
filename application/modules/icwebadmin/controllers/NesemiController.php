<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_NesemiController extends Zend_Controller_Action
{
	
	private $_model; 
	private $_brandMod;
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
    	$this->fronturl = $this->view->fronturl = '/seminar/details?semid=';
    	/*****************************************************************
    	 ***	    检查用户登录状态和区域权限       ***
    	*****************************************************************/
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$loginCheck->staffareaCheck($this->Staff_Area_ID);
    	
    	/*************************************************************
    	 ***		区域标题               ***
    	**************************************************************/
    	$this->sectionarea = new Icwebadmin_Model_DbTable_Sectionarea();
    	$tmp=$this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='".$this->Staff_Area_ID."')");
    	$this->view->AreaTitle=$tmp['staff_area_des'];
    	
    	//加载通用自定义类
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->filter = new MyFilter();
    	$this->_model = new Icwebadmin_Model_DbTable_Seminar();
    	
    	$this->_brandMod = new Icwebadmin_Model_DbTable_Brand();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$perpage=20;
    	$total = $this->_model->getTotal();
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);    	
    	if($_GET['app_level1']){
    		$app_level1 = (int) $_GET['app_level1'];
    		$where[] = 'seminar.app_level1='.$app_level1;
    	}
    	
    	if(isset($_GET['status']) && $_GET['status'] !='' ){
    		$status = (int) $_GET['status'];
    		$where[] = 'seminar.status='.$status;
    	}
    	
    	if($_GET['q'])
    	{
    		$q = $_GET['q'];
    		$where[] = " seminar.title like'%$q%' OR seminar.keyword  like '%$q%'";
    	}    	
    	$data = $this->_model->getAllSeminars($offset,$perpage,$where);
    	$this->view->data = $data;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    
    public function addAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->processData();
    		//Zend_Debug::dump($data); die();
			if(!$data['error']){
				$newid = $this->_model->addDate($data);
				if($newid){
					if($data['type']=='vdo')
					{
						$file = $_FILES['vido_file'];
						$zip = $this->filter->extend($file["name"]);
						if($zip!='zip'){
							$_SESSION['messages'] = "只允许上传zip文件.";
							$this->_redirect($this->editurl.'/id/'.$data['id']);
						}
					   //上传解压
					   $vido_path = 'upload/default/seminar/video/'.$newid.'/';
					   if(!is_dir($vido_path))
					   {
						  if(mkdir($vido_path,0777)){
							@move_uploaded_file($file["tmp_name"],$vido_path.$file["name"]);
							@unlink($file);
							//解压
							$zip =new ZipArchive();
							if ($zip->open($vido_path.$file["name"]) == TRUE)
							{
								$zip->extractTo($vido_path);
								$zip->close();
								@unlink($vido_path.$file["name"]);
							}
						  }
					   }
					}
					
					$weibodata = $this->getRequest()->getPost();
					$_SESSION['messages'] = "研讨会添加成功.";
					$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/webinar-'.$newid.'.html';
					//发新浪微博
					if($weibodata['weibocontent'] && $weibodata['sinaweibo']){
						$sina = new Icwebadmin_Service_SinaweiboService();
						if($weibodata['weibopic'] && $weibodata['image']){
							$sina->upload($weibocontent, HTTPHOST.$weibodata['image']);
						}else{
							$sina->update($weibocontent);
						}
					}
					//发腾讯微博
					if($weibodata['weibocontent'] && $weibodata['qqweibo']){
						$tencent = new Icwebadmin_Service_TencentweiboService();
						if($weibodata['weibopic'] && $weibodata['image']){
							$tencent->add_pic($weibocontent, HTTPHOST.$weibodata['image']);
						}else{
							$tencent->add($weibocontent);
						}
					}
					//日志
					$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'研讨会添加成功'));
					$this->_redirect($this->addurl);
				}else{
					$_SESSION['messages'] = "研讨会添加失败.";
					//日志
					$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$newid,'temp4'=>'研讨会添加失败'));
				}
			}else{
				$_SESSION['messages'] = $data['message'];
			}
			$this->view->processData = $data;
    	}
    	//获取品牌
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!=''");
    }
    
    
    public function editAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$data = $this->processData('edit');
    		//Zend_Debug::dump($data); die();
    		if(!$data['error']){
    			$data['status'] = $data['status']?$data['status']:0;
    			$data['home'] = $data['home']?$data['home']:0;
    			$re = $this->_model->updateSeminar($data['id'],$data);
    			if($re){
    				if($data['type']=='vdo' && $_FILES['vido_file']['name'])
    				{
    					$file = $_FILES['vido_file'];
    					$zip = $this->filter->extend($file["name"]);
    					if($zip!='zip'){
    						$_SESSION['messages'] = "只允许上传zip文件.";
    						$this->_redirect($this->editurl.'/id/'.$data['id']);
    					}
    					//上传解压
    					$vido_path = 'upload/default/seminar/video/'.$data['id'].'/';
    					@move_uploaded_file($file["tmp_name"],$vido_path.$file["name"]);
    					@unlink($file);
    					//解压
    					$zip =new ZipArchive();
    					if ($zip->open($vido_path.$file["name"]) == TRUE)
    					{
    						$zip->extractTo($vido_path);
    						$zip->close();
    						@unlink($vido_path.$file["name"]);
    					}
    					$_FILES['vido_file'];
    				}
    				$_SESSION['messages'] = "研讨会更新成功.";
    				
    				$weibodata = $this->getRequest()->getPost();
    				$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/webinar-'.$data['id'].'.html';
    				//发新浪微博
    				if($weibodata['weibocontent'] && $weibodata['sinaweibo']){
    					$sina = new Icwebadmin_Service_SinaweiboService();
    					if($weibodata['weibopic'] && $weibodata['image']){
    						$sina->upload($weibocontent, HTTPHOST.$weibodata['image']);
    					}else{
    						$sina->update($weibocontent);
    					}
    				}
    				//发腾讯微博
    				if($weibodata['weibocontent'] && $weibodata['qqweibo']){
    					$tencent = new Icwebadmin_Service_TencentweiboService();
    					if($weibodata['weibopic'] && $weibodata['image']){
    						$tencent->add_pic($weibocontent, HTTPHOST.$weibodata['image']);
    					}else{
    						$tencent->add($weibocontent);
    					}
    				}
    				
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'研讨会更新成功'));
    				$this->_redirect($this->editurl.'/id/'.$data['id']);
    			}else{
    				$_SESSION['messages'] = "研讨会更新失败.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['id'],'temp4'=>'研讨会更新失败'));
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    		
    	}else{
    	   $id =(int) $this->getRequest()->getParam('id');
    	   $this->view->id = $id;
    	   if(!$id) $this->_redirect($this->indexurl);
    	   $data = $this->_model->getSeminarDetailById($id);
    	   $this->view->processData = $data[0];
    	}
    	//获取品牌
    	$this->view->brand = $this->_brandMod->getAllByWhere("id!=''");
    }
    /*
     * 改变状态
    */
    public function changestatusAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id     = (int)$formData['id'];
    		$status = (int)$formData['status'];
    		$this->_model->update(array('status'=>$status),"id = {$id}");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改状态成功，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'更改状态失败，改为:'.$status));
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
    /*
     * 推荐首页
    */
    public function changehomeAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$id     = (int)$formData['id'];
    		$home = (int)$formData['homevalue'];
    		$this->_model->update(array('home'=>$home),"id = {$id}");
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改推荐到首页成功，改为:'.$home));
    		echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    		exit;
    	}else{
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$id,'temp4'=>'更改推荐到首页失败，改为:'.$home));
    		echo Zend_Json_Encoder::encode(array("code"=>400,"message"=>'提交失败'));
    		exit;
    	}
    }
    public function processData($pottype=''){
    	$post  = $this->getRequest()->getPost();
    	//Zend_Debug::dump($post); exit;
    	$error = 0;$message = '';
    	if(!$post['type']){
    		$error++;
    		$message .= "请选择类型.<br/>";
    	}
    	if(!$post['title']){
    		$error++;
    		$message .="请输入标题.<br/>";
    	}
    	if(!$post['author']){
    		$error++;
    		$message .="请输入作者.<br/>";
    	}
    	if(!$post['source']){
    		$error++;
    		$message .="请输入来源.<br/>";
    	}
    	if(!$post['app_level1']){
    		$error++;
    		$message .="请选择应用分类.<br/>";
    	}
    	if(!$post['keyword']){
    		$error++;
    		$message .="请输入关键字.<br/>";
    	}
    	if(!$post['description']){
    		$error++;
    		$message .="请输入研讨会简介.<br/>";
    	}
    	if(!$post['content']){
    		$error++;
    		$message .="请输入研讨会详细描述.<br/>";
    	}
    	if($post['type']=='vdo'){
    		if(!$post['company']){
    			$error++;
    			$message .="请输入公司名.<br/>";
    		}
    		if(!$post['com_log']){
    			$error++;
    			$message .="请上传公司log.<br/>";
    		}
    		if(!$post['com_profile']){
    			$error++;
    			$message .="请输入公司简介.<br/>";
    		}
    		if($_FILES['vido_file']['type'] != 'application/x-zip-compressed' && $pottype!='edit'){
    			$error++;
    			$message .="上传视频文件必须为.zip<br/>";
    		}
    	}
    	
    	//替换 为了处理图片
    	$post['content'] = str_replace('\\','',$post['content']);
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		$data = array(
    		'type'=>$post['type'],
    		'home'=>$post['home'],
    		'title'=>$post['title'],			
    		'author'=>$post['author'],
    		'source'=>$post['source'],
    		'keyword'=>$post['keyword'],
    		'description'=>$post['description'],
    		'content'=> $post['content'],
    		'app_level1'=>$post['app_level1'],
    		'app_level2'=>$post['app_level2'],	
    		'status'=>(int) $post['status']);
    		if($post['type'] == 'vdo'){
    		   $data['brand_id'] =$post['brand_id'];
    		   $data['company']  =$post['company'];
    		   $data['com_url']  =$post['com_url'];
    		   $data['com_log']  =$post['com_log'];
    		   $data['com_profile']=$post['com_profile'];
    		   $data['speaker']  =$post['speaker'];
    		   $data['spe_head'] =$post['spe_head'];
    		   $data['spe_post'] =$post['spe_post'];
    		   $data['spe_com']  =$post['spe_com'];
    		   $data['spe_profile']=$post['spe_profile'];
    		}
    		if($pottype=='edit'){
    			$data['id']=$post['id'];
    			$data['modified']=time();
    			$data['modified_by']=$_SESSION['staff_sess']['staff_id'];
    		}else{
    			$data['created']=time();
    			$data['created_by']=$_SESSION['staff_sess']['staff_id'];
    		}
    		
    		return $data;
    	}
    	
    	
    }
}