<?php
/**
 * 浏览产品记录基本功能
 * 1) 将物品id或part no加入记录
 * 2) 取出记录
 *
 * @author quanshuidingdang
 */

class Prodhistory {
	/**
	 * 构造函数
	 *
	 * @param array
	 */
	private $expired_time = 86400;//一天3600*24
	public function __construct() {
		
	}
	/**
	 * 插入记录
	 */
	public function addhistry($id_no)
	{
		$content_id = array();//1.创建一个数组
		if($id_no){
			$content_id[] = $id_no; //2.对接受到的ID插入到数组中去
			if(isset($_COOKIE['content_id'])) //3.判定cookie是否存在,第一次不存在(如果存在的话)
		    {
			$now_content = str_replace("\\", "", $_COOKIE['content_id']);//(4).您可以查看下cookie,此时如果unserialize的话出问题的,我把里面的斜杠去掉了
			$now = unserialize($now_content); //(5).把cookie 中的serialize生成的字符串反实例化成数组
			foreach($now as $n=>$w) { //(6).里面很多元素,所以我要foreach 出值
				if(!in_array($w,$content_id)) //(7).判定这个值是否存在,如果存在的化我就不插入到数组里面去;
				{
					$content_id[] = $w; //(8).插入到数组
				}
			}
			$content= serialize($content_id); //(9).把数组实例化成字符串
			setcookie("content_id",$content, time()+$this->expired_time); //(10).插入到cookie
		   }else {
			$content= serialize($content_id);//4.把数组实例化成字符串
			setcookie("content_id",$content, time()+$this->expired_time); //5.生成cookie
		   }
		   return true;
		}else return false;
	}

	/**
	 * 获得记录
	 */
	public function gethistry()
	{
		return unserialize(str_replace("\\", "", $_COOKIE['content_id']));
	}
}