//添加行
function addEvent(mfr,partno,buynum,price,deliverydate,remarks,mms,pms,nms) {
  var ni = document.getElementById('myDiv');
  //总实际个数
  var vnum = document.getElementById('theValueNum');
  var vnumtmp = vnum.value;
  vnumtmp++;
  vnum.value = vnumtmp;
  //提示信息
  var mmsbox = pmsbox = nmsbox ='';
  if(pms!='') mmsbox='<font color="#FF0000">'+mms+'</font>';
  if(pms!='') pmsbox='<font color="#FF0000">'+pms+'</font>';
  if(nms!='') nmsbox='<font color="#FF0000">'+nms+'</font>';
  //一共添加个数，包括删除的
  var numi = document.getElementById('theValue');
  var num = (document.getElementById("theValue").value -1)+ 2;
  numi.value = num;
  
  var divIdName = "my"+num+"Div";
  var newdiv = document.createElement('div');
  newdiv.setAttribute("id",divIdName);  
  newdiv.innerHTML = "<table width=\"100%\"><tr><td  class=\"bgsep\" width=\"15%\"><input id=mfr_"+num+" name=mfr_"+num+" size=10 value='"+mfr+"' /><p>"+mmsbox+"</p></td>"+
  "<td  class=\"bgsep\" width=\"18%\"><input id=partno_"+num+" name=partno_"+num+" size=12 value='"+partno+"' /><p>"+pmsbox+"</p></td>"+
  "<td  class=\"bgsep\" width=\"15%\"><input id=buynum_"+num+" name=buynum_"+num+" size=10 value='"+buynum+"'  onkeyup=\"value=value.replace(/[^\\d]/g,'')\"/><p>"+nmsbox+"</p></td>"+
  "<td  width=\"15%\"><input id=price_"+num+" name=price_"+num+" size=10 value='"+price+"'/></td>"+

  "<td  width=\"15%\"><input id=deliverydate_"+num+" name=deliverydate_"+num+" size=10 value='"+deliverydate+"'/></td>"+
  "<td  width=\"15%\"><input id=remarks_"+num+" name=remarks_"+num+" size=10 value='"+remarks+"' /></td><td width=\"10%\"><a href=\"javascript:;\" onclick=\"removeElement(\'"+divIdName+"\')\">移除</a></td>"+"</tr></table>"; 
  ni.appendChild(newdiv);
}

function removeElement(divNum) {
  //总实际个数
  var vnum = document.getElementById('theValueNum');
  var vnumtmp = vnum.value;
  vnumtmp--;
  vnum.value = vnumtmp;
  var d = document.getElementById('myDiv');
  var olddiv = document.getElementById(divNum);
  d.removeChild(olddiv);
}
function add()
{
	var divDropDown = document.getElementById('divDropDown');
	var divNewCustomer = document.getElementById('divNewCustomer');
	divDropDown.style.display = "none";
	divNewCustomer.style.display = "block";
}
function remove()
{
	var divDropDown = document.getElementById('divDropDown');
	var divNewCustomer = document.getElementById('divNewCustomer');
	divDropDown.style.display = "block";
	divNewCustomer.style.display = "none";
}
function changeMbprice(v)
{
	if(v=='RMB')
	{
	   $("#delivery").val('SZ');
	   $("#delivery_show").val('SZ');
	}
	else if(v=='HKD')
	{
	    $("#delivery").val('HK');
		$("#delivery_show").val('HK');
	 }
	else{ 
		 $("#delivery").val('HK');
		 $("#delivery_show").val('HK');
	}

}