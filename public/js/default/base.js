//询价
function openInquiry(id,number,currency) {
	$.openPopupLayer({
	name: "inquiryBox",
	url: "/inquiry/inqbox?id="+id+"&number="+number+"&currency="+currency
	});	  
}
//加入购物车
function buy(id,number,currency)
{
	$.openPopupLayer({
	    name: "cartBox",
	    url: "/cart/inputnum?partid="+id+"&number="+number+"&currency="+currency
	});
}
//再询价
function againInquiry(vid) {
	$.ajax({
            url: '/index/checklogin',
            data: {},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {  
					$.openPopupLayer({
						name: "inquiryBox",
						url: "/inquiry/again?id="+vid
					});
			   }else if(arr.code==100){
			   	   $.openPopupLayer({
		            name: "loginBox",
		            target: "login_box"
	              });
			   }else if(arr.code==200){
			   	   window.location.href ="/user/verification";
			   }
            }
    });
}
//收藏
function addfavorite(url) {
        var ua = navigator.userAgent.toLowerCase();
		var title = 'IC易站 -  全程设计链、供应链支持，为你提供产品、技术、服务、知识等专业服务！一站式的电子元器件电子商务交易平台！';
        if(ua.indexOf("msie 8")>-1){
            external.AddToFavoritesBar(url,title);//IE8
        }else{
            try {
                window.external.addFavorite(url,title);
            } catch(e) {
                try {
                    window.sidebar.addPanel(title, url, "");//firefox
                } catch(e) {
                    alert("浏览器不支持，请使用Ctrl+D进行收藏");
                }
            }
        }
    return false;
}
/* 输入框 */
  function inputFocus(obj, str) {
    if (obj.val() == str) {
      obj.val('');
    } else {
      obj.select();
    }
  }
  function inputBlur(obj, str) {
    if (obj.val() == '') {
      obj.val(str);
    }
  }
//加载易站左菜单
function onloadleftmenu(selectname){
	$.ajax({
            url: '/center/leftmenu',
            data: {'select':selectname},
            type: 'post',
            dataType: 'html',
            success: function(html) {
            	document.getElementById("leftmenu").innerHTML = html;
            }
    });
}
//加载热销产品
function hotpord(){
	$.ajax({
            url: '/publicbox/hotpord',
            data: {},
            type: 'post',
            dataType: 'html',
            success: function(html) {
            	document.getElementById("hotpord").innerHTML = html;
				//推荐产品价格
				 $(function(){
					  var tab = $("#hotpord .goodsprice");
					   tab.hover(function(){
						   $(this).find(".price").addClass("hover");
						   $(this).find(".tipprice").addClass("block");
					   },function(){
						   $(this).find(".price").removeClass("hover");
						   $(this).find(".tipprice").removeClass("block");
					  });
				})
            }
    });
}
//加载热门方案
function hotscheme(appid){
	$.ajax({
            url: '/publicbox/hotscheme',
            data: {'appid':appid},
            type: 'post',
            dataType: 'html',
            success: function(html) {
            	document.getElementById("hotscheme").innerHTML = html;
            }
    });
}
//删除询价
function delectinquiry(rowid)
{
	$.ajax({
            url: '/inquiry/delectinq',
            data: {'rowid':rowid},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code==0)
				{
				    location.reload();
				}
            }
    });
}
//删除购物车
function delectcart(rowids)
{
	$.ajax({
            url: '/cart/delectcart',
            data: {'rowids':rowids},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code==0)
				{
				    location.reload();
				}
            }
    });
}
$(function(){
		//购物车headbtnbg buy
        var tab = $(".headbtnbg .buyson");
        tab.hover(function(){
            $(this).addClass("hover");
			$(this).find(".hoverbox").addClass("block");
        },function(){
            $(this).removeClass("hover");
			$(this).find(".hoverbox").removeClass("block");
        });
		//询价
        var tab = $(".headbtnbg .questionson");
        tab.hover(function(){
            $(this).addClass("hover");
			$(this).find(".hoverbox").addClass("block");
        },function(){
            $(this).removeClass("hover");
			$(this).find(".hoverbox").removeClass("block");
        });
		
        //我的易站
        var tab = $(".menus .ui_navs");
        tab.hover(function(){
            $(this).find(".ui_first_nav").addClass("ui_hover");
			$(this).find(".ui_second_nav").addClass("block");
        },function(){
            $(this).find(".ui_first_nav").removeClass("ui_hover");
			$(this).find(".ui_second_nav").removeClass("block");
        });
		
		//客服中心
        var tab = $(".shortcut .fr .fore4");
        tab.hover(function(){
            $(this).addClass("hover");
        },function(){
            $(this).removeClass("hover");
        });
})
//通用弹出对话框
function alertbox(message)
{
	 $.openPopupLayer({
		  name: "alertBox",
		  url: "/publicbox/alert/message/"+encodeURI(message)
	   });
}

