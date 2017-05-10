//查看订单
function viewLineSo(salesnumber){
	$.openPopupLayer({
		name: "viewsoBox",
		url: "/icwebadmin/OrOrgl/viewso?salesnumber="+salesnumber
	});
}
//查看用户信息
function viewUser(uid)
{
	$.openPopupLayer({
        name:'viewBox',
        url:'/icwebadmin/UsUsgl/view/uid/'+uid
    });
}
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
function onloadadd(provinceid,cityid,areaid)
{
	$("#provinceSpan").load("/common/getprovince?provinceid="+provinceid);
	if(provinceid)
		$("#citySpan").load("/common/getcity?provinceid="+provinceid+"&cityid="+cityid);
	if(cityid)
		$("#areaSpan").load("/common/getarea?cityid="+cityid+"&areaid="+areaid);
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
function checkEmail(el)//用正则表达式判断
{
var regu = "^(([0-9a-zA-Z]+)|([0-9a-zA-Z]+[_.0-9a-zA-Z-]*[0-9a-zA-Z-]+))@([a-zA-Z0-9-]+[.])+([a-zA-Z]|net|NET|asia|ASIA|com|COM|gov|GOV|mil|MIL|org|ORG|edu|EDU|int|INT|cn|CN|cc|CC)$"
var re = new RegExp(regu);
if(el.search(re) == -1)
{
return true; //非法
}
return false;//正确
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

function openbox(url)
{
	 $.openPopupLayer({
        name:'box',
        url:url
    });
}
function openbox2(url)
{
	 $.openPopupLayer({
        name:'box2',
        url:url
    });
}