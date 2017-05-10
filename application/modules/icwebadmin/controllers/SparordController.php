<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/page.php';
class Icwebadmin_SparOrdController extends Zend_Controller_Action
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
    	$this->view->AreaTitle=$this->areaService->getTitle($this->Staff_Area_ID);
    	
    	//加载通用自定义类
    	$this->_mycommon = $this->view->mycommon = new MyAdminCommon();
    	$this->_filter = new MyFilter();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    }
    public function indexAction(){
    	$file = '../application/configs/common.ini';
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$file_handle = fopen($file, "r");
    		$description = $value_new = '';
    		while (!feof($file_handle)) {
    			$line = fgets($file_handle);
    			if(strpos($line,"="))
    			{
    				$value_old = explode('=',$line);
    				foreach($formData as $key=>$value){
    					if($value && $key == trim($value_old[0]) && $value!=trim($value_old[1])){
    						$line = ($key.'='.$value."\r\n");
    						$description .= $line;
    					}
    				}
    			}
    			if($line){
    				$value_new .=$line;
    			}
    		}
    		fclose($file_handle);
    		//写入
    		$stream = fopen($file, "w+");
    		fwrite($stream, $value_new);
    		fclose($stream);
    		$_SESSION['remess'] = '更新成功';
    		//日志
    		if($description){
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp4'=>'更新配置文件common.ini成功','description'=>$description));
    		}
    		$this->_redirect($this->indexurl);
    	}
    	$file_handle = fopen($file, "r");
    	$configArr = array();
    	while (!feof($file_handle)) {
    		$line = fgets($file_handle);
    		if($line){
    			if(strpos($line,"[")==0 && strpos($line,"]"))
    			{
    				$key = str_replace(array('[',']'),array('',''),$line);
    			}
    			if(!strpos($line,";") && strpos($line,";")!==0){
    				$configArr[$key][] = (explode('=',$line));
    			}
    		}
    	}
    	fclose($file_handle);
    	$this->view->configArr = $configArr;
    }
}