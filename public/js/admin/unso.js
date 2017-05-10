
////////////////////////////////////////////////////////////////////////////////发票
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
//修改发票
$(function(){
	$(".edit_invoice").click(function(){
		var invoicetype = $('input[name="invoicetype"]:checked').val();
		if(invoicetype==1){
		   addputong();
		}else if(invoicetype==2){
		   addzengzhishui();
		}
	});
});

//添加增值税发票
function addzengzhishui(){
    var uid        = $("#uid").val();
	var name    = $('input[name="dwname"]').val();
	if(shrname==''){alert('请输入单位名称');$('input[name="dwname"]').focus();return;}
    var identifier    = $('input[name="identifier"]').val();
	if(identifier==''){alert('请输入纳税号');$('input[name="identifier"]').focus();return;}
	var regaddress    = $('input[name="regaddress"]').val();
	if(regaddress==''){alert('请输入注册地址');$('input[name="regaddress"]').focus();return;}
	var regphone    = $('input[name="regphone"]').val();
	if(regphone==''){alert('请输入注册电话');$('input[name="regphone"]').focus();return;}
    var bank    = $('input[name="bank"]').val();
	if(bank==''){alert('请输入开户银行');$('input[name="bank"]').focus();return;}
    var account    = $('input[name="account"]').val();
	if(account==''){alert('请输入银行帐户');$('input[name="account"]').focus();return;}

	var contype = 1;
	var invoicetype=2;
	$.ajax({
           url: '/icwebadmin/OrUnso/changeinvoice',
            data: {'uid':uid,'invoicetype':invoicetype,'contype':contype,'name':name,'identifier':identifier,'regaddress':regaddress,'regphone':regphone,'bank':bank,'account':account},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code == 0)
                {
					$(".inv_dwname").html(name);
					$(".inv_identifier").html(identifier);
					$(".inv_regaddress").html(regaddress);
					$(".inv_regphone").html(regphone);
					$(".inv_bank").html(bank);
					$(".inv_account").html(account);
					document.getElementById('incoice_id').value             = arr.invid;
					$(".invoice_1").css("display","none");
		            $(".invoice_2").css("display","block");
					$(".invoice_show").css("display","block");
		            $(".invoice_add_edit").css("display","none");
                }else alert(arr.message);
            }
	});
}
//添加普通发票
function addputong()
{
	var uid        = $("#uid").val();
	var typeArr = new Array();
	typeArr[1] = '明细';
	typeArr[2] = '电子元件';
	typeArr[3] = '耗材';
	var taitouname    = document.getElementById("taitouname").value;
	if(taitouname==''){alert('请输入发票抬头');$('input[name="taitouname"]').focus();return;}
	var needinvoice  = document.getElementById("needinvoice");
	var con   =document.getElementsByName("contype");
	var contype = 1;
	var invoicetype=1;
	for (var i=con.length-1;i>=0;i--)
	{
		if(con[i].checked)  contype = con[i].value;
    }
	
	$.ajax({
           url: '/icwebadmin/OrUnso/changeinvoice',
            data: {'uid':uid,'invoicetype':invoicetype,'taitouname':taitouname,'contype':contype},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code == 0)
                {
					$(".inv_taitouname").html(taitouname);
					$(".inv_cont").html(typeArr[contype]);
                    document.getElementById('incoice_id').value  = arr.invid;
					$(".invoice_1").css("display","block");
		            $(".invoice_2").css("display","none");
					$(".invoice_show").css("display","block");
		            $(".invoice_add_edit").css("display","none");
                }else alert(arr.message);
            }
	});
}
////////////地址
function selectCity(){
		var provinceid=$("#province").val();
		$("#citySpan").load("/common/getcity?provinceid="+provinceid);
		$("#areaSpan").html("<select id=\"area\" name=\"area\"><option value=\"\">请选择区</option></select>");
}
function selectArea(){
	var cityid=$("#city").val();
	$("#areaSpan").load("/common/getarea?cityid="+cityid);
}
function setArea(value){
	return;
}

