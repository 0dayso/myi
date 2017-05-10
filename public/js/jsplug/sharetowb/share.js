(function(){
	if(!window.rmwbShareConf){
		window.rmwbShareConf = {};
	};

	//CSS
	var CSS = 'a.rmwb_fixbtn:link,a.rmwb_fixbtn:visited{width:22px;background:#fff url(/js/jsplug/sharetowb/rmwb_02.png) no-repeat 3px -197px;position:fixed;bottom:150px;font:12px/14px SimSun;color:#999;border:1px solid #eaeff5;padding:22px 0 5px;text-decoration:none;text-align:center;}'
		+ 'a.rmwb_fixbtn:hover{color:#c00;text-decoration:none;border-color:#c00;z-index:10000;}'
		+ '.rmwb_dragLayer{position:absolute;z-index:10000;width:210px;height:92px;z-index:10000;}'
		+ '.rmwb_dragLayer .rmwb_bg{width:210px;height:92px;background:url(/js/jsplug/sharetowb/rmwb_01.png) no-repeat 0 0;_background:none;_filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,sizingMethod=scale,src=\'/js/jsplug/sharetowb/rmwb_01.png\');}'
		+ '.rmwb_dragLayer .rmwb_icons{position:absolute;overflow:hidden;_zoom:1;top:46px;left:16px;}'
		+ '.rmwb_dragLayer .rmwb_tit{position:absolute;top:20px;left:25px;font:12px/20px SimSun;color:#666;}'
		+ '.rmwb_dragLayer .rmwb_icons a{width:24px;height:24px;background:#ccc;margin:0 10px;_display:inline;float:left;background:url(/js/jsplug/sharetowb/rmwb_02.png) no-repeat 0 0;}'
		+ '.rmwb_dragLayer .rmwb_icons a.icon_sina{background-position:0 -50px;}'
		+ '.rmwb_dragLayer .rmwb_icons a.icon_qq{background-position:0 -100px;}'
		+ '.rmwb_dragLayer .rmwb_icons a.icon_qzone{background-position:0 -150px;}';
	var styleTag = document.createElement("style");
	styleTag.type = "text/css";
	document.getElementsByTagName('head')[0].appendChild(styleTag);
	
	if(styleTag.styleSheet){
		styleTag.styleSheet.cssText = CSS;
	}else{
		styleTag.textContent = CSS;
	};
	//-- 划词分享 --
	var sX, sY;
	window.rmwbShare = {
		bindTextArea : function(id){
			var box = document.getElementById(id);
			if(!box){
				return;
			};
			addEvent(box, 'mousedown', function(e){
				sX = e.pageX || (e.clientX + document.documentElement.scrollLeft);
				sY = e.pageY || (e.clientY + document.documentElement.scrollTop);
			});
			addEvent(box, 'mouseup', function(e){
				var x = e.pageX || (e.clientX + document.documentElement.scrollLeft),
					y = e.pageY || (e.clientY + document.documentElement.scrollTop);

				if(x == sX && y == sY){
					return;
				};
				var text = '';
				if(e.button == 2){return};
				if(window.getSelection){
					text = window.getSelection().toString();
				}else if(document.selection) { //IE 
					text = document.selection.createRange().text;
				};
				if(text == ''){
					return;
				};
				_rmwbShare.text = cutString(text, 200);
				
				showShare(x, y);
			});
		}
	};
	rmwbShare.bindTextArea(rmwbShareConf.contentId || 'p_content');

	addEvent(document.body, 'mousedown', function(e){
		hideShare();
	});

	var shareLayerDiv, showStatus = false;
	function showShare(x, y){
		createShare();
		showStatus = true;
		shareLayerDiv.style.display = 'block';
		shareLayerDiv.style.left = x - 30 + 'px';
		shareLayerDiv.style.top = y + 5 + 'px';
	};
	function hideShare(){
		if(!showStatus){return}
		if(shareLayerDiv){
			shareLayerDiv.style.display = 'none';
		};
		showStatus = false;
	};
	
	function createShare(){
		if(shareLayerDiv){
			return;
		};
		shareLayerDiv = document.createElement('div');
		document.body.insertBefore(shareLayerDiv, document.body.firstChild);
		shareLayerDiv.className = 'rmwb_dragLayer';
		shareLayerDiv.onmousedown = function(e){
			e = e || event;
			e.stopPropagation ? e.stopPropagation() : (e.cancelBubble = true);
		};

		var html = '';
		for (var i = 0; i < _rmwbShare.btns.length; i++) {
			html += '<a href="javascript:void(0)" onclick="_rmwbShare.share(\'' + _rmwbShare.btns[i].className + '\');return false;" class="' + _rmwbShare.btns[i].className + '" title="分享到' + _rmwbShare.btns[i].title + '"></a>';
		};
		shareLayerDiv.innerHTML = '<div class="rmwb_bg"></div><div class="rmwb_tit">将选择内容分享到：</div><div class="rmwb_icons">' + html + '</div>';
	};
	window._rmwbShare = {
		text : document.title,
		url : window.location.href,
		pic : '',
		//按钮数据，变量：[$text],[$url],[$pic]
		btns : [{
			title : 'QQ好友',
			className : 'icon_rmwb',
			url :'http://connect.qq.com/widget/shareqq/index.html?url=[$url]&title=[$text]&desc=&summary=&site=IC易站',
			width : 650,
			height : 500
		},
		{
			title : '新浪微博',
			className : 'icon_sina',
			url : 'http://service.weibo.com/share/share.php?url=[$url]&title=[$text]&ralateUid=&source=IC易站&sourceUrl=http%3A%2F%2Fwww.people.com.cn%2F&content=gb2312&pic=[$pic]',
			width : 650,
			height : 500
		},
		{
			title : '腾讯微博',
			className : 'icon_qq',
			url : 'http://share.v.t.qq.com/index.php?c=share&a=index&url=[$url]&title=[$text]&pic=[$pic]',
			width : 612,
			height : 350
		},
		{
			title : 'QQ空间',
			className : 'icon_qzone',
			url : 'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=[$url]&title=[$text]&pics=[$pic]&summary=',
			width : 612,
			height : 500
		}],
		share : function(className){
			for (var i = 0; i < this.btns.length; i++) {
				if(this.btns[i].className == className){
					url = this.btns[i].url;
					url = url.replace(/\[\$text\]/g, encodeURIComponent(this.text));
					url = url.replace(/\[\$pic\]/g, encodeURIComponent(this.pic));
					url = url.replace(/\[\$url\]/g, encodeURIComponent(this.url));
					window.open(url, 'rmwbShare', 'toolbar=0,status=0,resizable=1,width='+(this.btns[i].width+80)+',height='+(this.btns[i].height+80)+',left='+Math.round(screen.width/2 - this.btns[i].width/2)+',top='+(Math.round(screen.height/2 - this.btns[i].height/2)));
					hideShare();
					break;
				};
			};
		}
	};
	function addEvent(obj,eventType,func){if(obj.attachEvent){obj.attachEvent("on" + eventType,func);}else{obj.addEventListener(eventType,func,false)}};
	function cutString(str,len){ //截取
		if(typeof(str) != "string"){return null};
		if(!(/^[0-9]*[1-9][0-9]*$/).test(len)){return str};
		if(len == 0){return str};
		var sum = 0,newStr = "";
		for(var i=0;i<str.length;i++){
			if(str.charCodeAt(i) > 255){
				sum += 2;
			}else{
				sum ++;
			};
			if(sum <= len - 2){
				newStr += str.charAt(i);
			}else{
				if(i==str.length-1){
					newStr += str.charAt(i);
				}else{
					newStr += "..";
				};
				break;
			};
		};
		return newStr;
	};
})();