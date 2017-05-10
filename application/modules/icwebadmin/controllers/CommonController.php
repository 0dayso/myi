<?php
require_once 'Iceaclib/common/filter.php';
require_once 'Iceaclib/common/kindeditor/json.php';
class Icwebadmin_CommonController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    	$this->_filter = new MyFilter();
    }
   
    /*
     * 产生验证码
    */
    public function createcodeAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$this->commonconfig = Zend_Registry::get('commonconfig');
    	// action body
    	/*
    	 * 初始化
    	*/
    	$border = 0; //是否要边框 1要:0不要
    	$how = 4; //验证码位数
    	$w = $how*21; //图片宽度
    	$h = 27; //图片高度
    	$fontsize = 14; //字体大小
    	$alpha = "23456789abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ"; //验证码内容1:字母
    	$chart = '的一是在了不和有大这主中人上为们地个用工时要动国产以我到他会作来分生对于学下级就年阶义发成部民可出能方进同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批如应形想制心样干都向变关点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫康遵牧遭幅园腔订香肉弟屋敏恢忘衣孙龄岭骗休借丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩';
    	$randcode = ""; //验证码字符串初始化
    	srand((double)microtime()*1000000); //初始化随机数种子
    	 
    	$im = ImageCreate($w, $h); //创建验证图片
    	/*
    	 * 绘制基本框架
    	*/
    	$bgcolor = ImageColorAllocate($im, 255, 255, 255); //设置背景颜色
    	ImageFill($im, 0, 0, $bgcolor); //填充背景色
    	if($border)
    	{
    		$black = ImageColorAllocate($im, 0, 0, 0); //设置边框颜色
    		ImageRectangle($im, 0, 0, $w-1, $h-1, $black);//绘制边框
    	}
    	/*
    	 * 逐位产生随机字符
    	*/
    	if($this->commonconfig->code->icwebadmin==1){
    		$alpha_or_number = 0;
    	}elseif($this->commonconfig->code->icwebadmin==2){
    		$alpha_or_number = 1;
    	}elseif($this->commonconfig->code->icwebadmin==3){
    		$alpha_or_number = mt_rand(0, 1);
    	}else{
    		$alpha_or_number = 0;
    	}
    	if($alpha_or_number){
    		//echo strlen($chart).' <br/>';
    		for($i=0; $i<$how; $i++)
    		{
    			$mtRnd = rand(0,strlen($chart));
    			while ($mtRnd%3!=0)
    			{ 
    				$mtRnd = rand(0,strlen($chart));
    			}
    			$code = substr($chart,$mtRnd,3);
    			$j = !$i ? 2 : $j+20; //绘字符位置
    			$color3 = ImageColorAllocate($im, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)); //字符随即颜色
    			//绘字符
    			imagettftext($im, $fontsize,0,$j, 20, $color3, "../docs/font/simhei.ttf", $code);
    			//ImageChar($im, $fontsize, $j, 3, $code, $color3); //绘字符
    			$randcode .= $code; //逐位加入验证码字符串
    		}
    	}else{
    		for($i=0; $i<$how; $i++){
    	      $which = mt_rand(0, strlen($alpha)-1); //取哪个字符
    	      $code = substr($alpha, $which, 1); //取字符
    	      $j = !$i ? 2 : $j+18; //绘字符位置
    	      $color3 = ImageColorAllocate($im, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)); //字符随即颜色
    	      //绘字符
    	      imagettftext($im, $fontsize,0,$j, 20, $color3, "../docs/font/redocn.otf", $code);
    	      //ImageChar($im, $fontsize, $j, 3, $code, $color3); //绘字符
    	      $randcode .= $code; //逐位加入验证码字符串
    	   }
    	}
    
    	/*
    	* 如果需要添加干扰就将注释去掉
    	*
    	* 以下for()循环为绘背景干扰线代码
    	*/
    	/* + -------------------------------绘背景干扰线 开始-------------------------------------------- + */
    	for($i=0; $i<2; $i++)//绘背景干扰线
    	{
    	$color1 = ImageColorAllocate($im, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255)); //干扰线颜色
    	ImageArc($im, mt_rand(-5,$w), mt_rand(-5,$h), mt_rand(20,300), mt_rand(20,200), 55, 44, $color1); //干扰线
    	}
    	/* + -------------------------------绘背景干扰线 结束-------------------------------------- + */
    	 
    	 
    	/*
    	* 如果需要添加干扰就将注释去掉
    	*
    	* 以下for()循环为绘背景干扰点代码
    	*/
    	/* + --------------------------------绘背景干扰点 开始------------------------------------------ + */
    	 
    	for($i=0; $i<$how*40; $i++)//绘背景干扰点
    	{
    	$color2 = ImageColorAllocate($im, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255)); //干扰点颜色
    	ImageSetPixel($im, mt_rand(0,$w), mt_rand(0,$h), $color2); //干扰点
    	}
    	 
    	/* + --------------------------------绘背景干扰点 结束------------------------------------------ + */
    	 
    	//把验证码字符串写入session  方便提交登录信息时检验验证码是否正确
    	$verifyCode = new Zend_Session_Namespace('verifycode');//使用SESSION存储数据时要设置命名空间
    	$verifyCode->code = strtolower($randcode);//设置值
    	$verifyCode->setExpirationSeconds(60);//命名空间 "user" 将在第一次访问后 60 秒
    			 
    	/*绘图结束*/
    	imagepng($im);
    	imagedestroy($im);
    	/*绘图结束*/
    }
    //打开上传图片页面
    public function openpicAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    }
    //上传头像
    public function uplodheadAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$error = 0;
    		$newfilename = $message = "";
    		if($_GET['headname']) $headname = $_GET['headname'];
    		else $headname ='';
    		$file     = $_FILES['fileToUpload'];
    		$filesize = @filesize($file['tmp_name']);
    		$filetype = @getimagesize($file['tmp_name']);
    		if(!empty($file['error'])){
    			$error++;
    			$message  =$file['error'];
    		}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    			$error++;
    			$message  = '上传图片为空';
    		}elseif(empty($filetype)){
    			$error++;
    			$message  = '请上传图片';
    		}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    		{
    			$error++;
    			$message  = '不允许上传的格式';
    		}elseif($filesize > 205000)
    		{
    			$error++;
    			$message  = '只允许上传200k以下的图片';
    		}else{
    			if($filetype['mime'] == "image/gif") $imgtyp="gif";
    			elseif($filetype['mime'] == "image/png") $imgtyp="png";
    			else $imgtyp="jpg";
    			if(empty($headname))
    			{
    			  $newfilename = time().".".$imgtyp;
    			}else {
    				$pt=strrpos($headname, ".");
    				$retval=substr($headname, 0, $pt);
    				if($retval=='nohead') $newfilename = time().".".$imgtyp;
    				else $newfilename = $retval.".".$imgtyp;
    			}
    			@move_uploaded_file($file["tmp_name"],"upload/admin/head/".$newfilename);
    			@unlink($file);
    			$message = '上传成功';
    		}
    		echo Zend_Json_Encoder::encode(array("error"=>$error,"message"=>$message,"filename"=>$newfilename));
    		exit;
    	}
    }
    //上传图片
    public function uplodimgAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$error = 0;
    		$newfilename = $message = "";
    		$type = $_POST['type'];
    		$path = $_POST['path'];
    		$file     = $_FILES['fileToUpload'];
    		$filesize = @filesize($file['tmp_name']);
    		$filetype = @getimagesize($file['tmp_name']);
    		if(!empty($file['error'])){
    			$error++;
    			$message  =$file['error'];
    		}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    			$error++;
    			$message  = '上传图片为空';
    		}elseif(empty($filetype)){
    			$error++;
    			$message  = '请上传图片';
    		}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    		{
    			$error++;
    			$message  = '不允许上传的格式';
    		}elseif($filesize > 205000)
    		{
    			$error++;
    			$message  = '只允许上传200k以下的图片';
    		}else{
    		    $newfilename = $file['name'];
    		    @move_uploaded_file($file["tmp_name"],$path.$newfilename);
    			@unlink($file);
    			$message = '上传成功';
    		}
    		echo Zend_Json_Encoder::encode(array("error"=>$error,"message"=>$message,"filename"=>$newfilename));
    		exit;
    	}
    }
    //控件 上传图片
    public function editoruplodimgAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_GET['part'];
    	$file     = $_FILES['imgFile'];
    	$file_url = $part . $file['name'];
    	$filesize = @filesize($file['tmp_name']);
    	$filetype = @getimagesize($file['tmp_name']);
    	$error = 0;$message='';
    	if(!empty($file['error'])){
    		$error++;
    		$message  =$file['error'];
    	}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    		$error++;
    		$message  = '上传文件为空';
    	}elseif(empty($filetype)){
    		$error++;
    		$message  = '请选择图片上传';
    	}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    	{
    		$error++;
    		$message  = '不允许上传的格式';
    	}elseif(($filesize/(1024*1024))>4)
    	{
    		$error++;
    		$message  = '只允许上传800k以下的图片';
    	}else{
    	   @move_uploaded_file($file["tmp_name"],$file_url);
     	   @unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0, 'url' => HTTPHOST.'/'.$file_url));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    	
    }
    /*
     * 重命名上传图片
     */
    public function uploadAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_GET['part'];
    	$file     = $_FILES['imgFile'];
    	$ext    = end(explode('.',$file['name']));
    	$filename = uniqid('img_') .".".$ext;
    	$file_url = $part . $filename;
    	$filesize = @filesize($file['tmp_name']);
    	$filetype = @getimagesize($file['tmp_name']);
    	$error = 0;$message='';
    	if(!empty($file['error'])){
    		$error++;
    		$message  =$file['error'];
    	}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    		$error++;
    		$message  = '上传文件为空';
    	}elseif(empty($filetype)){
    		$error++;
    		$message  = '请选择图片上传';
    	}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    	{
    		$error++;
    		$message  = '不允许上传的格式';
    	}elseif($filesize > 820000)
    	{
    		$error++;
    		$message  = '只允许上传800k以下的图片';
    	}else{
    		@move_uploaded_file($file["tmp_name"],$file_url);
    		@unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0, 'url' => HTTPHOST.'/'.$file_url));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    	 
    }
    //控件 上传文件
    public function editoruplodAction(){
    	$loginCheck = new Icwebadmin_Service_LogincheckService();
    	$loginCheck->sessionChecking();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_GET['part'];
    	$file     = $_FILES['imgFile'];
    	$file_url = $part . $file['name'];
    	$filesize = @filesize($file['tmp_name']);
    	$error = 0;$message='';
    	if(!empty($file['error'])){
    		$error++;
    		$message  =$file['error'];
    	}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    		$error++;
    		$message  = '上传文件为空';
    	}else{
    		@move_uploaded_file($file["tmp_name"],$file_url);
    		@unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0, 'url' => HTTPHOST.'/'.$file_url));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    }
    /*
     * 
     */
    public function filemanagerjsonAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part = $_GET['part'];

    	//根目录路径，可以指定绝对路径，比如 /var/www/attached/
    	$root_path = $part;
    	//根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
    	$root_url = HTTPHOST.'/'.$part;
    	//图片扩展名
    	$ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');

    	//根据path参数，设置各路径和URL
    	$current_path = $root_path;//realpath($root_path) . '/';
    	$current_url = $root_url;
    	$current_dir_path = '';
    	$moveup_dir_path = '';
    	echo realpath($root_path);
    	//排序形式，name or size or type
    	$order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);
    	
    	//不允许使用..移动到上一级目录
    	if (preg_match('/\.\./', $current_path)) {
    		echo 'Access is not allowed.';
    		exit;
    	}
    	//最后一个字符不是/
    	if (!preg_match('/\/$/', $current_path)) {
    		echo 'Parameter is not valid.';
    		exit;
    	}
    	//目录不存在或不是目录
    	if (!file_exists($current_path) || !is_dir($current_path)) {
    		echo 'Directory does not exist.';
    		exit;
    	}
    	
    	//遍历目录取得文件信息
    	$file_list = array();
    	if ($handle = opendir($current_path)) {
    		$i = 0;
    		while (false !== ($filename = readdir($handle))) {
    			if ($filename{0} == '.') continue;
    			$file = $current_path . $filename;
    			if (is_dir($file)) {
    				$file_list[$i]['is_dir'] = true; //是否文件夹
    				$file_list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件
    				$file_list[$i]['filesize'] = 0; //文件大小
    				$file_list[$i]['is_photo'] = false; //是否图片
    				$file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
    			} else {
    				$file_list[$i]['is_dir'] = false;
    				$file_list[$i]['has_file'] = false;
    				$file_list[$i]['filesize'] = filesize($file);
    				$file_list[$i]['dir_path'] = '';
    				$file_ext = strtolower(array_pop(explode('.', trim($file))));
    				$file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
    				$file_list[$i]['filetype'] = $file_ext;
    			}
    			$file_list[$i]['filename'] = $filename; //文件名，包含扩展名
    			$file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
    			$i++;
    		}
    		closedir($handle);
    	}
    	
    	
    	usort($file_list, 'cmp_func');
    	
    	$result = array();
    	//相对于根目录的上一级目录
    	$result['moveup_dir_path'] = $moveup_dir_path;
    	//相对于根目录的当前目录
    	$result['current_dir_path'] = $current_dir_path;
    	//当前目录的URL
    	$result['current_url'] = $current_url;
    	//文件数
    	$result['total_count'] = count($file_list);
    	//文件列表数组
    	$result['file_list'] = $file_list;
    	
    	//输出JSON字符串
    	header('Content-type: application/json; charset=UTF-8');
    	$json = new Services_JSON();
    	echo $json->encode($result);
    }
    //获取省
    public function getprovinceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid = (int)($_GET['provinceid']==''?'':$_GET['provinceid']);
    	$province = new Default_Model_DbTable_Province();
    	$provincearr=$province->getAllByWhere("provinceid!=''");
    	echo '<select id="province" name="province" onchange="selectCity()" class="input-medium">
    	<option value="">请选择省</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['provinceid']==$provinceid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['provinceid']."' $select >".$v['province']."</option>";
    	}
    	echo "</select>";
    }
    //获取城市
    public function getcityAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid = (int)$_GET['provinceid'];
    	$cityid = (int)($_GET['cityid']==''?'':$_GET['cityid']);
    	$city = new Default_Model_DbTable_City();
    	$cityarr=$city->getAllByWhere("fatherid='{$provinceid}'");
    	echo '<select id="city" name="city" onchange="selectArea()" class="input-medium">
    	<option value="">请选择市</option>';
    	foreach($cityarr as $k=>$v){
    		if($v['cityid']==$cityid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['cityid']."' $select >".$v['city']."</option>";
    	}
    	echo "</select>";
    }
    //获取地区
    public function getareaAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$cityid = (int)$_GET['cityid'];
    	$areaid = (int)($_GET['areaid']==''?'':$_GET['areaid']);
    	$area = new Default_Model_DbTable_Area();
    	$areaarr=$area->getAllByWhere("fatherid='{$cityid}'");
    	echo '<select id="area" name="area"  class="input-medium">
		<option value="">请选择区</option>';
    	foreach($areaarr as $k=>$v){
    		if($v['areaid']==$areaid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['areaid']."' $select >".$v['area']."</option>";
    	}
    	echo "</select>";
    }
    /*
     * 获取OA国家
    */
    public function getoacountryAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$countryid = $_GET['countryid'];
    	$oa_country_model = new Icwebadmin_Model_DbTable_Model('oa_country');
    	$countryarr=$oa_country_model->getAllByWhere("status='1'");
    	echo '<select id="country" name="country" onchange="selectOaProvince(this.value)">
    	<option value="">请选择省</option>';
    	foreach($countryarr as $k=>$v){
    		if($v['id']==$countryid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    /*
     * 获取OA省
     */
    public function getoaprovinceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$countryid  = $_GET['countryid'];
    	$provinceid = $_GET['provinceid'];
    	$oa_province_model = new Icwebadmin_Model_DbTable_Model('oa_province');
    	$provincearr = $oa_province_model->getAllByWhere("conuntry_id='{$countryid}' AND status=1");
    	echo '<select id="region" name="region" onchange="selectOaCity(this.value)">
    	<option value="">请选择地区</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$provinceid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //获取OA城市
    public function getoacityAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid    = $_GET['provinceid'];
    	$cityid        = $_GET['cityid'];
    	$oa_city_model = new Icwebadmin_Model_DbTable_Model('oa_city');
    	$city = $oa_city_model->getAllByWhere("province_id='{$provinceid}' AND status=1");
    	echo '<select id="city" name="city" onchange="selectOaZipcode(this.value)">
    	<option value="">请选择城市</option>';
    	foreach($city as $k=>$v){
    		if($v['id']==$cityid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //获取oa邮编
    public function getoazipcodeAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$cityid        = $_POST['cityid'];
    	$oa_city_model = new Icwebadmin_Model_DbTable_Model('oa_city');
    	$city = $oa_city_model->getRowByWhere(" id ='$cityid' AND status=1");
    	echo Zend_Json_Encoder::encode(array("zipcode"=>$city['zipcode']));
    }
    
    //产品一级分类
    public function getlevel1Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part_level1 = (int)($_GET['part_level1']==''?'':$_GET['part_level1']);
    	
    	$prodcat = new Icwebadmin_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='1' AND status=1");
    	
    	echo '<select id="part_level1" name="part_level1" onchange="selectlevel1()" class="input-medium">
    	<option value="">请选择一级分类</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$part_level1) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //产品二级分类
    public function getlevel2Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part_level1 = (int)($_GET['part_level1']==''?'':$_GET['part_level1']);
    	$part_level2 = (int)($_GET['part_level2']==''?'':$_GET['part_level2']);
    	$prodcat = new Icwebadmin_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='2' AND parent_id='{$part_level1}' AND status=1");
    	echo '<select id="part_level2" name="part_level2" onchange="selectlevel2()" class="input-medium">
    	<option value="">请选择二级分类</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$part_level2) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //产品三级分类
    public function getlevel3Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part_level2 = (int)($_GET['part_level2']==''?'':$_GET['part_level2']);
    	$part_level3 = (int)($_GET['part_level3']==''?'':$_GET['part_level3']);
    	$prodcat = new Icwebadmin_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='3' AND parent_id='{$part_level2}' AND status=1");
    	echo '<select id="part_level3" name="part_level3" class="input-medium">
    	<option value="">请选择三级分类</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$part_level3) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //应用一级分类
    public function getapp1Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$app_level1 = (int)($_GET['app_level1']==''?'':$_GET['app_level1']);
    	 
    	$prodcat = new Icwebadmin_Model_DbTable_AppCategory();
    	$provincearr=$prodcat->getAllByWhere("level='1' AND status=1");

    	echo '<select id="app_level1" name="app_level1" onchange="selectapp1()" class="input-medium">
    	<option value="">请选择一级分类</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$app_level1) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    //应用二级分类
    public function getapp2Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$app_level1 = (int)($_GET['app_level1']==''?'':$_GET['app_level1']);
    	$app_level2 = (int)($_GET['app_level2']==''?'':$_GET['app_level2']);
    	$prodcat = new Icwebadmin_Model_DbTable_AppCategory();
    	$provincearr=$prodcat->getAllByWhere("level='2' AND parent_id='{$app_level1}' AND status=1");
    	echo '<select id="app_level2" name="app_level2" class="input-medium">
    	<option value="">请选择二级分类</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$app_level2) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    /**
     * 检查公司名称是否已经存在
     */
    public function companycheckAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
        /* RETURN VALUE */
    	$validateValue=$_POST['validateValue'];
    	$validateId   =$_POST['validateId'];
    	$validateError=$_POST['validateError'];
    	/* RETURN VALUE */
    	$arrayToJs = array();
    	$arrayToJs[0] = $validateId;
    	$arrayToJs[1] = $validateError;
    	//检查
    	$userpf = new Icwebadmin_Model_DbTable_UserProfile();
    	$reUser = $userpf->getRowByWhere("companyname='{$validateValue}'");
    	if(!$reUser){		// validate??
    		$arrayToJs[2] = "true";			// RETURN TRUE
    		echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';			// RETURN ARRAY WITH success
    	}else{
    		for($x=0;$x<1000000;$x++){
    			if($x == 990000){
    				$arrayToJs[2] = "false";
    				echo '{"jsonValidateReturn":'.json_encode($arrayToJs).'}';		// RETURN ARRAY WITH ERROR
    			}
    		}
    	}
    }
    /*
     * ajax获取Part NO.
    */
    public function getajaxtagAction(){
    	$this->_prodService = new Icwebadmin_Service_ProductService();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$value =  $this->_prodService->getPartAndBrandLike($this->_filter->pregHtmlSql($_GET['q']));
    	echo Zend_Json::encode($value);
    }
}
//排序
function cmp_func($a, $b) {
	global $order;
	if ($a['is_dir'] && !$b['is_dir']) {
		return -1;
	} else if (!$a['is_dir'] && $b['is_dir']) {
		return 1;
	} else {
		if ($order == 'size') {
			if ($a['filesize'] > $b['filesize']) {
				return 1;
			} else if ($a['filesize'] < $b['filesize']) {
				return -1;
			} else {
				return 0;
			}
		} else if ($order == 'type') {
			return strcmp($a['filetype'], $b['filetype']);
		} else {
			return strcmp($a['filename'], $b['filename']);
		}
	}
}

