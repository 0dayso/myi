<?php
class MyFilter
{
	/*
	 * 检查是否为大写字母
	 */
	public function checkUpper($str){
	  if (preg_match("/[A-Z]$/", $str)){
		return true;
	  } else {
		return false;
	  }
	}
	/*
	 * 检查是否为大写字母开头的字符串
	 */
	public function checkStartUpper($str){
		if (preg_match("/^[A-Z][a-z]+$/", $str)){
			return true;
		} else {
			return false;
		}
	}
	/*
	 * 检查字符串长度
	 */
	public function checkLength($str,$min,$max){
	  $strLength = $this->getlength($str);
	  if($strLength >= $min && $strLength <= $max){
		return true;
	  } else {
		return false;
	  }
	}
	/**
	 * 检查中文字符串长度
	 */
	public function getlength($str)
	{
		if(empty($str)){
			return 0;
		}
		if(function_exists('mb_strlen')){
			return mb_strlen($str,'utf-8');
		}
		else {
			preg_match_all("/./u", $str, $ar);
			return count($ar[0]);
		}
	}
	/*
	 * 检查英文字母
	 */
	public function checkLetter($str){
	    if (preg_match("/^[a-zA-Z]+$/", $str)){
			return true;
		} else {
			return false;
		}
	}
	/*
	 * 检查上传公司附件格式
	 */
	public function checkComFile($str,$file_name){
		$arr = array('image/gif','image/jpeg','image/pjpeg','image/png','image/x-png','application/zip');
		if (in_array($str, $arr)){
			return true;
		} else {
			if($file_name)
			{
				$arr2=array('zip','rar','pdf');
				$extend = $this->extend($file_name);
				if (in_array($extend, $arr2)) return true;
				else return false;
			}else return false;
		}
	}
	/*
	 * 检查Email
	 */
	function checkEmail($email) {
		// First, we check that there's one @ symbol, and that the lengths are right
		if (!@ereg("[^@]{1,64}@[^@]{1,255}", $email)) {
			// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
			return false;
		}
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		for ($i = 0; $i < sizeof($local_array); $i++) {
			if (!@ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
				return false;
			}
		}
		if (!@ereg("^\\[?[0-9\\.]+\\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) {
				return false; // Not enough parts to domain
			}
			for ($i = 0; $i < sizeof($domain_array); $i++) {
				if (!@ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
					return false;
				}
			}
		}
		return true;
	}
	/*
	 * 过滤html
	 */
	public function pregHtml($str){
		//先把内容全部反编译过来再过滤
		$getfilter="'|<[^>]*?>|^\\+\\/v(8|9)|\\b(and|or)\\b.+?(>|<|=|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
		$postfilter="^\\+\\/v(8|9)|\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|<\\s*img\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
		$cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
		$str = preg_replace("/".$getfilter."/is","",$str);
		$str = preg_replace("/".$postfilter."/is","",$str);
		$str = preg_replace("/".$cookiefilter."/is","",$str);
		//剥去 HTML、XML 以及 PHP 的标签
		$str = strip_tags($str);
		return trim($str);
	}
	/*
	 * 反sql攻击,转义 SQL特殊字符,添加的反斜杠
	 */
	public function pregSql($str){
		//函数转义 SQL 语句中使用的字符串中的特殊字符  添加的反斜杠
		/*$search = array ('and','execute','update','count','chr',
				'mid','master','truncate','char','declare',
				'select','create','delete','insert','or','=','%20');                    // evaluate as php
		$replace = array ('','','','','',
				'','','','','',
				'','','','','','','');
		$str = str_replace($search, $replace, $str);*/
		return trim($str);
	}
	/*
	 * 过滤html，反sql攻击
	 */
	public function pregHtmlSql($str){
		return $this->pregSql($this->pregHtml($str));
	}
	/*
	 * 获取文件后缀名
	 */
	function extend($file_name)
	{
		$retval="";
		$pt=strrpos($file_name, ".");
		if ($pt) $retval=substr($file_name, $pt+1, strlen($file_name) - $pt);
		return ($retval);
	}
	/**
	 * 获取文件MimeType
	 */
	public function getMimeType($file)
	{
		$extend = strtolower($this->extend($file));
		$allarr = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
				'pdf'=>'application/pdf','zip'=>'application/x-zip-compressed','rar'=>'application/x-rar-compressed',
				'xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'tif'=>'image/tiff','bmp'=>'image/bmp','rtf'=>'application/msword','doc'=>'application/vnd.ms-word','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		if(!$allarr[$extend]) return false;
		else return array('extend'=>$extend,'mimetype'=>$allarr[$extend]);
	}
}