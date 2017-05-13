<?php
require_once 'Iceaclib/common/fun.php';
require_once 'Iceaclib/default/common.php';
require_once 'Iceaclib/common/filter.php';
class CommonController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    	$this->filter = new MyFilter();
    	$this->view->fun =$this->fun =new MyFun();
    	$this->_defaultlogService = new Default_Service_DefaultlogService();
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
    	$w = $how*18; //图片宽度
    	$h = 30; //图片高度
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
    	if($this->commonconfig->code->default==1){
    		$alpha_or_number = 0;
    	}elseif($this->commonconfig->code->default==2){
    		$alpha_or_number = 1;
    	}elseif($this->commonconfig->code->default==3){
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
    			$j = !$i ? 5 : $j+16; //绘字符位置
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
    	      $j = !$i ? 4 : $j+14; //绘字符位置
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
    //获取省
    public function getprovinceAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid = (int)($_GET['provinceid']==''?'':$_GET['provinceid']);
    	$province = new Default_Model_DbTable_Province();
    	$provincearr=$province->getAllByWhere("provinceid!=''");
    	echo '<select id="province" name="province" onchange="selectCity()">
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
    	echo '<select id="city" name="city" onchange="selectArea()">
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
    	echo '<select id="area" name="area" onchange="setArea(this.value)">
		<option value="">请选择区</option>';
		foreach($areaarr as $k=>$v){
			if($v['areaid']==$areaid) $select="selected";
			else $select="";
			echo "<option value='".$v['areaid']."' $select >".$v['area']."</option>";
		}
		echo "</select>";
    }
    //获取省
    public function getprovince2Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid = (int)($_GET['provinceid']==''?'':$_GET['provinceid']);
    	$province = new Default_Model_DbTable_Province();
    	$provincearr=$province->getAllByWhere("provinceid!=''");
    	echo '<select id="province2" name="province2" onchange="selectCity2()">
    	<option value="">请选择省</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['provinceid']==$provinceid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['provinceid']."' $select >".$v['province']."</option>";
    	}
    	echo "</select>";
    }
    //获取城市
    public function getcity2Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$provinceid = (int)$_GET['provinceid'];
    	$cityid = (int)($_GET['cityid']==''?'':$_GET['cityid']);
    	$city = new Default_Model_DbTable_City();
    	$cityarr=$city->getAllByWhere("fatherid='{$provinceid}'");
    	echo '<select id="city2" name="city2" onchange="selectArea2()">
    	<option value="">请选择市</option>';
    	foreach($cityarr as $k=>$v){
    		if($v['cityid']==$cityid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['cityid']."' $select >".$v['city']."</option>";
    	}
    	echo "</select>";
    }
    //获取地区
    public function getarea2Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$cityid = (int)$_GET['cityid'];
    	$areaid = (int)($_GET['areaid']==''?'':$_GET['areaid']);
    	$area = new Default_Model_DbTable_Area();
    	$areaarr=$area->getAllByWhere("fatherid='{$cityid}'");
    	echo '<select id="area2" name="area2">
		<option value="">请选择区</option>';
    	foreach($areaarr as $k=>$v){
    		if($v['areaid']==$areaid) $select="selected";
    		else $select="";
    		echo "<option value='".$v['areaid']."' $select >".$v['area']."</option>";
    	}
    	echo "</select>";
    }
    //产品一级分类
    public function getlevel1Action(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part_level1 = (int)($_GET['part_level1']==''?'':$_GET['part_level1']);
    	 
    	$prodcat = new Default_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='1' AND status=1");
    	 
    	echo '<select id="part_level1" name="part_level1" onchange="selectlevel1()" class="input-medium">
    	<option value="all">一级分类-全部</option>';
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
    	$prodcat = new Default_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='2' AND parent_id='{$part_level1}' AND status=1");
    	echo '<select id="part_level2" name="part_level2" onchange="selectlevel2()" class="input-medium">
    	<option value="all">二级分类-全部</option>';
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
    	$prodcat = new Default_Model_DbTable_ProdCategory();
    	$provincearr=$prodcat->getAllByWhere("level='3' AND parent_id='{$part_level2}' AND status=1");
    	echo '<select id="part_level3" name="part_level3" class="input-medium">
    	<option value="all">三级分类-全部</option>';
    	foreach($provincearr as $k=>$v){
    		if($v['id']==$part_level3) $select="selected";
    		else $select="";
    		echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    	}
    	echo "</select>";
    }
    /*
     * 获取应用
     */
    public function getappAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$level = (int)$_GET['level'];
    	$app_1_id = (int)$_GET['app_1_id'];
    	$app_2_id = (int)$_GET['app_2_id'];
    	$appModel = new Default_Model_DbTable_AppCategory();
    	$apparr=$appModel->getAllByWhere("status='1'");
    	//一级应用
    	if($level == 1){
    		echo '<select id="app_1" name="app_1" onchange="setApp()">';
    		echo '<option value="">请选择</option>';
			foreach($apparr as $k=>$v){
				if($v['level'] == 1){
				   if($v['id']==$app_1_id) $select="selected";
				   else $select="";
				   echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
				}
			}
			echo "</select>";
    	}elseif($level == 2){
    		echo '<select id="app_2" name="app_2">';
    		echo '<option value="">请选择</option>';
    		foreach($apparr as $k=>$v){
    			if($v['level'] == 2){
    				if($v['parent_id']==$app_1_id){
    				  if($v['id']==$app_2_id) $select="selected";
    				  else $select="";
    				  echo "<option value='".$v['id']."' $select >".$v['name']."</option>";
    				}
    			}
    		}
    		echo "</select>";
    	}
    }
    /*
     * 上传转账回执单类型检查
     */
    public function checkfileAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$error = 0;
    		$newfilename = $message = "";
    		$file = $_FILES['fileToUpload'];
    		$filetype = @getimagesize($file['tmp_name']);
    		if(!empty($file['error'])){
    			$error++;
    			$message  ='文件错误';
    		}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    			$error++;
    			$message  = '请上传附件';
    		}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    		{
    			$error++;
    			$message  = '不允许上传的格式';
    		}elseif(($file["size"]/(1024*1024))>1) //大于8M
    		{
    			$error++;
    			$message  = '文件大少超过1M';
    		}else{
    			$message  = '格式允许';
    		}
    		echo Zend_Json_Encoder::encode(array("error"=>$error,"message"=>$message));
    		exit;
    	}
    }
    //上传文件
    public function uplodfileAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_GET['part'];
    	$newname  = $_GET['newname'];
    	$id = $_POST['id'];
    	$file     = $_FILES[$id];
    	if($newname){
    		$file_name = $newname.'.'.$this->filter->extend($file['name']);
    	}else{
    		$file_name = $file['name'];
    	}
    	$error = 0;$message='';
    	//如果不存在文件夹，建立文件夹
    	if(!is_dir($part)) //判断是否存在
    	{
    		if(!mkdir($part))//创建
    		{
    			$error++;
    			$message  = '文件夹创建失败';
    		}else{
    			@copy("upload/default/company_annex/index.html",$part.'index.html');
    		}
    	}
    	$file_url = $part.$file_name;
    	if(!empty($file['error'])){
    		$error++;
    		$message  =$file['error'];
    	}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    		$error++;
    		$message  = '上传文件为空';
    	}elseif(($file["size"]/(1024*1024))>3) //大于3M
    	{
    		$error++;
    		$message  = '文件大少超过3M';
    	}else{
    		@move_uploaded_file($file["tmp_name"],$file_url);
    		@unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0,'filename'=>$file_name,'fileurl'=>$file_url, 'url' => HTTPHOST.'/'.$file_url));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    }
    //上传图片
    public function uplodimgAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_POST['part'];
    	$newname  = $_POST['newname'];
    	$file     = $_FILES['fileToUpload'];
    	if($newname){
    		$file_name = $newname.'.'.$this->filter->extend($file['name']);
    	}else{
    		$file_name = $file['name'];
    	}
    	$file_url = $part.$file_name;
    	$error = 0;$message='';
        $filetype = @getimagesize($file['tmp_name']);
    		if(!empty($file['error'])){
    			$error++;
    			$message  ='文件错误';
    		}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    			$error++;
    			$message  = '请上传附件';
    		}elseif(($filetype['mime'] != "image/gif") && ($filetype['mime'] != "image/jpeg") && ($filetype['mime'] != "image/png") && ($filetype['mime'] !="image/x-png") && ($filetype['mime'] != "image/pjpeg"))
    		{
    			$error++;
    			$message  = '不允许上传的格式';
    		}elseif(($file["size"]/(1024*1024))>1) //大于8M
    		{
    			$error++;
    			$message  = '文件大少超过1M';
    		}else{
    		@move_uploaded_file($file["tmp_name"],$file_url);
    		@unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0,'message' => '上传成功','filename'=>$file_name));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    }
    //上传文件
    public function uplodannexAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$part     = $_POST['part'];
    	$newname  = $_POST['newname'];
    	$type     = $_POST['type'];
    	$file     = $_FILES["fileToUpload_$type"];
    	if($newname){
    		$file_name = $newname.'.'.$this->filter->extend($file['name']);
    	}else{
    		$file_name = $file['name'];
    	}
    	$file_url = $part.$file_name;
    	$error = 0;$message='';
    	$filetype = @getimagesize($file['tmp_name']);
    	if(!empty($file['error'])){
    		$error++;
    		$message  ='文件错误';
    	}elseif(empty($file['tmp_name']) || $file['tmp_name'] == 'none'){
    		$error++;
    		$message  = '请上传附件'.$file['tmp_name'];
    	}elseif(($file["size"]/(1024*1024*8))>1) //大于8M
    	{
    		$error++;
    		$message  = '文件大少超过8M';
    	}else{
    		@move_uploaded_file($file["tmp_name"],$file_url);
    		@unlink($file);
    	}
    	if(!$error){
    		echo Zend_Json_Encoder::encode(array('error' => 0,'message' => '上传成功','filename'=>$file_name));
    	}else{
    		echo Zend_Json_Encoder::encode(array('error' => 1, 'message' => $message));
    	}
    	exit;
    }
    //记录日记
    public function logAction()
    {
//     	$this->_helper->layout->disableLayout();
//     	$this->_helper->viewRenderer->setNoRender();
//     	$this->_defaultlogService = new Default_Service_DefaultlogService();
//     	$this->_defaultlogService->addViewLog($this->getRequest()->getParams());
    }
    //记录日记
    public function viewlogAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if(isset($_SESSION['userInfo']['uidSession'])){
    	  if(!isset($_SESSION['viewnumber_tmp'])){
    		if(!isset($_SESSION['viewnumber'])){
    	       $_SESSION['viewnumber'] = 1;
    	    }else{
    		   $_SESSION['viewnumber']++;
    	    }
    	  }
    	  if(isset($_SESSION['viewnumber']) && $_SESSION['viewnumber']>=6){
    	     //添加积分
    	     $this->_scoreService = new Default_Service_ScoreService();
    	     $this->_scoreService->addScore('viewpage');
    	     $_SESSION['viewnumber_tmp'] = 1;
    	     unset($_SESSION['viewnumber']);
    	  }
    	}
    }
    //百度分享获取积分
    public function bdshareAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if(isset($_SESSION['userInfo']['uidSession'])){
    	//添加积分
    	$this->_scoreService = new Default_Service_ScoreService();
    	$data = $this->getRequest()->getParams();
    	$this->_scoreService->addScore('share',1,$data['page']);
    	}
    }
    //记录日记js
    public function logjsAction()
    {
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	$logjs = '
    var f = document,
    i = window,
    m = navigator,
    n = f.location,
    p = i.screen,
    h = encodeURIComponent,
    l = decodeURIComponent,
    k = "https:" == n.protocol ? "https:": "http:",
    g = function(a, c , obj) {
        try {
            var b = [];
            b.push("siteid='.($_GET['siteid']?$_GET['siteid']:1).'");
			b.push("status=" + h(a.status));
            b.push("name=" + h(a.name));
            b.push("msg=" + h(a.message));
            b.push("r=" + h(f.referrer));
            b.push("page=" + h(n.href));
            b.push("agent=" + h(m.userAgent));
            b.push("ex=" + h(c));
            b.push("rnd=" + Math.floor(2147483648 * Math.random()));
			b.push("text=" + h(obj.text()));
            b.push("title=" + h(obj.attr("title")));
			b.push("href=" + h(obj.attr("href")));
			b.push("target=" + h(obj.attr("target")));
			b.push("mid=" + h(obj.attr("id")));
			b.push("class=" + h(obj.attr("class")));
			b.push("alt=" + h(obj.attr("alt")));
			b.push("src=" + h(obj.attr("src")));
			b.push("rev=" + h(obj.attr("rev")));
			b.push("rel=" + h(obj.attr("rel")));
            var str = "'.HTTPHOST.'/common/log?" + b.join("&").replace("%3C", "(|");
            if(obj.attr("target")!="_blank"){
            	 (new Image).src = str;
				 window.onunload = function(){(new Image).src = str;return true;}
            }else{
			    (new Image).src = str;
			}
        } catch(d) {}
    };
    v = function() {
        try {
            var b = [];
            b.push("r=" + h(f.referrer));
            b.push("page=" + h(n.href));
            b.push("agent=" + h(m.userAgent));
            b.push("rnd=" + Math.floor(2147483648 * Math.random()));
            var str = "'.HTTPHOST.'/common/viewlog?" + b.join("&").replace("%3C", "(|");
			(new Image).src = str;
        } catch(d) {}
    };
     sh = function() {
        try {
            var b = [];
            b.push("r=" + h(f.referrer));
            b.push("page=" + h(n.href));
            b.push("agent=" + h(m.userAgent));
            b.push("rnd=" + Math.floor(2147483648 * Math.random()));
            var str = "'.HTTPHOST.'/common/bdshare?" + b.join("&").replace("%3C", "(|");
			(new Image).src = str;
        } catch(d) {}
    };
     //share
     var shtmp = 0;
	 $(function(){
		$("#bdshare_l_c").click(function(){
             sh();shtmp=1;
        });
        $("#bdshare").click(function(){
             if(shtmp==0) sh();
        });
     })
    //viewlog
    v();
    //end q.prototype
	$(".logclick").click(function(){
		try {
			var r=new Object();
			r.status = "200";
			r.name   = "click";
			r.message= "ok";
            g(r, "ok" , $(this))
        } catch(r) {
			r.status = "404";
            g(r, "main failed" , $(this))
        }
	});
	$(".loghover").hover(function(){
		try {
			var r=new Object();
			r.status = "200";
			r.name   = "hover";
			r.message= "ok";
            g(r, "ok" , $(this))
        } catch(r) {
			r.status = "404";
            g(r, "main failed" , $(this))
        }
	});
    function logclickaction(obj){
       try {
			var r=new Object();
			r.status = "200";
			r.name   = "click";
			r.message= "ok";
            g(r, "ok" , obj)
        } catch(r) {
			r.status = "404";
            g(r, "main failed" , obj)
        }
    }';
    	echo $logjs;
    }
    /**
     * 积分左菜单
     */
    public function leftmenuAction(){
    	$this->_helper->layout->disableLayout();
    	$this->_scoreService = new Default_Service_ScoreService();
    	//获取是否已经签订
    	$this->view->jifenview = $this->_scoreService->checkgetscore('jifenview',$_SESSION['userInfo']['uidSession']);
    	$this->view->viewpage  = $this->_scoreService->checkgetscore('viewpage',$_SESSION['userInfo']['uidSession']);
    	$this->view->shareview = $this->_scoreService->checkgetscore('share',$_SESSION['userInfo']['uidSession']);
    	//获取已经分享获得的积分
    	$this->_giftservice = new Default_Service_GiftService();
    	$this->view->sharenum = $this->_giftservice->getshareNum($_SESSION['userInfo']['uidSession']);
    }
    /**
     * ajax获取型号相关推荐，搜索输入框
     */
    public function getsearchshowAction(){
    	$this->_helper->layout->disableLayout();
    	$this->view->partno = $_GET['q'];
    }
    /**
     * 签订获取积分
     */
    public function registrAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_scoreService = new Default_Service_ScoreService();
    	$re = $this->_scoreService->addScore('jifenview');
    	if($re>0){
    		echo Zend_Json_Encoder::encode(array("code"=>0, "message"=>"签到成功，您获得{$re}积分"));
    		exit;
    	}else{
    		echo Zend_Json_Encoder::encode(array("code"=>100, "message"=>'签到失败'));
    		exit;
    	}
    }
    /**
     * 发送邮件邀请
     */
    public function sendinviteAction(){
    	//登录检查
    	$this->common = new MyCommon();
    	$this->common->loginCheck();
    	$this->_helper->layout->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	if($this->getRequest()->isPost()){
    		$data = $this->getRequest()->getPost();
    		$error= 0;$mess='';
    		$emailto = array();
    		$data['email'] = str_replace(array(';','；',',','，'), array(' ',' ',' ',' '), $data['email']);
            if(strpos($data['email'],' '))  $email = explode(' ',$data['email']);
    	    else  $email = array($data['email']);
    		if(count($email) > 5) {
    			$error++;
    			$mess = '邮箱地址不要超过5个';
    		}
    		foreach($email as $e){
    			if($e && !$this->filter->checkEmail(($e))){
    				$error++;
    				$mess = '请输入正确的邮箱地址';
    				break;
    			}elseif($e){
    				$emailto[] = trim($e);
    			}
    		}
    		if(empty($emailto)){$error++;$mess = '请输入邮箱地址';}
    		if(strtolower($data['verifycode']) != $_SESSION['verifycode']['code'])
    		{
    			$error++;
    			$mess = '请输入正确的验证码';
    		}
    		if(empty($data['invitecon'])){
    			$error++;
    			$mess = '请输入邀请内容';
    		}
    		if($error){
    			$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['email'],'temp4'=>'发送邀请邮件失败','description'=>$mess.'||'.implode($emailto,',')));
    			echo Zend_Json_Encoder::encode(array("code"=>100,"mess"=>$mess));
    			exit;
    		}else{
    			$fromname = 'IC易站';
    			$title    = '您的好友邀请您注册IC易站账号';
    			$ivurl = HTTPHOST."/user/register?invitekey=".$this->fun->encryptVerification($_SESSION['userInfo']['uidSession']);
    			$mess ='</tbody>
              </table><tr>
              <td valign="top" bgcolor="#ffffff" align="center"><table cellspacing="0" border="0" cellpadding="0" width="730" style="font-family:\'微软雅黑\';">
                  <tbody>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="0" border="0" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b;">
                          <tr>
                            <td><div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">您的好友('.$_SESSION['userInfo']['unameSession'].')邀请您注册IC易站账号。请点击下面链接进行注册：</div></td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                      <td valign="middle" ><table cellpadding="0" cellspacing="10" border="0" width="730" style="text-align:left; font-size:12px; line-height:20px; font-family:\'微软雅黑\';color:#5b5b5b; border:1px #c4d1d7 solid;background:#f7fdff;border-collapse:separate;border-spacing:10px;">
                          <tr>
                            <td>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">'.$data['invitecon'].'</div>
                            		<div style="height:10px;padding:0; margin:0;font-size:0; line-height:10px ">&nbsp;</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">如果您无法点击此链接，请将它复制到浏览器地址后访问。</div>
                              <div style="padding:3px 0;margin:0;color:#5b5b5b;font-family:\'微软雅黑\';">此邮件由系统自动发出，请勿直接回复。</div>
                            </td>
                          </tr>
                        </table></td>
                    </tr>
                    <tr>
                  </tbody>
                </table></td>
            </tr>';
    			$re = $this->fun->sendemail($emailto, $mess, $fromname, $title,'');
    			if($re){
    				$this->_defaultlogService->addLog(array('log_id'=>'E','temp2'=>$data['email'],'temp4'=>'发送邀请邮件成功','description'=>$ivurl.'||'.implode($emailto,',')));
    				echo Zend_Json_Encoder::encode(array("code"=>0,"mess"=>'发送邀请邮件成功'));
    				exit;
    			}else{
    				$this->_defaultlogService->addLog(array('log_id'=>'E','temp1'=>400,'temp2'=>$data['email'],'temp4'=>'发送邀请邮件失败','description'=>'发邮件失败'.'||'.implode($emailto,',')));
    				echo Zend_Json_Encoder::encode(array("code"=>100,"mess"=>'发邮件失败'));
    				exit;
    			}
    		}
    	}
    }
}