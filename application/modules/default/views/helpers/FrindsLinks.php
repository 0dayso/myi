<?php
class Zend_View_Helper_FrindsLinks extends Zend_View_Helper_Abstract
{
	function frindsLinks()
	{
		$html = '';
		//友情链接
		$frindsservice = new Default_Service_FriendshipService();
		$this->frindslink = $frindsservice->getHomeLink();
		if($this->frindslink){
			$html .='<div class="friendlinkbottom">
		        	<strong>友情链接：</strong>
		            <div class="friendlinkli">           	
		            	<ul>';
			foreach($this->frindslink as $key=>$link){
			      $bo ='';if($key==(count($this->frindslink)-1)) $bo = ' style="border:0;"';
		          $html .='<li '.$bo.'><a href="'.$link['url'].'" title="'.$link['name'].'" target="_blank">'.$link['name'].'</a></li>';
			}       
		    $html .='<!--<li style="border:0; line-height:6px; *line-height:12px;line-height:10px\0;"><a href="/links" target="_blank">更多<span style="font-family:Arial; font-size:16px;">&raquo;</span></a></li> -->
		                </ul>
		            </div>
		        </div>';
		}
		return $html;
	}
}