//购物车输入数据检查通用
var setAmountBase={
	reg:function(x){
		if(x=='') return true;
		return new RegExp("^[1-9]\\d*$").test(x);
	},
	amount:function(obj,input,mode,moq,mpq){
		var x=$(obj).val();
		if (this.reg(x)){
			if (mode){
				if((moq%mpq)==0 && mpq!=0)
				{
					var m=parseInt($(input).val());
					x = parseInt(x)+parseInt(mpq);
					$(input).val(m+1);
				}else x++;
			}else{
				if((moq%mpq)==0 && mpq!=0)
				{
					var m=parseInt($(input).val());
					x = parseInt(x)-parseInt(mpq);
					$(input).val(m-1);
				}else x--;
			}
		}else{
			alert("请输入正确的数量！");
			$(obj).val(moq);
			$(obj).focus();
		}
		return parseInt(x);						
	},
	reduce:function(obj,input,partid,moq,mpq,stock){
		var x=this.amount(obj,input,false,moq,mpq);
		moq = parseInt(moq);
		mpq = parseInt(mpq);
		if((moq%mpq)==0 && mpq!=0){
			if(x<moq){
					alert("不要低于最少购买数量:"+moq+"！");
					$(obj).val(moq);
					$(input).val(moq/mpq);
			}else $(obj).val(x);
		}else{
				if (x>=moq){
				   $(obj).val(x);
				}else{
					alert("不要低于最少购买数量:"+moq+"！");
				   $(obj).val(moq);
				   $(obj).focus();
				}
		}	
		subtotal(obj);
	},
	add:function(obj,input,rowid,moq,mpq,stock){
		var x=this.amount(obj,input,true,moq,mpq);
		$(obj).val(x);
		subtotal(obj);
	},
	modify:function(obj,rowid,moq,mpq,stock){
		var x=$(obj).val();
		moq = parseInt(moq);
		mpq = parseInt(mpq);
			if (!this.reg(x)){
				alert("请输入正确的数量！");
				$(obj).val(moq);
				$(obj).focus();
			}else{
				if (x>=moq){
				   $(obj).val(x);
				}else{
				   if(x=='') return true;
				   alert("不要低于最少购买数量:"+moq+"！");
				   $(obj).val(moq);
				   $(obj).focus();
				}
			}	
		subtotal(obj);
	},
	multiple:function(obj,input,rowid,moq,mpq,stock){
		var x=$(input).val();
		var num = $(obj).val();
		var allnum = x*mpq;
			if (!this.reg(x)){
				alert("请输入正确的数量！");
				$(input).val(moq/mpq);
				$(obj).val(moq);
				$(input).focus();
			}else{
				$(obj).val(allnum);
			}
		subtotal(obj);
	}
}
//当购买数量超过库存，弹出提示
//var outride_show = 1;
//var old_partid   = 0;
function outride_buynum(stock,id)
{
	var part_id = 0;
	var idboj = document.getElementById('partid');
    var collection_id = document.getElementById('collection_id').value;
	var supplier_id = document.getElementById('supplier_id').value;
	if(idboj==undefined) {
		part_id = id;
		//alertbox('剩余库存：'+stock+'，您输入的购买数量超过库存数!');
	}else{
		part_id = idboj.value;
	}
   //if(outride_show==1 || old_partid!=part_id){
	   outride_show = 0;
	   old_partid = part_id;
	   $.openPopupLayer({
		  name: "outrideBox",
		  url: "/cart/outridebuynum?id="+part_id+"&stock="+stock+"&collection_id="+collection_id+"&supplier_id="+supplier_id
	   });
  // }
}

