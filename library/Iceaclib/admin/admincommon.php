<?php
class MyAdminCommon
{
	 //加密
     function encrypt_aes($input){
		if (!$input){
		  return;
		}	
	    /* Data */ 	
    	$key='IC secert key, web team. strong with dfgdfgd 32 bit';
	    $iv='m3bmwasiv4200909m3bmwasiv4200909'; 	
	    /* Open module, and create IV */ 			
	    $td = mcrypt_module_open('rijndael-256','','cfb','');
		$key = substr($key,0,mcrypt_enc_get_key_size($td));	    
		
    	/* Reinitialize buffers for decryption*/		
		mcrypt_generic_init($td,$key,$iv);

	    /* Encrypt data */		
		$c_t = mcrypt_generic($td,$input);
		
	    /*  Clean up */		
		mcrypt_generic_deinit($td);
		mcrypt_module_close ($td);
		
		/*conversion  store data */
		return base64_encode($c_t);				
	}
	//解密
	function decrypt_aes($input){
		if (!$input){
		  return;
		}	
	    /* Data */
    	$key='IC secert key, web team. strong with dfgdfgd 32 bit';
	    $iv='m3bmwasiv4200909m3bmwasiv4200909'; 
	    /* Open module, and create IV */ 				
	    $td = mcrypt_module_open('rijndael-256','','cfb','');
		$key = substr($key,0,mcrypt_enc_get_key_size($td));	  
		  
    	/* Reinitialize buffers for decryption*/				  
		mcrypt_generic_init($td,$key,$iv);
		
	    /* Decrypt data */			
		$p_t = mdecrypt_generic($td,base64_decode($input));
		
		/* Clean up */
		mcrypt_generic_deinit($td);
		mcrypt_module_close ($td);
		return trim($p_t);	
	}
	//获得加密后的hashkey，用于发邮件
	public function encryptVerification($uname,$keyone){
		//加密并转意
		$hashkey = rawurlencode($this->encrypt_aes($keyone.','.$uname));
		return $hashkey;
	}
	//解密hashkey
	public function decryptVerification($hashkey){
		$hashkey = $this->decrypt_aes(rawurldecode($hashkey));
		return $hashkey;
	}
    //A权限检查
	public function checkA($Staff_Area_ID)
	{	
		if($_SESSION['right_rule'][$Staff_Area_ID]=="A") return TRUE;
		else return FALSE;
	}
	//W权限检查
	public function checkW($Staff_Area_ID)
	{
		if($_SESSION['right_rule'][$Staff_Area_ID]=="W") return TRUE;
		else return FALSE;
	}
	//R权限检查
	public function checkR($Staff_Area_ID)
	{
		if($_SESSION['right_rule'][$Staff_Area_ID]=="R") return TRUE;
		else return FALSE;
	}
	//B权限检查
	public function checkB($Staff_Area_ID)
	{
		if($_SESSION['right_rule'][$Staff_Area_ID]=="B") return TRUE;
		else return FALSE;
	}
	//创建文件夹
	public function createFolder($folder)
	{
		$ok=0;
		$success=APPLICATION_PATH.$folder;
		if (!empty($folder))
		{	
			if(!is_dir(APPLICATION_PATH.$folder)) //判断是否存在
			{
				if(mkdir(APPLICATION_PATH.$folder,0777))//创建
					$ok=$success;
			}else $ok=$success;
		}
		return $ok;
	}
	//添加文件
	public function createFile($folder,$filename,$inserttxt)
	{
		$filename=APPLICATION_PATH.$folder.$filename;
		if(!file_exists($filename))
		{
			$handle=fopen($filename,"w+");
			fwrite($handle,$inserttxt);
			fclose($handle);
			return TRUE;
		}
	}
	//添加controllers的内容
	public function getControllersTxt($sectionid,$areaid)
	{
	$insert_txt='<?php require_once \'Iceaclib/admin/admincommon.php\';
require_once \'Iceaclib/common/filter.php\';
require_once \'Iceaclib/common/page.php\';
class Icwebadmin_'.$sectionid.$areaid.'Controller extends Zend_Controller_Action
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
    }
    public function indexAction(){
    
    }
}';
		return $insert_txt;
	}
	//获取adminview插入的文本
	function getViewsTxt()
	{
		$insert_txt='
	<div class="conmian">
	<h1><?php echo $this->AreaTitle;?></h1>
				<div id="message_sess" style="display:none" class="message_sess"> 
          <div title="关闭" class="but" onclick="document.getElementById(\'message_sess\').style.display=\'none\'">&nbsp;</div>
          <div id="alert_message"></div> 
          </div>
	  <!-- 需要填写的区域-->
      <h2><?php echo "工作区域";?></h2>
      <br/>
      <!-- 结束区域-->
	</div>';
		return $insert_txt;
	}
}