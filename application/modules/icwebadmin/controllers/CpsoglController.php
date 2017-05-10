<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_CpsoglController extends Zend_Controller_Action
{
	
	private $_model ;
	private $_service ;
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
    	$this->uploadurl = $this->view->uploadurl =  "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/upload";
    	
    	$this->view->iconurl	= "/upload/default/applications/icon/";
    	$this->view->solveurl	= "/upload/default/applications/solve/";
    	$this->view->fileurl		= "/upload/default/applications/file/";
    	$this->fronturl = $this->view->fronturl = '/applications/details?solid=';
    	$this->view->openpicurl   = "/icwebadmin/common/openpic";
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
    	$this->_model = new Icwebadmin_Model_DbTable_Solution();
    	$this->_service = new Icwebadmin_Service_CpsoglService();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$sql = '';
    	if($_GET['searchtitle']){
    		$this->view->searchtitle = trim($_GET['searchtitle']);
    		$sql = " AND sol.title LIKE '%".$this->view->searchtitle."%'";
    	}
    	$perpage=20;
    	$total = $this->_service->getRowNum($sql);
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$this->view->datas = $this->_service->getAllSol($offset,$perpage,$sql);
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();    
    }
    
    public function addAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$weibodata = $post = $this->getRequest()->getPost();
    		$data = $this->processData($post);
    		//核心器件
    		$core_parts = $post['core_parts'];
    		//周边器件
    		$parts = $post['parts'];
    		//BOM清单
    		$bom_prodid_arr = $post['bom_prod_id'];
    		$bom_dosage_arr = $post['bom_dosage'];
    		$bom_remark_arr = $post['bom_remark'];
    		//Zend_Debug::dump($data); die();
    		if(!$data['error']){
    			$newid = $this->_model->addDate($data);
    			if($newid){
    				//添加周边器件
    				if($core_parts){
    					$this->_service->addSolutionProduct($newid,$core_parts,'core');
    				}
    				//添加周边器件
    				if($parts){
    					$this->_service->addSolutionProduct($newid,$parts);
    				}
    				//bom清单
    				if($post['bom_prod_id']){
    					$this->_service->addSolutionProduct($newid,implode(',',$bom_prodid_arr),'bom',array('dosage'=>$bom_dosage_arr,'remark'=>$bom_remark_arr));
    				}
    				$_SESSION['messages'] = "添加成功.";
    				
    				$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/solution-'.$newid.'.html';
    				$imgpart = 'http://www.iceasy.com/upload/default/applications/icon/';
    				//发新浪微博
    				if($weibodata['weibocontent'] && $weibodata['sinaweibo']){
    					$sina = new Icwebadmin_Service_SinaweiboService();
    					if($weibodata['weibopic'] && $weibodata['sol_img']){
    						$sina->upload($weibocontent, $imgpart.$weibodata['sol_img']);
    					}else{
    						$sina->update($weibocontent);
    					}
    				}
    				//发腾讯微博
    				if($weibodata['weibocontent'] && $weibodata['qqweibo']){
    					$tencent = new Icwebadmin_Service_TencentweiboService();
    					if($weibodata['weibopic'] && $weibodata['sol_img']){
    						$tencent->add_pic($weibocontent, $imgpart.$weibodata['sol_img']);
    					}else{
    						$tencent->add($weibocontent);
    					}
    				}
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp2'=>$newid,'temp4'=>'方案添加成功'));
    				$this->_redirect($this->addurl);
    			}else{
    				$_SESSION['messages'] = "添加失败.";
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'A','temp1'=>400,'temp2'=>$newid,'temp4'=>'方案添加失败'));
    			}
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}
    }
    
    function editAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	if($this->getRequest()->isPost()){
    		$weibodata = $post = $this->getRequest()->getPost();
    		$data = $this->processData($post,'edit');
    		//核心器件
    		$core_parts = $post['core_parts'];
    		//周边器件
    		$parts = $post['parts'];
    		//BOM清单
    		$bom_prodid_arr = $post['bom_prod_id'];
    		$bom_dosage_arr = $post['bom_dosage'];
    		$bom_remark_arr = $post['bom_remark'];
    		
    		if(!$data['error']){
    			$data['status'] = $data['status']?$data['status']:0;
    			$re = $this->_model->updateSeminar($data['id'],$data);
    			$this->_service->updateSolutionProduct($data['id'],$parts);
    			//添加周边器件
    			$this->_service->updateSolutionProduct($data['id'],$core_parts,'core');
    			//添加周边器件
    			$this->_service->updateSolutionProduct($data['id'],$parts);
    			//bom清单
    			$this->_service->updateSolutionProduct($data['id'],implode(',',$bom_prodid_arr),'bom',array('dosage'=>$bom_dosage_arr,'remark'=>$bom_remark_arr));
    			
    			$_SESSION['messages'] = "更新成功.";
    			$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/solution-'.$data['id'].'.html';
    			$imgpart = 'http://www.iceasy.com/upload/default/applications/icon/';
    			//发新浪微博
    			if($weibodata['weibocontent'] && $weibodata['sinaweibo']){
    				$sina = new Icwebadmin_Service_SinaweiboService();
    				if($weibodata['weibopic'] && $weibodata['sol_img']){
    					$sina->upload($weibocontent, $imgpart.$weibodata['sol_img']);
    				}else{
    					$sina->update($weibocontent);
    				}
    			}
    			//发腾讯微博
    			if($weibodata['weibocontent'] && $weibodata['qqweibo']){
    				$tencent = new Icwebadmin_Service_TencentweiboService();
    				if($weibodata['weibopic'] && $weibodata['sol_img']){
    					$tencent->add_pic($weibocontent, $imgpart.$weibodata['sol_img']);
    				}else{
    					$tencent->add($weibocontent);
    				}
    			}
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['id'],'temp4'=>'方案更新成功'));
    			
    			$this->_redirect($this->editurl.'/id/'.$data['id']);
    		}else{
    			$_SESSION['messages'] = $data['message'];
    		}
    		$this->view->processData = $data;
    	}else{
    		$id =(int) $this->getRequest()->getParam('id');
    		$this->view->id = $id;
    		if(!$id) $this->_redirect($this->indexurl);
    		$this->view->processData = $this->_model->getPartsBySolutionId($id);
    		//核心器件
    		$core_partsarr = $this->_service->getSolutionProduct($id,'core');
    		$this->view->processData['core_parts'] = $core_partsarr['parts_str'];
    		//周边器件
    		$partsarr = $this->_service->getSolutionProduct($id,'rim');
    		$this->view->processData['parts'] = $partsarr['parts_str'];
    		//BOM清单
    		$this->view->processData['bom'] = $this->_service->getSolutionProduct($id,'bom');
    	}
    }
    /**
     * 成功案例
     */
    function cgalAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id =(int) $this->getRequest()->getParam('id');
    	$this->view->id = $id;
    	if(!$id) $this->_redirect($this->indexurl);
    	$this->view->processData = $this->_model->getPartsBySolutionId($id);
    	//获取成功案例
    	$this->view->cgal = $this->_service->getSolCaseByid($id);
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$this->_service->updateSolutionCase($formData['solid'],$formData['company_id'],$formData['project_name']);
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$formData['solid'],'temp4'=>'更新成功案例成功'));
    		$_SESSION['messages'] = '更新成功';
    		$this->_redirect('/icwebadmin/CpSogl/cgal/id/'.$formData['solid']);
    	}
    }
    /**
     * 选择公司
     */
    public function companyAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	//获取公司
    	$this->view->company = $this->_service->getSolutionCompany();
    	$this->view->id = $_GET['id'];
    }
    /**
     * 添加公司
     */
    public function addcompanyAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_solcomModel = new Icwebadmin_Model_DbTable_Model('solution_company');
    	$this->view->id = $_GET['id'];
    	$this->view->comid = $_GET['comid'];
    	if($this->view->comid){
    		$this->view->company = $this->_solcomModel->getRowByWhere("id=".$this->view->comid);
    	}
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$error = 0;$message='';
    		if(empty($post['company_name'])){
    			$message .='请输入公司名称';
    			$error++;
    		}
    	    if(empty($post['company_profile'])){
    			$message .='请输入公司简介';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//查询是否已经存在公司
    			if($post['comid']){
    			   $re = $this->_solcomModel->getRowByWhere("id!='".$post['comid']."' AND company_name='".$post['company_name']."'");
    			}else{
    			   $re = $this->_solcomModel->getRowByWhere("company_name='".$post['company_name']."'");
    			}
    			if($re){
    				echo Zend_Json_Encoder::encode(array("code"=>1,"comid"=>$re['id'],"comname"=>$re['company_name'],"message"=>"此公司已经存在"));
    				exit;
    			}
    			if($post['comid']){
    				$this->_solcomModel->update(array('company_name'=>$post['company_name'],
    					'company_profile'=>$post['company_profile']), "id=".$post['comid']);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$post['comid'],'temp4'=>'修改公司成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0,"comid"=>$post['comid'],"comname"=>$post['company_name'],"message"=>'修改公司成功'));
    				exit;
    			}else{
    			    $newid = $this->_solcomModel->addData(array('company_name'=>$post['company_name'],
    					                             'company_profile'=>$post['company_profile'],
    					                             'created'=>time(),
    					                             'created_by'=>$_SESSION['staff_sess']['staff_id']));
    			    if($newid){
    			    	//日志
    			    	$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$newid,'temp4'=>'添加公司成功'));
    			    	echo Zend_Json_Encoder::encode(array("code"=>0,"comid"=>$newid,"comname"=>$post['company_name'],"message"=>'添加公司成功'));
    			    	exit;
    			    }else{
    			    	//日志
    			    	$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$newid,'temp4'=>'添加公司失败，id为空'));
    			    	echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'添加失败，系统错误'));
    			    	exit;
    			    }
    			}
    		}
    	}
    }
    /**
     * 技术支持
     */
    function jszqAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id =(int) $this->getRequest()->getParam('id');
    	$this->view->id = $id;
    	if(!$id) $this->_redirect($this->indexurl);
    	$this->view->processData = $this->_model->getPartsBySolutionId($id);
    	//获取技术支持
    	$this->view->jszq = $this->_service->getSolEngineerByid($id);
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$this->_service->updateSolutionEngineer($formData['solid'],$formData['engineer_id']);
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$formData['solid'],'temp4'=>'更新技术支持成功'));
    		$_SESSION['messages'] = '更新成功';
    		$this->_redirect('/icwebadmin/CpSogl/jszq/id/'.$formData['solid']);
    	}
    }
    /**
     * 选择技术支持
     */
    public function engineerAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	//获取公司
    	$this->view->engineer = $this->_service->getSolEngineer();
    	$this->view->id = $_GET['id'];
    }
    /**
     * 添加技术支持
     */
    public function addengineerAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_engineerModel = new Icwebadmin_Model_DbTable_Model('engineer');
    	$this->view->id = $_GET['id'];
    	$this->view->engid = $_GET['engid'];
    	if($this->view->engid){
    		$this->view->engineer = $this->_engineerModel->getRowByWhere("id=".$this->view->engid);
    	}
    	if($this->getRequest()->isPost()){
    		$post = $this->getRequest()->getPost();
    		$error = 0;$message='';
    		if(empty($post['name'])){
    			$message .='请输入姓名';
    			$error++;
    		}
    		if(empty($post['office'])){
    			$message .='请输入职位';
    			$error++;
    		}
    		if(empty($post['tel'])){
    			$message .='请输入电话';
    			$error++;
    		}
    		if(empty($post['email'])){
    			$message .='请输入邮箱';
    			$error++;
    		}
    		if(empty($post['introduction'])){
    			$message .='请输入简介';
    			$error++;
    		}
    		if(empty($post['uploadimg'])){
    			$message .='请上传头像';
    			$error++;
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			//查询是否已经存在公司
    			if($post['engid']){
    				$re = $this->_engineerModel->getRowByWhere("id!='".$post['engid']."' AND name='".$post['name']."'");
    			}else{
    				$re = $this->_engineerModel->getRowByWhere("name='".$post['name']."'");
    			}
    			if($re){
    				echo Zend_Json_Encoder::encode(array("code"=>1,"engid"=>$re['id'],"name"=>$re['name'],"office"=>$re['office'],"message"=>"此姓名已经存在"));
    				exit;
    			}
    			$data = array('name'=>$post['name'],
    					'office'=>$post['office'],
    					'head'=>$post['uploadimg'],
    					'introduction'=>$post['introduction'],
    					'tel'=>$post['tel'],
    					'email'=>$post['email'],
    					'created'=>time());
    			if($post['engid']){
    				$this->_engineerModel->update($data, "id=".$post['engid']);
    				//日志
    				$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$post['engid'],'temp4'=>'修改技术支持成功'));
    				echo Zend_Json_Encoder::encode(array("code"=>0,"engid"=>$post['engid'],"name"=>$post['name'],"office"=>$post['office'],"message"=>'修改成功'));
    				exit;
    			}else{
    				$newid = $this->_engineerModel->addData($data);
    				if($newid){
    					//日志
    					$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$newid,'temp4'=>'添加技术支持成功'));
    					echo Zend_Json_Encoder::encode(array("code"=>0,"engid"=>$newid,"name"=>$post['name'],"office"=>$post['office'],"message"=>'添加成功'));
    					exit;
    				}else{
    					//日志
    					$this->_adminlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$newid,'temp4'=>'添加技术支持失败，id为空'));
    					echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'添加失败，系统错误'));
    					exit;
    				}
    			}
    		}
    	}
    }
    /**
     * 设计文档
     */
    function sjwdAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id =(int) $this->getRequest()->getParam('id');
    	$this->view->id = $id;
    	if(!$id) $this->_redirect($this->indexurl);
    	$this->view->processData = $this->_model->getPartsBySolutionId($id);
    	//测试文档
    	$this->view->sjwd = $this->_service->getSolDocumentByid($id);
    	if($this->getRequest()->isPost()){
    		$formData  = $this->getRequest()->getPost();
    		$this->_service->updateSolutionDocument($formData['solid'],$formData);
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$formData['solid'],'temp4'=>'更新设计文档成功'));
    		$_SESSION['messages'] = '更新成功';
    		$this->_redirect('/icwebadmin/CpSogl/sjwd/id/'.$formData['solid']);
    	}
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
    public function processData($post,$pottype=''){
    	$error = 0;$message = '';
        //替换
        $post['home'] = (isset($post['home'])) ?  1 : 0;
    	$post['outline'] = str_replace('\\','',$post['outline']);
    	$post['superiority'] = str_replace('\\','',$post['superiority']);
    	
    	if(!$post['title']){
    		$error++;
    		$message .= "请输入文章标题！<br>";
    			
    	}
    	if(!$post['title']){
    		$error++;
    		$message .= "请输入作者！<br>";
    	}    	
    	if(!$post['app_level1']){
    		$error++;
    		$message .= "请选择应用分类！<br>";
    	}    	
    	/*if(!$post['sol_img']){
    		$error++;
    		$message .= "请上传方案图片！<br>";
    	}    	
    	if(!$post['datasheet']){
    		$error++;
    		$message .= "请上传方案文档！<br>";
    	}*/    	  	
    	if($error){
    		$post['error'] = $error;
    		$post['message'] = $message;
    		return $post;
    	}else{
    		$data = array(
    		'title'=>$post['title'],
    		'home'=>$post['home'],
    		'author'=>$post['author'],
    		'source'=>$post['source'],
    		'sol_img'=>$post['sol_img'],			
    		'solution_no'=>$post['solution_no'],
    		'tags'=>$post['tags'],
    		'datasheet'=>$post['datasheet'],
    		'description'=>$post['description'],
    		'outline'=> $post['outline'],
    		'superiority'=> $post['superiority'],
    	    'solution_img'=> $post['solution_img'],
    		'app_level1'=>$post['app_level1'],
    		'app_level2'=>$post['app_level2'],	
    		'status'=>(int) $post['status']);
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
    public function createhtmlAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	
    	$viewFolder ="/modules/default/views/scripts/2014/applications/";
    	$path  = APPLICATION_PATH.$viewFolder."home.phtml";
    	if( file_exists( $path ) )
    	{
    		$body = file_get_contents($path);
    		$this->mycommon->createFile($viewFolder,'home_tmp.phtml',$body);
    	}else{
    		echo "文件不存在 $path";
    	}
    	//$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$id,'temp4'=>'更改状态成功，改为:'.$status));
    	//echo Zend_Json_Encoder::encode(array("code"=>0,"message"=>'操作成功'));
    	exit;
    	
    }
}