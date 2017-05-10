////////////////////////////////////////////////////////////////////////////////收货地址
$(document).ready(function(){
       $.formValidator.initConfig({formID:"addressform",onSuccess:function(){changeaddress();},onError:function(msg,obj){showphonemsg(msg,obj)}});
       $("#shrname").formValidator({onFocus:"请输入收货人名称",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"请输入收货人名称"});
	   $("#areatmp").formValidator({empty:false,onFocus:"请输选择所在地区",onCorrect:"&nbsp;"}).inputValidator({min:1,onError:"请输选择所在地区"});
	   $("#address").formValidator({onFocus:"请输入详细地址，不需要重复填写省/市/区",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:true,emptyError:"不能有空符号"},onError:"请输入详细地址"});
	   
	   
	  /* $("#companyname").formValidator({empty:true,onFocus:"请输入公司名称",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"请输入公司名称"});*/
    
	   
	   $("#mobile").formValidator({empty:true,onFocus:"请输入你的手机号码",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).inputValidator({min:11,max:13,onError:"手机号码必须大于11位"}).regexValidator({regExp:"mobile",dataType:"enum",onError:"你输入的手机号码格式不正确"});
	   $("#tel").formValidator({empty:true,onFocus:"格式例如：0577-88888888",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).regexValidator({regExp:"^((\\d{2,5}-)?(\\d{2,5})-)?(\\d{7,8})(-(\\d{3,}))?$",onError:"请输入正确的固定电话,格式例如：0577-88888888"});
	   
	   //第二组校验组，组号为"2" 
	    $.formValidator.initConfig({validatorGroup:"2",formID:"putongform",onSuccess:function(){addputong();}});
       $("#taitouname").formValidator({validatorGroup:"2",onFocus:"请输入发票抬头",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入发票抬头"});
	   
	   //第三组校验组，组号为"3" 
	   $.formValidator.initConfig({validatorGroup:"3",formID:"zengzhishuiform",onSuccess:function(){addzengzhishui();}});
       $("#dwname").formValidator({validatorGroup:"3",onFocus:"单位名称（必须是您公司营业执照上的全称）",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入单位名称"});
	   $("#identifier").formValidator({validatorGroup:"3",onFocus:"纳税人识别号（必须是您公司《税务登记证》的编号，一般为15位，请仔细核对后输入）",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入纳税人识别号"});
	   $("#regaddress").formValidator({validatorGroup:"3",onFocus:"注册地址（必须是您公司营业执照上的注册地址）",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入注册地址"});
 
	   $("#regphone").formValidator({validatorGroup:"3",empty:false,onFocus:"注册电话（请提供能有效电话）格式例如：0577-88888888",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).regexValidator({regExp:"^((\\d{2,5}-)?(\\d{2,5})-)?(\\d{7,8})(-(\\d{3,}))?$",onError:"请输入正确的注册电话,格式例如：0577-88888888"});
	   
	   $("#bank").formValidator({validatorGroup:"3",onFocus:"开户银行（必须是您公司银行开户许可证上的开户银行）；",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入开户银行"});
	   $("#account").formValidator({validatorGroup:"3",onFocus:"银行账号（必须是您公司开户许可证上的银行账号）",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入银行帐户"});
	   var f1 = true;
	   if(annexurl==1) f1 = false;
	   $("#fileToUpload").formValidator({validatorGroup:"3",empty:f1, onFocus:".JPG/.GIF/.PNG/.PDF/.ZIP/.RAR 格式，大小不超过3M",onEmpty:"&nbsp;",onCorrect:"&nbsp;"}).regexValidator({regExp:"file",dataType:"enum",onError:".JPG/.GIF/.PNG/.PDF/.ZIP/.RAR 格式，大小不超过3M" })
	   var f2 = true;
	   if(annexurl2==1) f2 = false;
	   $("#fileToUpload2").formValidator({validatorGroup:"3",empty:f2, onFocus:".JPG/.GIF/.PNG/.PDF/.ZIP/.RAR 格式，大小不超过3M",onEmpty:"&nbsp;",onCorrect:"&nbsp;"}).regexValidator({regExp:"file",dataType:"enum",onError:".JPG/.GIF/.PNG/.PDF/.ZIP/.RAR 格式，大小不超过3M" })
	   //第四组校验组，组号为"4" 
	   $.formValidator.initConfig({validatorGroup:"4",formID:"invoiceaddressform",onSuccess:function(){changeaddress();},onError:function(msg,obj){showphonemsg(msg,obj)}});
       $("#shrname").formValidator({validatorGroup:"4",onFocus:"请输入收货人名称",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"请输入收货人名称"});
	   $("#areatmp").formValidator({validatorGroup:"4",empty:false,onFocus:"请输选择所在地区",onCorrect:"&nbsp;"}).inputValidator({min:1,onError:"请输选择所在地区"});
	   $("#address").formValidator({validatorGroup:"4",onFocus:"请输入详细地址，不需要重复填写省/市/区",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:true,emptyError:"不能有空符号"},onError:"请输入详细地址"});

})

function selectCity(){
		var provinceid=$("#province").val();
		$("#citySpan").load("/common/getcity?provinceid="+provinceid);
		$("#areaSpan").html("<select id=\"area\" name=\"area\"><option value=\"\">请选择区</option></select>");
		document.getElementById("areatmp").value = '';
	}
	function selectArea(){
		document.getElementById("areatmp").value = '';
		var cityid=$("#city").val();
		$("#areaSpan").load("/common/getarea?cityid="+cityid);
	}
	function onloadadd(provinceid,cityid,areaid)
	{
		$("#provinceSpan").load("/common/getprovince?provinceid="+provinceid);
		if(provinceid && cityid)
			$("#citySpan").load("/common/getcity?provinceid="+provinceid+"&cityid="+cityid);
		if(cityid && areaid)
		    $("#areaSpan").load("/common/getarea?cityid="+cityid+"&areaid="+areaid);
	}
	function setArea(value){
		document.getElementById("areatmp").value = value;
	}
	//提示手机或固话错误时检查
	function showphonemsg(msg,obj)
	{
		if(obj.name=='mobile' || obj.name=='tel')
		{
		   var re = checkPhone();
		   if(re) document.getElementById('phomemsg').innerHTML=msg;
		   else document.getElementById('phomemsg').innerHTML='手机和座机必须填一个';
		}
	}
	//检查是否有空
	function checkPhone()
	{
		var phonename= document.getElementById('mobile').value;
		var telname= document.getElementById('tel').value;
		if(phonename=='' && telname=='') return false;
		else return true;
	}
	//提交收货地址
	function changeaddress()
	{
		var re = checkPhone();
		if(!re) {document.getElementById('phomemsg').innerHTML='手机和座机必须填一个';return;}
		var tmp        = document.getElementById("tmp").value;
	    var addid      = document.getElementById("addid").value;
		var shrname    = document.getElementById("shrname").value;
		var province   = document.getElementById("province").value;
		var city       = document.getElementById("city").value;
		var area       = document.getElementById("area").value;
		var address    = document.getElementById("address").value;
		var companyname= document.getElementById("companyname").value;
		var warehousing= document.getElementById("warehousing").value;
		var zipcode    = document.getElementById("zipcode").value;
		var mobile     = document.getElementById("mobile").value;
		var tel        = document.getElementById("tel").value;
		var defaulttmp = document.getElementById("default").checked;
		var defaultval = 0;
		if(defaulttmp) defaultval = 1;
		$.ajax({
           url: '/order/changeaddress',
            data: {'tmp':tmp,'addid':addid,'shrname':shrname,'province':province,'city':city,'area':area,'address':address,'zipcode':zipcode,'companyname':companyname,'warehousing':warehousing,'mobile':mobile,'tel':tel,'default':defaultval},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
               if(arr.code == 0) location.reload();
			   else alert(arr.message);
			}
	    });
	}
	
function closediv(divid){
	document.getElementById(divid).style.display   = 'none';
}
function changeaddcss(addid){
     $(".changeaddcss").removeClass("mod_pipe_active");
     $(".addselect"+addid).addClass("mod_pipe_active");
 
}
//选择改变地址
function editchange(id,tmp){
	if(tmp=='add')
	{
		var addradio=document.getElementsByName("addressradio");
		for (var i=addradio.length-1;i>=0;i--)
	    {
		   addradio[i].checked = false;
		}
	    document.getElementById("tmp").value = tmp;
		document.getElementById("addid").value = '';
		document.getElementById("shrname").value = '';
		$("#provinceSpan").load("/common/getprovince");
		$("#citySpan").load("/common/getcity");
		$("#areaSpan").load("/common/getarea");
		document.getElementById("areatmp").value = '';
		document.getElementById("address").value = '';
		document.getElementById("zipcode").value = '';
		document.getElementById("mobile").value = '';
		document.getElementById("tel").value = '';
		document.getElementById("default").checked = false;
	}else{
	$.ajax({
	   url: '/center/getaddress',
		data: {'id':id},
		type: 'post',
		dataType: 'json',
		success: function(arr) {
			if(arr.code == 0)
			{
				document.getElementById("tmp").value = tmp;
				document.getElementById("addid").value = id;
				document.getElementById("shrname").value = arr.re.name;
				$("#provinceSpan").load("/common/getprovince?provinceid="+arr.re.province);
				$("#citySpan").load("/common/getcity?provinceid="+arr.re.province+"&cityid="+arr.re.city);
				$("#areaSpan").load("/common/getarea?cityid="+arr.re.city+"&areaid="+arr.re.area);
				document.getElementById("areatmp").value = arr.re.area;
				document.getElementById("address").value = arr.re.address;
				document.getElementById("zipcode").value = arr.re.zipcode;
				document.getElementById("companyname").value = arr.re.companyname;
				document.getElementById("mobile").value = arr.re.mobile;
				document.getElementById("tel").value = arr.re.tel;
				if(arr.redefault==1)document.getElementById("default").checked = true;
		        else document.getElementById("default").checked = false;
			}
	    }
	});
	}
}
//删除收货地址
function deleteaddress(ID)
{
	if(confirm('你确定删除吗？'))
    {
	$.ajax({
           url: '/center/address',
            data: {'ID':ID,'type':'delete'},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code == 0)
                {
                    location.reload();
                }
            }
	});
	}
}
//加载改变地址div
function showCgAdd(){
   document.getElementById("showaddress").style.display   = 'none';
   document.getElementById("changeaddress").style.display = 'block';
   document.getElementById("addclose").style.display      = 'block';
   document.getElementById("addcshow").style.display      = 'none';
}
//关闭改变地址div
function closeCgAdd(){
   document.getElementById("showaddress").style.display   = 'block';
   document.getElementById("changeaddress").style.display = 'none';
   document.getElementById("addclose").style.display      = 'none';
   document.getElementById("addcshow").style.display      = 'block';
}
////////////////////////////////////////////////////////////////////////////////发票
//添加普通发票
function addputong()
{
	var taitouname    = document.getElementById("taitouname").value;
	var needinvoice  = document.getElementById("needinvoice");
	var con   =document.getElementsByName("contype");
	var contype = 1;
	var invoicetype=1;
	for (var i=con.length-1;i>=0;i--)
	{
		if(con[i].checked)  contype = con[i].value;
    }
	var needinvoicevalue='';
	if(needinvoice.checked){
		needinvoicevalue = needinvoice.value;
	}
	$.ajax({
           url: '/order/invoice',
            data: {'invoicetype':invoicetype,'taitouname':taitouname,'contype':contype,'needinvoice':needinvoicevalue},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code == 0)
                {
                    location.reload();
                }
            }
	});
}
//添加增值税发票
function addzengzhishuiaction(filename,filename2)
{    
	var name       = document.getElementById("dwname").value;
	var identifier = document.getElementById("identifier").value;
	var regaddress = document.getElementById("regaddress").value;
	var regphone   = document.getElementById("regphone").value;
	var bank       = document.getElementById("bank").value;
	var account    = document.getElementById("account").value;
	var needinvoice  = document.getElementById("needinvoice");
	var needinvoicevalue='';
	if(needinvoice.checked){
		needinvoicevalue = needinvoice.value;
	}
	var contype = 1;
	var invoicetype=2;
	$.ajax({
           url: '/order/invoice',
            data: {'invoicetype':invoicetype,'contype':contype,'name':name,'identifier':identifier,'regaddress':regaddress,'regphone':regphone,'bank':bank,'account':account,'needinvoice':needinvoicevalue,'annex1':filename,'annex2':filename2},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code == 0)
                {
                    location.reload();
                }else{
				    alert(arr.message);
				}
            }
	});
} 
//上传
function addzengzhishui(){
	var fileToUpload  = document.getElementById('fileToUpload').value;
	if(fileToUpload!=''){
	$.ajaxFileUpload
	(
		{
			url:'/common/uplodfile/?part='+annexur_part+'&newname='+newname1,
			secureuri:false,
			fileElementId:'fileToUpload',
			dataType: 'json',
			data:{name:'logan', id:'fileToUpload'},
			success: function (data, status)
				{
					if(typeof(data.error) != 'undefined')
					{
						if(data.error != 0)
						{
							alert(data.message);
						}else
						{
							var fileToUpload2  = document.getElementById('fileToUpload2').value;
							if(fileToUpload2!=''){
							$.ajaxFileUpload
							(
								{
									url:'/common/uplodfile/?part='+annexur_part+'&newname='+newname2,
									secureuri:false,
									fileElementId:'fileToUpload2',
									dataType: 'json',
									data:{name:'logan', id:'fileToUpload2'},
									success: function (data2, status2)
										{
											if(typeof(data2.error) != 'undefined')
											{
												if(data2.error != 0)
												{
													alert(data2.message);
												}else
												{
													addzengzhishuiaction(data.filename,data2.filename);
												}
											}
										},
										error: function (data2, status2, e2)
										{
											alert(e2);
										}
									}
								)
							}else addzengzhishuiaction('','');
						}
					}
				},
				error: function (data, status, e)
				{
					alert(e);
				}
			}
		)
	}else{
		var fileToUpload2  = document.getElementById('fileToUpload2').value;
		if(fileToUpload2!=''){
		$.ajaxFileUpload
		(
			{
				url:'/common/uplodfile/?part='+annexur_part+'&newname='+newname2,
				secureuri:false,
				fileElementId:'fileToUpload2',
				dataType: 'json',
				data:{name:'logan', id:'fileToUpload2'},
				success: function (data2, status2)
					{
						if(typeof(data2.error) != 'undefined')
						{
							if(data2.error != 0)
							{
								alert(data2.message);
							}else
							{
								addzengzhishuiaction('',data2.filename);
							}
						}
					},
					error: function (data2, status2, e2)
					{
						alert(e2);
					}
				}
			)
		}else addzengzhishuiaction('','');	
    }
}
//改变发票div
function changeInvDiv(type){
   if(type==2)
   {
      document.getElementById("zengzhishui").style.display   = 'block';
	  document.getElementById("putong").style.display        = 'none';
   }else if(type==1){
      document.getElementById("putong").style.display        = 'block';
   	  document.getElementById("zengzhishui").style.display   = 'none';
   }
}
//显示发票div
function showInvDiv(){
   document.getElementById("div_invoice_box").style.display   = 'block';
   document.getElementById("showinvoice").style.display       = 'none';
   document.getElementById("invcshow").style.display          = 'none';
   document.getElementById("invclose").style.display          = 'block';
   //修改发票地址
   $("#add_address").css("display","none");
}
//关闭发票div
function closeInvDiv(){
   document.getElementById("div_invoice_box").style.display   = 'none';
   document.getElementById("showinvoice").style.display       = 'block';
   document.getElementById("invcshow").style.display          = 'block';
   document.getElementById("invclose").style.display          = 'none';
    //修改发票地址
   $("#add_address").css("display","block");
}
//支付方式
function payshow(){
	var pyobj        = document.getElementsByName("paymenttype");
	var online_show  = document.getElementById("online_show");
	var transfer_show= document.getElementById("transfer_show");
	
	
	var paymenttype  ='';
	for (var i=pyobj.length-1;i>=0;i--)
	{
		if(pyobj[i].checked)  paymenttype = pyobj[i].value;
    }
	if(paymenttype=='online')
	{
		 $("#online_show").css("display","block");
		 $("#transfer_show").css("display","none");
		 $("#coupon_show").css("display","none");
	}
	if(paymenttype=='transfer')
	{
		$("#online_show").css("display","none");
		$("#transfer_show").css("display","block");
		$("#coupon_show").css("display","none");
	}
	if(paymenttype=='coupon')
	{
		$("#online_show").css("display","none");
		$("#transfer_show").css("display","none");
		$("#coupon_show").css("display","block");
	}
	if(paymenttype=='cod')
	{
		$("#online_show").css("display","block");
		$("#transfer_show").css("display","none");
	}
	
}
//改变发票
function changeInvoice(){
	
	var needinvoice  = document.getElementById("needinvoice");
	var changeinvoice  = document.getElementById("changeinvoice");
	if(needinvoice.checked) changeinvoice.style.display   = 'block';
	else changeinvoice.style.display   = 'none';
}
//修改发票地址
function changeinvoiceadd(type){
	if(type=='open'){
	   document.getElementById("changeinvoiceadd").style.display   = 'block';
	   document.getElementById('invadd').innerHTML = '<a href="javascript:;" onclick="changeinvoiceadd(\'close\')">关闭</a>';
	}else{
	  document.getElementById("changeinvoiceadd").style.display   = 'none';
	  document.getElementById('invadd').innerHTML = '<a href="javascript:;" onclick="changeinvoiceadd(\'open\')">修改</a>';
	}
}
//保存发票地址
function saveinvoiceaddress(){
	var invadd   = document.getElementsByName("invoiceaddressradio");
	var newaddid=0;
	for (var i=invadd.length-1;i>=0;i--)
	{
		if(invadd[i].checked)  newaddid = invadd[i].value;
    }
	document.getElementById('invaddvaule').innerHTML = document.getElementById(newaddid+'invoiceadd').innerHTML;
	document.getElementById("changeinvoiceadd").style.display   = 'none';
	document.getElementById('invadd').innerHTML = '<a href="javascript:;" onclick="changeinvoiceadd(\'open\')">修改</a>';
}