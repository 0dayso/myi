<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_NeNewsController extends Zend_Controller_Action
{
	private $_filter;
	private $_mycommon;
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
    	$this->mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	$this->view->ajax_url = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/getajaxtag";
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    	$this->_model = new Icwebadmin_Model_DbTable_News();
    }
    public function indexAction(){
    	$perpage=20;
    	$total = $this->_model->getTotal();
    	$page_ob = new Page(array('total'=>$total,'perpage'=>$perpage));
    	$offset  = $page_ob->offset();
    	$this->view->page_bar= $page_ob->show(6);
    	$where[] = 'news_type_id !=3';
		if($_GET['news_type_id']){
			$news_type_id = (int) $_GET['news_type_id'];
			$where[] = 'a.news_type_id='.$news_type_id;
		}
		if($_GET['app_level1']){
			$app_level1 = (int) $_GET['app_level1'];
			$where[] = 'a.app_level1='.$app_level1;
		}    	
		
		if(isset($_GET['status']) && $_GET['status'] !='' ){
			$status = (int) $_GET['status'];
			$where[] = 'a.status='.$status;
		}
		
		if($_GET['q'])
		{
			$q = $_GET['q'];
			$where[] = " a.title like'%$q%' OR a.keyword  like '%$q%'";
		}		
    	$data = $this->_model->getAll($offset,$perpage,$where);
    	$this->view->data = $data;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    
    public function addAction(){
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$request = $this->getRequest();
    	if($request->isPost()){
    		$weibodata = $data = $this->_process($request->getPost());
    		unset($data['sinaweibo']);
    		unset($data['qqweibo']);
    		unset($data['weibocontent']);
    		unset($data['weibopic']);
    		$id = $this->_model->add($data);
    		
    		$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/news-'.$id.'.html';
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
    		
    		$message = "Records added! ";
    		$this->_helper->flashMessenger->addMessage($message);
    		$this->_redirect($this->indexurl);
    	}    	
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    
    public function editAction(){
    	
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo "权限不够。";
    		exit;
    	}
    	$id =(int) $this->getRequest()->getParam('id');
    	if(!$id) $this->_redirect($this->indexurl);
    	$request = $this->getRequest();
    	if($request->isPost()){
    		$weibodata = $data = $this->_process($request->getPost());
    		unset($data['sinaweibo']);
    		unset($data['qqweibo']);
    		unset($data['weibocontent']);
    		unset($data['weibopic']);
    		$this->_model->updateById($data,$id);
    		
    		$weibocontent = $weibodata['weibocontent'].HTTPHOST.'/news-'.$id.'.html';
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
    		
    		$message = "Records #{$id} updated! ";
    		$this->_helper->flashMessenger->addMessage($message);
    		$this->_redirect($this->view->url());
    	}    	
    	$data = $this->_model->getOneById($id);
    	$product_model = new Icwebadmin_Model_DbTable_Product();
    	$part_no = ($data['part_id']) ? $product_model->getBySql("select id,part_no from product where id in($data[part_id])") : array();
    	$this->view->part_no = $part_no;
    	$this->view->data = $data;
    	$this->view->messages = $this->_helper->flashMessenger->getMessages();
    }
    
    public function deleteAction(){
    	
    }
    
    public function activeAction(){
    	$data = array(
    			'modified'=>time(),
    	);
    	$id =(int) $this->getRequest()->getParam('id');
    	if($this->getRequest()->getParam('flag')!=null)
    	{
    		$data['status'] = $this->getRequest()->getParam('flag');
    	}
    	if($this->getRequest()->getParam('home')!=null)
    	{
    		$data['home'] = $this->getRequest()->getParam('home');
    	}
    	if(!$id) $this->_redirect($this->indexurl);
    	$this->_model->activeById($id, $data);
    	$message = "Records updated.";
    	$this->_helper->flashMessenger->addMessage($message);
    	$this->_redirect($this->indexurl);	
    }
    /*
     * ajax获取Part NO.
    */
    public function getajaxtagAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$value =  $this->_prodService->getPartLike($this->_filter->pregHtmlSql($_GET['q']));
    	echo Zend_Json::encode($value);
    }
    
    public function _process($post)
    {
    	$error = 0;$message = '';
    	if(!$post['title']){
    		$error++;
    		$message .= "请输入标题.<br/>";
    	}
    	if(!$post['author']){
    		$error++;
    		$message .= "请输入作者.<br/>";
    	}    	
    	if(!$post['source']){
    		$error++;
    		$message .= "请输入来源.<br/>";
    	}    	
    	if(!$post['keyword']){
    		$error++;
    		$message .= "请输入关键字.<br/>";
    	}    	 	
    	if(!$post['news_type_id']){
    		$error++;
    		$message .= "请选择资讯类型.<br/>";
    	}   
    	if(!$post['app_level1']){
    		$error++;
    		$message .= "请选择应用分类.<br/>";
    	}    
    	if(!$post['description']){
    		$error++;
    		$message .= "请输入资讯简介.<br/>";
    	}    		 	
    	if(!$post['content']){
    		$error++;
    		$message .= "请输入资讯详细.<br/>";
    	}    
    	if($error){
    		$this->_helper->flashMessenger->addMessage($message);
    		$_SESSION['post'] = $post;
    		$this->_redirect($this->view->url());
    	}else{
    		//替换 为了处理图片
    		$post['content'] = str_replace('\\','',$post['content']);    		
    		unset($post['related_ic']);
    		$post['part_id'] = ($post['part_id']) ? implode(',',array_values($post['part_id'])) : '';
    		$post['created']  = time();
    		$post['modified']  = time();
    		return $post;
    	}
    	
    }
    
}