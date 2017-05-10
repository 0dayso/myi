(function($) {
	$.fn.validationEngineLanguage = function() {};
	$.validationEngineLanguage = {
		newLang: function() {
			$.validationEngineLanguage.allRules = 	{"required":{    			// Add your regex rules here, you can take telephone as an example
						"regex":"none",
						"alertText":"* 不能为空",
						"alertTextCheckboxMultiple":"* Please select an option",
						"alertTextCheckboxe":"* This checkbox is required"},
					"length":{
						"regex":"none",
						"alertText":"*请输入 ",
						"alertText2":" - ",
						"alertText3": " 长度的字符"},
					"maxCheckbox":{
						"regex":"none",
						"alertText":"* Checks allowed Exceeded"},	
					"minCheckbox":{
						"regex":"none",
						"alertText":"* Please select ",
						"alertText2":" options"},	
					"confirm":{
						"regex":"none",
						"alertText":"* 两次输入的密码不一致"},		
					"telephone":{
						"regex":"/^[0-9\-\(\)\ ]+$/",
						"alertText":"* Invalid phone number"},	
					"email":{
						"regex":"/^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/",
						"alertText":"* 请输入正确的邮箱地址"},	
					"date":{
                         "regex":"/^[0-9]{4}\-\[0-9]{1,2}\-\[0-9]{1,2}$/",
                         "alertText":"* Invalid date, must be in YYYY-MM-DD format"},
					"onlyNumber":{
						"regex":"/^[0-9\ ]+$/",
						"alertText":"* 只能输入数字"},
				    "onlyUpper":{
						"regex":"/^[A-Z\ ]+$/",
						"alertText":"* 只能填入大写字母"},
					"noSpecialCaracters":{
						"regex":"/^[\u4E00-\u9FA5a-z0-9_.]+$/i",
						"alertText":"* 只支持中英文、数字、“.”或者“_”"},	
					"onlyLetter":{
						"regex":"/^[a-zA-Z\ \']+$/",
						"alertText":"* 只能填入字母"},
					"validate2fields":{
    					"nname":"validate2fields",
    					"alertText":"* You must have a firstname and a lastname"},
						"ajaxUname":{
						"file":"unamecheck",
						"alertTextOk":"* 用户名可用",	
						"alertTextLoad":"* Loading......",
						"alertText":"* 用户名已经存在"},	
					"ajaxEmail":{
						"file":"emailcheck",
						"alertTextOk":"* 邮箱可用",
						"alertText":"* 邮箱已经存在",
						"alertTextLoad":"* Loading......"},
					"ajaxCompanyname":{
						"file":"/icwebadmin/common/companycheck",
						"alertTextOk":"* 公司名可用",
						"alertText":"* 此公司已经在IC易站注册，不需要再添加",
						"alertTextLoad":"* Loading......"},
					"ajaxVerifycode":{
						"file":"verifycode",
						"alertTextOk":"* 验证成功",
						"alertText":"* 验证码错误",
						"alertTextLoad":"* Loading......"}
					}	
					
		}
	}
})(jQuery);

$(document).ready(function() {	
	$.validationEngineLanguage.newLang()
});