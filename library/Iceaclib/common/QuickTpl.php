<?php
/*
 * 替换模板类
*/
class Iceaclib_common_QuickTpl
{
	private $filename; //模板文件
	private $tplcontent; //模板内容
	private $content; //返回内容
	public function __construct() {
		
	}
	//初始化模板文件，将所有内容读入
	function quicktpl($tplfilename) {
		$this->filename=$tplfilename;
		$fd = fopen( $this->filename, "r" );
		$this->content = fread($fd, filesize($this->filename));
		fclose( $fd );
	}
	//初始化模板内容，将所有内容读入
	function quicktplContent($tplcontent) {
		$this->content = $tplcontent;
	}
	//替换标志位内容
	function assign($key,$value){
		$this->content=str_replace("{".$key."}",$value,$this->content);
	}
	//替换标志块内容
	function blockAssign($block_name,$values){
		//获得替换块的子模板
		if(is_array($values)){
			ereg("{".$block_name."}.*{/".$block_name."}",$this->content,$regs);
			$str_block=substr($regs[0],2+strlen($block_name),-(strlen($block_name)+3));
			$str_replace="";
			$block_replace="";
			foreach($values as $subarr){
				$str_replace=$str_block;
				while ( list( $key, $val ) = each( $subarr ) ){
					$str_replace=str_replace("{".$key."}",$val,$str_replace);
				}
				$block_replace.=$str_replace;
			}
			$this->content=ereg_replace ("{".$block_name."}.*{/".$block_name."}",$block_replace,$this->content);
		}else{
			$this->content=ereg_replace ("{".$block_name."}.*{/".$block_name."}","none",$this->content);

		}
	}
	//输出模板内容
	function getContent(){
		return $this->content;
	}
}