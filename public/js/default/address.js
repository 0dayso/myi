// JavaScript Document
   $(document).ready(function(){
        $.formValidator.initConfig({formID:"addressform",onSuccess:function(){changeaddress();},onError:function(msg,obj){showphonemsg(msg,obj)}});
       $("#shrname").formValidator({onFocus:"请输入收货人名称",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:false,emptyError:"不能有空符号"},onError:"请输入收货人名称"});
	   $("#areatmp").formValidator({empty:false,onFocus:"请输选择所在地区",onCorrect:"&nbsp;"}).inputValidator({min:1,onError:"请输选择所在地区"});
	   $("#address").formValidator({onFocus:"请输入详细地址，不需要重复填写省/市/区",onCorrect:"&nbsp;"}).inputValidator({min:2,empty:{leftEmpty:false,rightEmpty:true,emptyError:"不能有空符号"},onError:"请输入详细地址"});
	   $("#zipcode").formValidator({empty:true,onFocus:"请输入邮编",onCorrect:"&nbsp;",onEmpty:"不能为空"}).regexValidator({regExp:"zipcode",dataType:"enum",onError:"请输入正确的邮编"});
       $("#mobile").formValidator({empty:true,onFocus:"请输入你的手机号码",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).inputValidator({min:11,max:13,onError:"手机号码必须是11位"}).regexValidator({regExp:"mobile",dataType:"enum",onError:"你输入的手机号码格式不正确"});
	    $("#tel").formValidator({empty:true,onFocus:"格式例如：0577-88888888",onCorrect:"&nbsp;",onEmpty:"&nbsp;"}).regexValidator({regExp:"^((\\d{2,5}-)?(\\d{2,5})-)?(\\d{7,8})(-(\\d{3,}))?$",onError:"请输入正确的固定电话,格式例如：0577-88888888"});
})
    function selectCity(){
		var provinceid=$("#province").val();
		$("#citySpan").load("/common/getcity?provinceid="+provinceid);
		$("#areaSpan").html("<select id=\"area\" name=\"area\"><option value=\"\">请选择区</option></select>");
		document.getElementById("areatmp").value = '';
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
		else submit();
	}
//删除
function deleteaction(ID)
{
	if(confirm('你确定删除收货地址吗？'))
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
//修改默认
function defaultaction(ID)
{
	$.ajax({
            url: '/center/address',
            data: {'ID':ID,'type':'default'},
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