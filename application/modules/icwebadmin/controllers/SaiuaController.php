<?php require_once 'Iceaclib/admin/admincommon.php';
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/fun.php';
class Icwebadmin_SaiuaController extends Zend_Controller_Action
{
	private $_adminlogService;
	private $_appModel;
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
    	
    	$this->view->getinfourl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/getinfo";
    	$this->view->editinfourl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/editinfo";
    	$this->view->editruleurl  = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/editrule";
    	$this->view->editappurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/editapp";
    	$this->view->updateallruleurl   = "/icwebadmin/{$this->Section_Area_ID}{$this->Staff_Area_ID}/updateallrule";
    	$this->view->openpicurl   = "/icwebadmin/common/openpic";
    	$this->view->uplodheadcurl= "/icwebadmin/common/uplodhead";
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
    	$this->staff  = new Icwebadmin_Model_DbTable_Staff();
    	$this->section = new Icwebadmin_Model_DbTable_Section();
    	$this->area    = new Icwebadmin_Model_DbTable_Sectionarea();
    	$this->_adminlogService = new Icwebadmin_Service_AdminlogService();
    	$this->_appModel = new Icwebadmin_Model_DbTable_AppCategory();
    }
    public function indexAction(){
    	$this->view->staffall = $this->staff->getAllByWhere("staff_id!=''","department_id ASC");
    }
    public function addAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	//区域
    	$this->view->Section = $section_array = $this->section->getAllByWhere("Section_area_id!=''");
    	$this->view->Area    = $this->getArea($this->area, $section_array);
    	//获取用户
    	$this->view->superior = $this->staff->getAllByWhere("status=1");
    	//获取部门和级别
    	$this->department = new Icwebadmin_Model_DbTable_Department();
    	$this->view->Department=$this->department->getAllByWhere("department_id!=''","department_id DESC");
    	$this->level        = new Icwebadmin_Model_DbTable_Level();
    	$this->view->Level  =$this->level->getAllByWhere("level_id!=''","level_id DESC");
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Staff_ID      = $this->filter->pregHtmlSql($formData['staffid']);
    		$Status        = (int)($formData['status']);
    		$Department_ID = $this->filter->pregHtmlSql($formData['departmentid']);
    		$Level_ID      = $this->filter->pregHtmlSql($formData['levelid']);
    		$superior      = $formData['superior'];
    		$LastName      = $this->filter->pregHtmlSql($formData['lastname']);
    		$FirstName     = $this->filter->pregHtmlSql($formData['firstname']);
    		$tel        = $this->filter->pregHtmlSql($formData['tel']);
    		$ext        = $this->filter->pregHtmlSql($formData['ext']);
    		$phone         = $this->filter->pregHtmlSql($formData['phone']);
    		$Email         = $this->filter->pregHtmlSql($formData['email']);
    		$Head          = $this->filter->pregHtmlSql($formData['uploadimg']);
    		$Head          = $Head==''?'nohead.jpg':$Head;
    		$Right_Rule    = $formData['Right_Rule'];
    		$area_id_str=$rule_str='';
    		if(!empty($Right_Rule))
    		{
    			$i=1;
    			$ruleTmp = array('A','W','R','B');
    			foreach($Right_Rule as $area_id=>$rule)
    			{
    				if(in_array($rule,$ruleTmp))
    				{
    					if($rule!='B'){
    						if($i==1) {
    							$area_id_str = $area_id;
    							$rule_str    =$rule;
    							$i=0;
    						}
    						else {
    							$area_id_str .= ",".$area_id;
    							$rule_str .= ",".$rule;
    						}
    					}
    				}
    			}
    		}
    		$error = 0;$message='';
    		if(!$Department_ID){
    			$message ='请选择部门。<br/>';
    			$error++;
    		}
    		if(!$Level_ID){
    			$message .='请选择级别。<br/>';
    			$error++;
    		}
    		if(!$Staff_ID){
    			$message .='请填写用户名。<br/>';
    			$error++;
    		}
    		if(!$this->filter->checkLength($Staff_ID,3,30)){
    			$message .='用户名长度必须为3-30。<br/>';
    			$error++;
    		}else{
    			$rearray = $this->staff->getRowByWhere("staff_id='{$Staff_ID}'");
    			if(!empty($rearray)){
    				$message .='用户名已经存在。<br/>';
    				$error++;
    			}
    		}
    		if(empty($LastName)){
    			$message .='请填写姓别。<br/>';
    			$error++;
    		}
    		if(empty($FirstName)){
    			$message .='请填写名字。<br/>';
    			$error++;
    		}
    		if(empty($Email)){
    			$message .='请填写Email。<br/>';
    			$error++;
    		}elseif(!$this->filter->checkEmail($Email)){
    			$message .='请填写正确的Email地址。<br/>';
    			$error++;
    		}else{
    			$rearray = $this->staff->getRowByWhere("email='{$Email}'");
    			if(!empty($rearray)){
    				$message .='Email已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$pass = rand(111111,999999);
    			$PassWord = md5(md5($pass));
    			$newid = $this->staff->addStaff(array('staff_id'=>$Staff_ID,
    					'password'=>$PassWord,
    					'status'=>$Status,
    					'department_id'=>$Department_ID,
    					'level_id'=>$Level_ID,
    					'superior'=>$superior,
    					'lastname'=>$LastName,
    					'firstname'=>$FirstName,
    					'tel'=>$tel,
    					'ext'=>$ext,
    					'phone'=>$phone,
    					'email'=>$Email,
    					'head'=>$Head,
    					'staff_area_rule'=>$area_id_str,
    					'right_rule'=>$rule_str,
    					'addtime'=>date('Y-m-d H:i:s'),
    					'updatetime'=>date('Y-m-d H:i:s')),$Staff_ID);
    			if($newid){
    $this->_emailService = new Default_Service_EmailtypeService();
    //发送email
   $link = '<a href="http://'.$_SERVER['HTTP_HOST'].'/icwebadmin/index/login" target="_blank">IC易站后台</a>';		 
   $mess ='</tbody>
        </table><tr>
              <td><table align="center" width="100%" border="0" cellpadding="0" cellspacing="0" style="background:#fff; ">
	<tr>
    	<td align="left" style="padding-bottom:24px; padding-left:10px">
        	<table algin="center" width="698" border="0" cellpadding="0" cellspacing="0" style="background:#fff;">
                <tr>
                	<td align="left">
                    	<table width="698" style="border:1px solid #ddd;border-top:2px solid #f00">
                        	<tr>
                            	<td align="center">
                                	<table style="width:636px;padding:0px 16px; margin-top:10px;text-align:left;">
                                    	<tr>
                                            <td style=" font-size:14px;padding-bottom:10px; font-weight:bold; color:#f00;">尊敬的'.$LastName.$FirstName.'，您好!</td>
                                        </tr>
                                    	<tr>
                                            <td style="color:#666; padding-bottom:8px;font-size:12px;">您已经成功在IC易站后台注册成功，您的用户信息如下（请妥善保管）；请到'.$link.'进行登录</td>
                                        </tr>
                                        <tr>
                                            <td style=" background:#EEF8FF; overflow:hidden; zoom:1; padding:16px; border:1px solid #CEE4F6;font-size:12px;">
                                                <p>用户名:'.$Staff_ID.'</p>
                                                <p>密   码:'.$pass.'</p>
                                                <p style="color:#999;">此邮件由系统自动发出，请勿直接回复。</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table></td></tr>';
    			  $fromname = 'IC易站';
    			  $title    = '欢迎注册IC易站后台';
    			  $myfun = new MyFun();
    			
    			  $emailarr = $this->_emailService->getEmailAddress('add_staff');
    			  $emailcc = $emailbcc = array();
    			  if(!empty($emailarr['cc'])){
    			  	$emailcc = $emailarr['cc'];
    			  }
    			  if(!empty($emailarr['bcc'])){
    			  	$emailbcc = $emailarr['bcc'];
    			  }
    			  $re = $myfun->sendemail($Email, $mess, $fromname, $title,$emailcc,$emailbcc);
    			  //日志
    			  if($re){
    			  	$this->_adminlogService->addLog(array('log_id'=>'M','temp2'=>$newid,'temp3'=>$Staff_ID,'temp4'=>'后台添加用户发送邮件成功'));
    			  	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加用户并发送邮件成功。'));
    			  	exit;
    			  }else{
    			  	$this->_adminlogService->addLog(array('log_id'=>'M','temp1'=>400,'temp2'=>$newid,'temp3'=>$Staff_ID,'temp4'=>'后台添加用户发送邮件失败'));
    			  	echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'添加用户但发送邮件失败。请记住新用户密码：'.$pass));
    			  	exit;
    			  }
    			}else{
    				echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>'添加失败。'));
    				exit;
    			}
    		}
    	}
    }
    //编辑基本信息
    public function editinfoAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($_GET['ID']){
    	    $staffid = $this->filter->pregHtmlSql($_GET['ID']);
    	    $this->view->staffall = $this->staff->getRowByWhere("staff_id='{$staffid}'");
    	    //获取用户
    	    $this->view->superior = $this->staff->getAllByWhere("status=1");
    	    //获取部门和级别
    	    $this->department = new Icwebadmin_Model_DbTable_Department();
    	    $this->view->Department=$this->department->getAllByWhere("department_id!=''","department_id DESC");
    	    $this->level        = new Icwebadmin_Model_DbTable_Level();
    	    $this->view->Level  =$this->level->getAllByWhere("level_id!=''","level_id DESC");
    	    $this->view->staffid = $staffid;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Staff_ID      = $this->filter->pregHtmlSql($formData['staffid']);
    		$Status        = (int)($formData['status']);
    		$Department_ID = $this->filter->pregHtmlSql($formData['departmentid']);
    		$Level_ID      = $this->filter->pregHtmlSql($formData['levelid']);
    		$superior      = $formData['superior'];
    		$LastName      = $this->filter->pregHtmlSql($formData['lastname']);
    		$FirstName     = $this->filter->pregHtmlSql($formData['firstname']);
    		$tel        = $this->filter->pregHtmlSql($formData['tel']);
    		$ext        = $this->filter->pregHtmlSql($formData['ext']);
    		$phone         = $this->filter->pregHtmlSql($formData['phone']);
    		$Email         = $this->filter->pregHtmlSql($formData['email']);
    		$Head          = $this->filter->pregHtmlSql($formData['uploadimg']);
    		$Head          = $Head==''?'nohead.jpg':$Head;
    		$error = 0;$message='';
    		if(!$Department_ID){
    			$message ='请选择部门。<br/>';
    			$error++;
    		}
    		if(!$Level_ID){
    			$message .='请选择级别。<br/>';
    			$error++;
    		}
    		if(empty($LastName)){
    			$message .='请填写姓别。<br/>';
    			$error++;
    		}
    		if(empty($FirstName)){
    			$message .='请填写名字。<br/>';
    			$error++;
    		}
    		if(empty($Email)){
    			$message .='请填写Email。<br/>';
    			$error++;
    		}elseif(!$this->filter->checkEmail($Email)){
    			$message .='请填写正确的Email地址。<br/>';
    			$error++;
    		}else{
    			$rearray = $this->staff->getRowByWhere("staff_id!='{$Staff_ID}' AND email='{$Email}'");
    			if(!empty($rearray)){
    				$message .='Email已经存在。<br/>';
    				$error++;
    			}
    		}
    		if($error){
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>$message));
    			exit;
    		}else{
    			$this->staff->updateById(array('status'=>$Status,
    					'department_id'=>$Department_ID,
    					'level_id'=>$Level_ID,
    					'superior'=>$superior,
    					'lastName'=>$LastName,
    					'firstname'=>$FirstName,
    					'tel'=>$tel,
    					'ext'=>$ext,
    					'phone'=>$phone,
    					'email'=>$Email,
    					'head'=>$Head,
    					'updatetime'=>date('Y-m-d H:i:s')),$Staff_ID);
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}
    	}
    }
    /**
     * 编辑负责应用领域
     */
    public function editappAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($_GET['ID']){
    		$staffid = $this->filter->pregHtmlSql($_GET['ID']);
    		$this->view->StaffInfo = $this->staff->getRowByWhere("staff_id='{$staffid}'");
    		$this->view->provincearr = $this->_appModel->getAllByWhere("level='1' AND status=1");
    		$this->view->provincearr[] = array('id'=>'other','name'=>'其它');
    	}
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$Staff_ID = $formData['staffid'];
    		$app_rule = $formData['app_rule'];
    		$rulestr = '';
    		for($i=0;$i<count($app_rule);$i++){
    			if($app_rule[$i]){
    				if(empty($rulestr)) $rulestr = $app_rule[$i];
    				else $rulestr .= ','.$app_rule[$i];
    			}
    		}
    		$this->staff->updateById(array('app_rule'=>$rulestr,'updatetime'=>date('Y-m-d H:i:s')),$Staff_ID);
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    		exit;
    		
    	}
    }
    //编辑区域权限
    public function editruleAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($_GET['ID']){
    	    $Staff_ID = $this->filter->pregHtmlSql($_GET['ID']);
    		//查询个人资料
    		$rearray = $this->staff->getBySql("SELECT * FROM admin_staff as s,admin_department as d,admin_level as l 
    				WHERE s.staff_id=:staffid AND s.department_id = d.department_id AND s.level_id=l.level_id",array('staffid'=>$Staff_ID));
    		if($rearray) $this->view->StaffInfo = $rearray[0];
    		//区域
    		$this->view->Section = $section_array = $this->section->getAllByWhere("section_area_id!=''");
    		$this->view->Area    = $this->getArea($this->area, $section_array);
    		$this->view->Staff_ID = $Staff_ID;
    	}
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Staff_ID      = $this->filter->pregHtmlSql($formData['staffid']);
    		$Right_Rule    = $formData['Right_Rule'];
    		if(!empty($Right_Rule))
    		{
    			$i=1;$area_id_str=$rule_str='';
    			$ruleTmp = array('A','W','R','B');
    			foreach($Right_Rule as $area_id=>$rule)
    			{
    				if(in_array($rule,$ruleTmp))
    				{
    					if($rule!='B'){
    					  if($i==1) {
    						$area_id_str = $area_id;
    						$rule_str    =$rule;
    						$i=0;
    					  }
    					  else {
    						$area_id_str .= ",".$area_id;
    						$rule_str .= ",".$rule;
    					  }
    					}
    				}
    			}	 	
    			$this->staff->updateById(array('staff_area_rule'=>$area_id_str,
    					'right_rule'=>$rule_str,
    					'updatetime'=>date('Y-m-d H:i:s')),$Staff_ID);
    			//日志
    			$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$Staff_ID,'temp4'=>'更新成功权限','description'=>$area_id_str.'; '.$rule_str));
    			echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'编辑成功。'));
    			exit;
    		}else{
    			echo Zend_Json_Encoder::encode(array("code"=>404, "message"=>'参数不能为空。'));
    			exit;
    		}
    	}
    }
    //ajax获取个人信息
    public function getinfoAction(){
    	$this->_helper->layout->disableLayout();
    	if($this->getRequest()->isPost()){
    		$formData      = $this->getRequest()->getPost();
    		$Staff_ID = $this->filter->pregHtmlSql($formData['Staff_ID']);
    		//查询个人资料
    		$rearray = $this->staff->getBySql("SELECT s.*,d.*,l.*,st.lastname as stln,st.firstname as stfn 
    				FROM admin_staff as s
    				LEFT JOIN admin_level as l ON s.level_id=l.level_id
    				LEFT JOIN admin_department as d ON s.department_id = d.department_id
    				LEFT JOIN admin_staff as st ON s.superior = st.staff_id
    				WHERE s.staff_id=:staffid ",array('staffid'=>$Staff_ID));
    		if($rearray) $this->view->StaffInfo = $rearray[0];
    		//区域
    		$this->view->Section = $section_array = $this->section->getAllByWhere("section_area_id!=''");
    		$this->view->Area    = $this->getArea($this->area, $section_array);
    		//应用领域 其它
    		$this->view->provincearr = $this->_appModel->getAllByWhere("level='1' AND status=1");
    		$this->view->provincearr[] = array('id'=>'other','name'=>'其它');
    		$placeModel = new Icwebadmin_Model_DbTable_Model("lab_place");
    		$this->view->place = $placeModel->getBySql("SELECT * FROM `lab_place` WHERE status=1");
    	}
    }
    /**
	 * 更新区域所有权限
	 */
    public function updateallruleAction(){
    	//更新
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$allstaff = $this->staff->getAllByWhere("status = 1");
    		$description = '';
    		foreach($allstaff as $staff){
    			$rulearr = array_combine(explode(",",$staff['staff_area_rule']),explode(",",$staff['right_rule']));
    			$rulearr[$data['staff_area_id']] = $data['Right_Rule'][$staff['staff_id']];
    			//去空
    			$rulearr = array_filter($rulearr);
    			$staff_area_rule = implode(",",array_keys($rulearr));
    			$right_rule = implode(",",$rulearr);
    			//更新
    			$this->staff->update(array('staff_area_rule'=>$staff_area_rule,'right_rule'=>$right_rule), "staff_id = '".$staff['staff_id']."'");
    			$description .=$staff_area_rule.','.$right_rule.';';
    		}
    		$_SESSION['messages'] = "更新成功.";
    		//日志
    		$this->_adminlogService->addLog(array('log_id'=>'E','temp2'=>$data['staff_area_id'],'temp4'=>'更新成功权限','description'=>$description));
    		$this->_redirect($this->view->updateallruleurl.'/areaid/'.$data['staff_area_id']);
    	}
    	
    	$getareaid = $this->_getParam('areaid');
    	$this->view->area = $this->sectionarea->getRowByWhere("(status=1) AND (staff_area_id='{$getareaid}')");
    	if(empty($this->view->area)) $this->_redirect($this->indexurl);
    	//查询个人资料
    	$this->view->rearray = $this->staff->getBySql("SELECT s.*,d.department as dname,l.level as lname
    				FROM admin_staff as s
    				LEFT JOIN admin_level as l ON s.level_id=l.level_id
    				LEFT JOIN admin_department as d ON s.department_id = d.department_id
    			    WHERE s.status = 1 ORDER BY `department_id` ASC,`level_id` ASC");
    	//4个权限人员
    	$this->view->rulearr = $A = $W = $R = $B = array();
    	
    	foreach($this->view->rearray as $staff){
    		$staff_area_rule = explode(",",$staff['staff_area_rule']);
    		$right_rule      = explode(",",$staff['right_rule']);
    		if($staff_area_rule && $right_rule){
    			if(in_array($getareaid,$staff_area_rule))
    			{
    			   foreach(array_combine($staff_area_rule,$right_rule) as $areaid=>$rule){
    				 if($getareaid == $areaid)
    				 {
    					if($rule == 'A'){
    						$A[] = $staff;
    						break;
    					}elseif($rule == 'W'){
    						$W[] = $staff;
    						break;
    					}elseif($rule == 'R'){
    						$R[] = $staff;
    						break;
    					}	
    				 }
    			   }
    			}else $B[] = $staff;
    		}else{
    			$B[] = $staff;
    		}
    	}
    	$this->view->rulearr['A']=$A;
    	$this->view->rulearr['W']=$W;
    	$this->view->rulearr['R']=$R;
    	$this->view->rulearr['B']=$B;

    }
    /**
     * 首页统计
     */
    public function statisticsAction(){
        $this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->_getParam("staffid")){
    		$this->view->StaffInfo = $this->staff->getRowByWhere("staff_id='".$this->_getParam("staffid")."'");
    	}
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$staff_id   = $formData['staff_id'];
    		$statistics = ($formData['statistics']?implode(',',$formData['statistics']):'');
    		$this->staff->updateById(array('statistics'=>$statistics,'updatetime'=>date('Y-m-d H:i:s')),$staff_id);
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    		exit;
    		
    	}
    }
    /**
     * 实验室负责权限
     */
    public function labAction(){
    	$this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->_getParam("staffid")){
    		$this->view->StaffInfo = $this->staff->getRowByWhere("staff_id='".$this->_getParam("staffid")."'");
    		$placeModel = new Icwebadmin_Model_DbTable_Model("lab_place");
    		$this->view->place = $placeModel->getBySql("SELECT * FROM `lab_place` WHERE status=1");
    	}
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$staff_id   = $formData['staff_id'];
    		$statistics = ($formData['statistics']?implode(',',$formData['statistics']):'');
    		
    		$this->staff->updateById(array('lab_rule'=>$statistics,'updatetime'=>date('Y-m-d H:i:s')),$staff_id);
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    		exit;
    
    	}
    }
    /**
     * 管控用户
     */
    public function controlstaffAction(){
        $this->_helper->layout->disableLayout();
    	if(!$this->mycommon->checkA($this->Staff_Area_ID) && !$this->mycommon->checkW($this->Staff_Area_ID))
    	{
    		echo Zend_Json_Encoder::encode(array("code"=>200, "message"=>"权限不够。"));
    		exit;
    	}
    	if($this->_getParam("staffid")){
    		$this->view->StaffInfo = $this->staff->getRowByWhere("staff_id='".$this->_getParam("staffid")."'");
    	}
    	if($this->getRequest()->isPost()){
    		$formData = $this->getRequest()->getPost();
    		$staff_id   = $formData['staff_id'];
    		$control_staff = $formData['control_staff'];
    		$this->staff->updateById(array('control_staff'=>$control_staff,'updatetime'=>date('Y-m-d H:i:s')),$staff_id);
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>'更新成功。'));
    		exit;
    		
    	}
    }
    private function getArea($areaModel,$section_array)
    {
    	$area_array = array();
    	for($a=0;$a<count($section_array);$a++)
    	{
    	$section=$section_array[$a]["section_area_id"];
    	$where="(status='1') AND section_area_id='".$section."'";
    	$order="CAST(`order_id` AS SIGNED) ASC ";
    	$area_array[$section]=$areaModel->getAllByWhere($where,$order);
    	}
    	return $area_array;
    }
}