//购物车输入数据检查
var setAmount={
	reg:function(x){
		if(x=='') {
			//document.getElementById("buyaction").innerHTML='<a class="button"  class="button" onclick="alert(\'请输入数量\');">去结算</a>';
			return true;
		}else {
			//document.getElementById("buyaction").innerHTML='<a class="button"  class="button" onclick="order()">去结算</a>';
		}
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
			alertbox("请输入正确的数量！");
			$(obj).val(moq);
			$(obj).focus();
		}
		return parseInt(x);						
	},
	reduce:function(obj,input,rowid,moq,mpq){
		var x=this.amount(obj,input,false,moq,mpq);
		moq = parseInt(moq);
		mpq = parseInt(mpq);
		if((moq%mpq)==0 && mpq!=0){
			if(x<moq){
				alertbox("不要低于最少购买数量:"+moq+"！");
			    $(obj).val(moq);
				$(input).val(moq/mpq);
			}else $(obj).val(x);
		}else{
			if (x>=moq){
			   $(obj).val(x);
		    }else{
			   alertbox("不要低于最少购买数量:"+moq+"！");
			   $(obj).val(moq);
			   $(obj).focus();
		    } 
		}
		recalculated(obj,input,rowid,moq,mpq);
	},
	add:function(obj,input,rowid,moq,mpq){
		var x=this.amount(obj,input,true,moq,mpq);
		$(obj).val(x);
		recalculated(obj,input,rowid,moq,mpq);
	},
	modify:function(obj,rowid,moq,mpq){
		var x=$(obj).val();
		moq = parseInt(moq);
		mpq = parseInt(mpq);
		if (!this.reg(x)){
			alertbox("请输入正确的数量！");
			$(obj).val(moq);
			$(obj).focus();
		}else{
	        if (x>=moq){
			   $(obj).val(x);
		    }else{
				if(x=='') {$(obj).focus();return;};
			   alertbox("不要低于最少购买数量:"+moq+"！");
			   $(obj).val(moq);
			   $(obj).focus();
		    }
		}
		recalculated(obj,'',rowid,moq,mpq);
	},
	multiple:function(obj,input,rowid,moq,mpq){
		var x=$(input).val();
		var num = $(obj).val();
		if (!this.reg(x)){
			alertbox("请输入正确的数量！");
			$(input).val(moq/mpq);
			$(obj).val(moq);
			$(input).focus();
		}else{
			if(x=='') {$(obj).focus();return;};
			$(obj).val(x*mpq);
		}
		recalculated(obj,input,rowid,moq,mpq);
	}
}
//当购买数量超过库存，弹出提示
var outride_show_cart = 1;
var old_partid_cart   = 0;
function outride_buynum_car(id,stock,rowid)
{
	if(outride_show_cart==1 || old_partid_cart!=rowid){
	    outride_show_cart = 0;
	    old_partid_cart = rowid;
		$.openPopupLayer({
		  name: "outrideBox",
		  url: "/cart/outridebuynum?id="+id+"&stock="+stock
		});
	}
}
//重新计算
function recalculated(obj,input,rowid,moq,mpq)
{
   var newvale = parseInt($(obj).val());
   $.ajax({
            url: '/cart/recalculated',
            data: {'rowid':rowid,'qty':newvale},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
			   if(arr.code==0)
			   {  
			      if(arr.surplus == 1){
					//对话框
					//outride_buynum_car(arr.id,arr.surplus,rowid);
					 alert("很抱歉，库存不足");
					 location.reload();
					 return;
				  }
				  $('[name=price_'+rowid+']')[0].innerHTML    =arr.price;
				  $('[name=itemtotal_'+rowid+']')[0].innerHTML=arr.itemtotal;
			   }else if(arr.code==100)
			   {
				   	outride_buynum(arr.surplus);
				   //alert(arr.message);
				   //location.reload();
			   }else{
			   	  alertbox(arr.message);
			   }
            }
    });
}
//SelectAll
function selectall(){
     var getChkboxs=document.getElementsByName("checkItem");
	 var allbox=document.getElementById('allbox').checked;
	 var reducebut=document.getElementsByName("reducebut");
	 var pamountbut=document.getElementsByName("pamountbut");
	 var multiplebut=document.getElementsByName("multiplebut");
	 var addbut=document.getElementsByName("addbut");
	 var rowids = new Object();
	 var checked = true;
	 if(allbox)
	 {
	    for (var i=getChkboxs.length-1;i>=0;i--)
	    {
		   getChkboxs[i].checked = checked = true;
		   rowids[getChkboxs[i].value]=getChkboxs[i].value;
		   reducebut[i].disabled="";
		   addbut[i].disabled="";
		   pamountbut[i].disabled="";
		   multiplebut[i].disabled="";
	    }
	 }else
	 {
	 	for (var i=getChkboxs.length-1;i>=0;i--)
	    {
		   getChkboxs[i].checked = checked = false;
		   rowids[getChkboxs[i].value]=getChkboxs[i].value;
		   reducebut[i].disabled="disabled";
		   addbut[i].disabled="disabled";
		   pamountbut[i].disabled="disabled";
		   multiplebut[i].disabled="disabled";
	    }
	 }
	 changeitem(rowids,checked);
}
//SelectOne
function selectone(obj,rowid){
     var getChkboxs=document.getElementsByName("checkItem");
	 var reducebut=document.getElementsByName("reducebut");
	 var pamountbut=document.getElementsByName("pamountbut");
	 var multiplebut=document.getElementsByName("multiplebut");
	 var addbut=document.getElementsByName("addbut");
	 var rowids    = new Object();
	 var allchecked = true;
	 
	 for (var i=getChkboxs.length-1;i>=0;i--)
	 {
		 if(getChkboxs[i].checked==false){
		    rowids[getChkboxs[i].value]=getChkboxs[i].value;
		    allchecked = false;
			reducebut[i].disabled="disabled";
		    addbut[i].disabled="disabled";
			pamountbut[i].disabled="disabled";
			multiplebut[i].disabled="disabled";
	     } else{
		   reducebut[i].disabled="";
		   addbut[i].disabled="";
		   pamountbut[i].disabled="";
		   multiplebut[i].disabled="";
		 }
	 }
	
	document.getElementById('allbox').checked=allchecked;
	changeitem(rowids,allchecked);
}
//changeitem
function changeitem(rowids,checked)
{
	$.ajax({
            url: '/cart/changeitem',
            data: {'rowids':rowids,'checked':checked},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code==0)
				{
				   $('[name=total_quantity]')[0].innerHTML     =arr.total_quantity;
				   //$('[name=freight]')[0].innerHTML            =arr.freight;
				   $('[name=cart_total]')[0].innerHTML         =arr.cart_total;
				}
            }
    });
}
//删除所有
function delectallcart()
{
	 var getChkboxs=document.getElementsByName("checkItem");
	 var rowids = new Object();
	 var ok=false;
	 for (var i=getChkboxs.length-1;i>=0;i--)
	 {
		 if(getChkboxs[i].checked)
		 {
		    rowids[getChkboxs[i].value]=getChkboxs[i].value;
			ok = true;
		 }
	 }
	 if(!ok) alertbox('请至少选择一件产品')
	 else delectcart(rowids);
}
//更改交货地
function changeplace(rowids)
{
	$.ajax({
            url: '/cart/changeplace',
            data: {'rowids':rowids},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code==0)
				{
				    location.reload();
				}else{
					alertbox(arr.message);//alert(arr.message);	
				}
            }
    });
}

//结算
function order(delivery_place)
{
	$.ajax({
            url: '/cart/checkorder',
            data: {'delivery_place':delivery_place},
            type: 'post',
            dataType: 'json',
            success: function(arr) {
            	if(arr.code==0){
				    window.location.href='/order?key='+arr.key+'&items='+arr.items;
				}else if(arr.code==102) {
					
					var submit = function (v, h, f) {
                        if (v == 'ok') window.location.href='/order?key='+arr.key+'&items='+arr.items;
                        return true; //close
                     };
                    $.jBox.confirm(arr.message, "提示", submit);		
				}else {alertbox(arr.message);}
            }
    });
}