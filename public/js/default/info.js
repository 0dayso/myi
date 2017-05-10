// JavaScript Document
   $(document).ready(function(){
		//第一组校验组，默认组号为"1"
        $.formValidator.initConfig({formID:"basis",onSuccess:function(){submit();}});
		$("#mobile").formValidator({empty:false,onFocus:"请输入你的11位手机号码",onCorrect:"&nbsp;"}).inputValidator({min:11,max:11,onError:"手机号码必须是11位的,请确认"}).regexValidator({regExp:"mobile",dataType:"enum",onError:"你输入的手机号码格式不正确"});
		$("#birthday").formValidator({onFocus:"请输入的出生日期",onCorrect:"&nbsp;"})
		.inputValidator({min:"1900-01-01",max:"29999-01-01",type:"date",onError:"请输入出生日期"});
		
		//第二组校验组，组号为"2" 
        $.formValidator.initConfig({validatorGroup:"2",formID:"company",onSuccess:function(){submit();}});
       $("#companyname").formValidator({validatorGroup:"2",onFocus:"请输入公司名称",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入公司名称"});
	   $("#contact").formValidator({validatorGroup:"2",onFocus:"请输入联系人",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"不能为空,请输入联系人"});
	   $("#tel").formValidator({validatorGroup:"2",empty:false,onFocus:"格式例如：0577-88888888-111",onCorrect:"&nbsp;",onEmpty:"请输入电话号码"}).regexValidator({regExp:"^(([0\\+]\\d{2,5}-)?(0\\d{2,5})-)?(\\d{7,8})(-(\\d{3,6}))?$",onError:"请输入正确的联系电话"});
	   $("#fax").formValidator({validatorGroup:"2",empty:false,onFocus:"格式例如：0577-88888888",onCorrect:"&nbsp;",onEmpty:"请输入传真号"}).regexValidator({regExp:"^(([0\\+]\\d{2,5}-)?(0\\d{2,5})-)?(\\d{7,8})(-(\\d{3,6}))?$",onError:"请输入正确的传真号"});
	   $("#areatmp").formValidator({validatorGroup:"2",empty:false,onFocus:"请输选择所在地区",onCorrect:"&nbsp;"}).inputValidator({min:1,onError:"请输选择所在地区"});
	   
	   $("#address").formValidator({validatorGroup:"2",onFocus:"请输入详细地址，不需要重复填写省/市/区",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:true,emptyError:"不能有空符号"},onError:"不能为空,请输入详细地址"});
	   $("#zipcode").formValidator({validatorGroup:"2",empty:true,onFocus:"请输入邮编",onCorrect:"&nbsp;",onEmpty:"可为空"}).regexValidator({regExp:"zipcode",dataType:"enum",onError:"请输入正确的邮编"});
	   
		$("#uploadtext1").formValidator({validatorGroup:"2",empty:true, onFocus:"请上传正确格式的附件",onEmpty:"可为空",onCorrect:"&nbsp;"}).regexValidator({regExp:"file",dataType:"enum",onError:"附件格式不正确" })
		$("#uploadtext2").formValidator({validatorGroup:"2",empty:true, onFocus:"请上传正确格式的附件",onEmpty:"可为空",onCorrect:"&nbsp;"}).regexValidator({regExp:"file",dataType:"enum",onError:"附件格式不正确",onCorrect:"&nbsp;"})
		
		$("#uploadtext3").formValidator({validatorGroup:"2",empty:true, onFocus:"请上传正确格式的附件",onEmpty:"可为空",onCorrect:"&nbsp;"}).regexValidator({regExp:"file",dataType:"enum",onError:"附件格式不正确" })

	   //第三组校验组，组号为"3"
        $.formValidator.initConfig({validatorGroup:"3",formID:"changepass",onSuccess:function(){submit();}});
		
       	$("#oldpass").formValidator({validatorGroup:"3",onFocus:"请输入旧密码，至少6个长度",onCorrect:"&nbsp;"}).inputValidator({min:6,empty:{leftEmpty:false,rightEmpty:false,emptyError:"密码两边不能有空符号"},onError:"密码不能为空,请确认"})
		.ajaxValidator({
		dataType : "json",
		async : true,
		url : "/center/checkpass",
		success : function(data){
            if(data.code==0) return true;
			else return false;
		},
		buttons: $("#but3"),
		onError : "密码错误",
		onWait : ""
	    });
		$("#newpass").formValidator({validatorGroup:"3",onFocus:"请输入新密码，至少6个长度",onCorrect:"&nbsp;"}).inputValidator({min:6,empty:{leftEmpty:false,rightEmpty:false,emptyError:"密码两边不能有空符号"},onError:"密码不能为空,请确认"});
		$("#newpass2").formValidator({validatorGroup:"3",onFocus:"输再次输入新密码",onCorrect:"&nbsp;"}).inputValidator({min:6,empty:{leftEmpty:false,rightEmpty:false,emptyError:"重复密码两边不能有空符号"},onError:"重复密码不能为空,请确认"}).compareValidator({desID:"newpass",operateor:"=",onError:"2次密码不一致,请确认"});
	})
   
    //轮转
    $(function(){
        var tab = $(".order_tab_head li");
        tab.click(function(){
            $(this).addClass("on").siblings().removeClass("on");		
            var index = tab.index(this);
            $(".table_box").eq(index).show().siblings().hide();	
        });
     })

    function selectCity(){
		var provinceid=$("#province").val();
		$("#citySpan").load("/common/getcity?provinceid="+provinceid);
	}
	function selectArea(){
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