//产品详细页面小计
function subtotal(obj){
   var delivery_place='SZ';
   var buynum = parseInt($(obj).val());
   var partid = document.getElementById('partid').value;
   var collection_id = document.getElementById('collection_id').value;
   var supplier_id = document.getElementById('supplier_id').value;
   var delivery_place_obj        = document.getElementsByName("delivery_place");
   for (var i=delivery_place_obj.length-1;i>=0;i--)
	{
		if(delivery_place_obj[i].checked)  delivery_place = delivery_place_obj[i].value;
    }
   $.ajax({
            url: '/cart/calculate',
            data: {'buynum':buynum,'partid':partid,'delivery_place':delivery_place,'collection_id':collection_id,'supplier_id':supplier_id},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {  
			      if(arr.surplus==1){
					 alert("很抱歉，库存不足");
					 location.reload();
					 return;
				  }
				  $("#sell_price").html(arr.price);
				  $("#sell_price_old").html((arr.price*1.03).toFixed(5));
				  $("#sell_total").html(arr.total);
				  $(".lead_time_show").html(arr.lead_time_show);
				  $(".stock_type").html(arr.stock_type);
			   }else if(arr.code==100)
			   {
				   alert(arr.message);
				   location.reload();
			   }else{
			   	  alertbox(arr.message);
			   }
            }
    });
}
//产品详细页面加入购物车
function buy_details(obj,id,moq) 
{
  var delivery_place='SZ';
  var buynum = parseInt($(obj).val());
  if(isNaN(buynum) || buynum == 0) {alertbox('请输入数量');$(obj).focus();return;}
  else if(buynum < moq){alertbox("不要低于最少购买数量:"+moq+"！");$(obj).focus();return;}
  var delivery_place_obj        = document.getElementsByName("delivery_place");
  for (var i=delivery_place_obj.length-1;i>=0;i--)
  {
	if(delivery_place_obj[i].checked)  delivery_place = delivery_place_obj[i].value;
  }
   //添加到购物车
   buy_details_add(id,buynum,delivery_place);
}
//bpp页面加入购物车
function buyBpp(id,bpp_stock_id,delivery_place,buynum) 
{
   //添加到购物车
   $.ajax({
            url: '/cart/add',
            data: {'id':id,'buynum':buynum,'delivery_place':delivery_place,'bpp_stock_id':bpp_stock_id},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {
				 $(".cartnumber").html(arr.cartnumber);
				 $.openPopupLayer({
		          name: "cartshowBox",
		          url: "/cart/show?cartnumber="+arr.cartnumber
	              });
				  
				  $(".cartnumber").html(arr.cartnumber);
			   }else if(arr.code==101)
			   {
				  outride_buynum(arr.stock,id);  
				 //location.reload();
			   }else {alertbox(arr.message);}
            }
    });
}
//添加到购物车
function buy_details_add(id,buynum,delivery_place){
	var collection_id = document.getElementById('collection_id').value;
	var supplier_id = document.getElementById('supplier_id').value;
	$.ajax({
            url: '/cart/add',
            data: {'id':id,'buynum':buynum,'delivery_place':delivery_place,'collection_id':collection_id,'supplier_id':supplier_id},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {
				 $(".cartnumber").html(arr.cartnumber);
				 $.openPopupLayer({
		          name: "cartshowBox",
		          url: "/cart/show?cartnumber="+arr.cartnumber
	              });
				  
				  $(".cartnumber").html(arr.cartnumber);
			   }else if(arr.code==101)
			   {
				 alert(arr.message);
				 location.reload();
			   }else alertbox(arr.message);
            }
    });
}
function BASEisFloat(theFloat)     
{     
 //判断是否为浮点数     
  len=theFloat.length;     
  dotNum=0;     
  if(len==0) return false; 
  for(var i=0;i<len;i++){     
    oneNum=theFloat.substring(i,i+1);     
    if(oneNum==".") {
		dotNum++;
		if(i == (len-1)) return false;
	}
    if (((oneNum<"0" || oneNum>"9") && oneNum!=".") || dotNum>1)     
      return false;     
  }     
  if(len>1 && theFloat.substring(0,1)=="0"){     
      if(theFloat.substring(1,2)!=".") return false;     
  }     
  return true; 
}
function CheckFloat(theFloat,num)     
{
  var len=theFloat.length;     
  var check = false;
  for(var i=0;i<len;i++){     
    oneNum=theFloat.substring(i,i+1);     
    if(oneNum==".") check=true;
  }
  if(check){
	  var strs= new Array(); 
	  strs=theFloat.split(".")
	  if(strs[1].length > num){     
		 return false;     
	  }     
	  return true; 
  }else return true;
}
function isTel(value)
{
 //国家代码(2到3位)-区号(2到3位)-电话号码(7到8位)-分机号(3位)"
 var pattern =/^(([0\+]\d{2,5}-)?(0\d{2,5})-)(\d{7,8})(-(\d{3,6}))?$/;
 if(value!="")
 {
    if(pattern.exec(value))
     {
         return true;
     }
  }
  return false;
}
function number_format(number,fix,fh,jg)//number传进来的数,fix保留的小数位,默认保留两位小数,fh为整数位间隔符号,默认为空格,jg为整数位每几位间隔,默认为3位一隔
{
   var fix = 2 ;
   var fh = ',' ;
   var jg = 3 ;
   var str = '' ;
   number = number.toFixed(fix);
   zsw = number.split('.')[0];//整数位
   xsw = number.split('.')[1];//小数位
   zswarr = zsw.split('');//将整数位逐位放进数组
   for(var i=1;i<=zswarr.length;i++)
   {
      str = zswarr[zswarr.length-i] + str ;
      if(i%jg == 0)
      {
        str = fh+str;//每隔jg位前面加指定符号
      }
   }
   str = (zsw.length%jg==0) ? str.substr(1) : str;//如果整数位长度是jg的的倍数,去掉最左边的fh
   zsw = str+'.'+xsw;//重新连接整数和小数位
   return zsw;
}
//打印特定区域
function  printDiv(divid){   
  var newWin=window.open('http://', '', '');   
  var titleHTML=document.getElementById(divid).innerHTML;   
  newWin.document.write(titleHTML);   
  newWin.print();
  newWin.close();   
}
//打印页面
function  printPage(){   
  window.print();   
}
//复制邀请链接
function copyinvite(divid){
    if(document.all){
		window.clipboardData.setData("Text",$('#'+divid).val());
		alert("复制成功");
	}else{
		alert("您的浏览器不支持复制功能，请手动复制！");
		$('#'+divid).select();
	}
}
//登陆框
function openlogin()
{
	$.ajax({
            url: '/index/checklogin',
            data: {},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {  
					location.reload();
			   }else if(arr.code==100){
			   	  $.openPopupLayer({
		             name: "loginBox",
		             target: "login_box"
	              });
			   }else if(arr.code==200){
			   	   window.location.href ="/user/verification";
			   }
            }
    });	
}
//补零
function pad(num, n) {  
  var len = num.toString().length;  
    while(len < n) {  
        num = "0" + num;  
        len++;  
    }  
    return num;  
} 
//验证码
function getVerify(){
 var d = new Date();
 document.getElementById('verify').src="/common/createcode?t="+d.toTimeString();
}
//检查email地址
function checkemail(temp)
  {
        var myreg = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
        if(!myreg.test(temp.val()))
         {
                temp.focus();
                return false;
         }else{
                return true;
         }
        
    } 
	