//保存收货地址
$(function(){
	$(".edit_address").click(function(){
		var uid        = $("#uid").val();
	    var addid      = $("#addid").val();
		var shrname    = $("#shrname").val();
		if(shrname==''){alert('请输入收货人');$('input[name="shrname"]').focus();return;}
		var province   = $("#province").val();
		//if(province==''){alert('请选择省');$('input[name="province"]').focus();return;}
		var province_name   = $("#province").find("option:selected").text();
		province_name = (province_name=="请选择省"?"":province_name);
		var city       = $("#city").val();
		//if(city==''){alert('请选择市');$('input[name="city"]').focus();return;}
		var city_name  = $("#city").find("option:selected").text();
		city_name = (city_name=="请选择市"?"":city_name);

		var area       = $("#area").val()
		//if(area==''){alert('请选择区');$('input[name="area"]').focus();return;}
		var area_name  = $("#area").find("option:selected").text();
		area_name = (area_name=="请选择区"?"":area_name);
		var address    = $("#address").val();
		if(address==''){alert('请输入详细地址');$('input[name="address"]').focus();return;}
		var companyname= $("#companyname").val();
		var warehousing= $("#warehousing").val();
		var zipcode    = $("#zipcode").val();
		var mobile     = $("#mobile").val();
		var tel        = $("#tel").val();
		if(mobile=='' && tel==''){alert('请输入手机或者电话');return;}
		var defaulttmp = $("#default").attr("checked");
		var defaultval = 0;
	    if(defaulttmp=='checked') defaultval = 1;
		$.ajax({
            url: '/icwebadmin/OrUnso/changeaddress',
            data: {'uid':uid,'addid':addid,'shrname':shrname,'province':province,'city':city,'area':area,'address':address,'zipcode':zipcode,'companyname':companyname,'warehousing':warehousing,'mobile':mobile,'tel':tel,'default':defaultval},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
               if(arr.code == 0){
				   $("#addid").val(arr.addid);
				   $(".show_shrname").html(shrname);
				   $(".show_address").html(province_name+city_name+area_name+address);
				   $(".show_companyname").html(companyname);
				   $(".show_warehousing").html(warehousing);
				   $(".show_zipcode").html(zipcode);
				   $(".show_mobile").html(mobile);
				   $(".show_tel").html(tel);
				   $(".address_show").css("display","block");
		           $(".address_add_edit").css("display","none");
			   }else{
				   alert(arr.message);   
			   }
			}
	    });
	});
});

//选择改变地址
function editchange(id,tmp){
	if(tmp=='add')
	{
		var addradio=document.getElementsByName("addressradio");
		for (var i=addradio.length-1;i>=0;i--)
	    {
		   addradio[i].checked = false;
		}
		document.getElementById("addid").value = '';
		document.getElementById("shrname").value = '';
		$("#provinceSpan").load("/common/getprovince");
		$("#citySpan").load("/common/getcity");
		$("#areaSpan").load("/common/getarea");
		document.getElementById("address").value = '';
		document.getElementById("zipcode").value = '';
		document.getElementById("mobile").value = '';
		document.getElementById("tel").value = '';
		document.getElementById("default").checked = false;
	}else{
	$.ajax({
	   url: '/icwebadmin/OrUnso/getaddress',
		data: {'id':id},
		type: 'post',
		dataType: 'json',
		success: function(arr) {
			if(arr.code == 0)
			{
				document.getElementById("addid").value = id;
				document.getElementById("shrname").value = arr.re.name;
				$("#provinceSpan").load("/common/getprovince?provinceid="+arr.re.province);
				$("#citySpan").load("/common/getcity?provinceid="+arr.re.province+"&cityid="+arr.re.city);
				$("#areaSpan").load("/common/getarea?cityid="+arr.re.city+"&areaid="+arr.re.area);
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