//邀请框
function openinvitebox(){
	$.ajax({
		url: '/index/checklogin',
		data: {},
		type: 'post',
		dataType: 'json',
		success: function(arr) {
		  if(arr.code==0)
		  {
		     $.openPopupLayer({
			   name: "invitebox",
			   url: "/jifen/invitebox"
		     });
		  }else if(arr.code==100){
			$.openPopupLayer({
			   name: "loginBox",
			   target: "login_box"
			 });
		   }else if(arr.code==200){
			  window.location.href ="/user/verification";
		  }
		}
	});
}

function openbox(url)
{
	 $.openPopupLayer({
        name:'box',
        url:url
    });
}

//更新积分
function upscore(){
	 $.ajax({
			url: '/user/getuserscore',
			data: {},
			type: 'post',
			dataType: 'json',
			success: function(arr) {
				$(".userscore").html(arr.score);
			}
	  });	
}
//下载代码
function downloadcode(key){
	$.ajax({
            url: '/index/checklogin',
            data: {},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
				if(arr.code==0){  
					$.ajax({
						url: '/code/cscore',
						data: {'key':key},
						type: 'post',
						dataType: 'json',
						success: function(arr) {
							if(arr.code==0) {window.location.href='/code/download?key='+key;}
							else if(arr.code==1){ 
							    $.openPopupLayer({
									name:'passBox',
									url:'/code/inputpass?key='+key
								});
							}else alert(arr.message);
						}
				});
			   }else if(arr.code==100){
			   	  $.openPopupLayer({
		             name: "loginBox",
		             target: "login_box"
	              });
			   }else if(arr.code==200){
			   	   window.location.href ="/user/verification";
			   }
            }
    });
}
//shouw
function center_css(){
	if (screen.width >= 1280) {
    document.write('<style type="text/css">.u_right{width: 1053px;}.ivcoice{width: 1051px;}</style>');
}	else {document.write('<style type="text/css">.w{ width:990px}</style>');}
}
function widescreen_css(){
	if (screen.width >= 1280) { 
        	document.write('<style type="text/css">.w{width: 1190px}.searchTxt,.searchTxtHover{width: 450px}.searchtext{width: 350px}.indexShowBox,.slider_img,.slider_img img,.indexShow{width: 700px !important;height: 342px}.mainbannerbox{width: 1000px}.brand_wall{width: 672px;}.brand_wall li a,.brand_wall li .empty,.flowTip span,.brand_wall li a.flowTip span{width: 92px}.mainrightbox_buynow{width: 258px;}.mainrightbox_buynow .input150{width: 250px}.mainrightbox .item{width: 278px;}.mod_tab{width: 278px;}.mod_tab .tabbar a{width: 92px;}.ptip201312{margin-bottom: 10px}.por_box2013 .tabelbtn{top: 90px}.hotnew .hotnew_por_box2013{width: 798px;height: 205px}.hotnew .por_box2013{width: 187px;height: 205px;}.hotnew .por_box2013 .s-img img{height: 100px}.hotnew .hotnewbrand{height: 225px}.hotnew .brandlisth a{height: 55px;line-height: 55px;*font-size: 46px}.hotgoods{width: 888px;}.hot_sch{width: 290px;}.hot_sch .indexShowBox,.hot_sch .slider_img,.hot_sch .slider_img img,.hot_sch .indexShow{width: 290px !important;height: 225px}.hot_sch .slider_trigger{top: 200px}.hotRecommend .hotRecommend_A{height: 180px;}.hotRecommend .hotRecommend_A li{margin-right: 10px}.hotRecommend .por_box2013{width: 290px;height: 180px}.hotRecommend .por_box2013 .pbrand201312{top: 140px}.hotRecommend .item .s-img img{height: 110px}.hotRecommend .fore4 .item{width: 290px}.app_soumain{width: 700px}.sou201312{width: 659px;padding: 5px 10px 15px 31px;}.sou201312 ul{width: 639px}.sou201312 .por_box2013{width: 305px}.U-hotRecommend{width: 205px}.U-hotRecommend .por_box2013{width: 203px}.market-cat .section .sublist{width: 455px;}.list1 .market-cat .section{width: 382px;}.prodetail2013{background:url(/2014/css/img/rightshadbg.jpg) repeat-y 1000px 0;}.proinfo{width: 708px;}.proinfo_m{width: 710px;}.proinfo_data, .proinfo_s, .proinfo_btn{width: 690px;}.sembox2013 .proinfo_btn{width: 140px;}.code2013 .proinfo_btn{width: 140px;}.prodetail2013_c{width: 995px;}.rigybg{background:url(/2014/css/img/rightshadbg.jpg) repeat-y 915px 0;}.selectbox201312,.layout .infolist{width: 905px;}.selectbox201312 .catList{width: 800px;}.selectbox201312 dl dd{width: 805px;}.infolist .posts{width: 885px;}.post-cont{width: 670px;}.sembox2013 .post-cont{width: 880px;height: 50px;}.code2013 .post-cont{width: 740px;}.sch_detal .posts{width: 840px}.sch_detal .post-cont,.sch_detal .read_more{width: 840px;}.sch_detal .shoplist,.code2013 .shoplist{width: 905PX;}.schemecontent{width: 990px;}.themain{width: 900px;}.sembox2013 .themain1 .themain_p, .code2013 .themain1 .themain_p{width: 870px;}.sembox2013 .themain1 .themain_p p, .code2013 .themain1 .themain_p p{width: 700px;}.sembox2013 .dl2013 dd{width: 810px;}.codetj{background:url(/2014/css/img/codetjbg_b.jpg) no-repeat !important;width: 855px;}.BOMinfo{width: 1110px;}.BOMinfo .btnok-new1{margin: 30px 0 10px 450px;}.cat-index li{width: 65px;height: 65px;margin: 10px 0 0px 13px;}.cat-index li a{width: 65px;height: 65px;}.cat-index li .shadow{width: 65px;height: 65px;background:url(/2014/css/img/p-65-65.png) no-repeat;_background: 0;_filter: progid: dximagetransform.microsoft.alphaimageloader(enabled=true,sizingMethod=noscale,src="../img/p-65-65.png");}.cat-index li .icon{height: 45px;width: 55px;padding: 10px 5px;font-size: 14px;line-height: 20px;}.mymainbox{width: 1055px;}.mymainboxleft{width: 790px;}.ourmain_1, .ourmian2, .ourmain_3, .ourmian6, .ourmian7{width: 760px;}.ourmian6, .ourmian7{width: 1055px !important;}.ourmian6jifen{height: 225px;}.s1_mod{float: left;width: 252px;height: 220px;}.s1_pic{width: 252px;height: 220px;}.s1_pic img{width: 256px;height: 170px;}.s1_info{width: 252px;}.s1_info_name{width: 236px;}.ourmian7 .hotRecommend_A{height: 170px;}.ourmian7 .por_box2013{width: 254px;height: 156px;border: 0}.ourmian7 .por_box2013 .pbrand201312{top: 110px;}.footerinfo{width: 930px;}.footerinfo .helpCol_01, .footerinfo .helpCol_02, .footerinfo .helpCol_03, .footerinfo .helpCol_04, .footerinfo .helpCol_05{width: 176px}.footer_pic li a{width: 236px;}.friendlinkbottom{width: 1158px}.friendlinkli{width: 1050px;}.brandconimg img{width:1190px;}.brand_list dl dd{width:1070px;}.brandZm li{width: 41px;}.brandZm .other{width:53px}.brand_list_sp li{padding:7px 7px 7px 8px;}.brand_list li img{ height:55px;}.indexsearch{width:1188px;}.pro_sortbarul,.ulfore2{ width:1080px }.ulfore2 li,.ulfore3,.ulfore3 li label{ width:180px;}.epsonyycon{width:297px; height:180px;}.GDimgbox_d{ height:200px;}</style>');
		}	
	if (screen.width >= 1366) { 
        	document.write('<style type="text/css">.rightsav .ibox{display:block}.rightsav{top:300px;}</style>');
		}	
}

//样片申请
function appSamples(key){
	$.ajax({
            url: '/index/checklogin',
            data: {},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {  
			   		 $.ajax({
						url: '/user/checkdetailed',
						data: {},
						type: 'post',
						dataType: 'json',
						success: function(arr) {
							if(arr.code==0)
							{
								openbox('/samples/app?key='+key);
							}else{
								$.openPopupLayer({
								 name: "companyinfoBox",
								 url: "/center/companyinfo?opbox=box&opurl=/samples/app&key="+key
								});
							}
						}
					});
					
			   }else if(arr.code==100){
			   	 $.openPopupLayer({
		             name: "loginBox",
		             target: "login_box"
	              });
			   }else if(arr.code==200){
			   	   window.location.href ="/user/verification";
			   }
            }
    });
}