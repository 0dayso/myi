$(function(){
	
	//$(".alert").alert('close');
		$('a.colorbox').click(function(){
				var url = this.href;
				$.openPopupLayer({
			        name:'addBox',
			        url:url
			    });
			    return false;
		});
		$('a.del_click').click(function(){
			
			return confirm("Are you sure to delete this ?");
		});
		$('#parts').typeahead({
			items:8,
			source: ['AS179-92LF','MC908MR8CDWE','MC9S08AC16CFGE','MC9S08AW32CFUE']
			
		});
		
		$('.form-validate').validate();
		var editor;
		KindEditor.ready(function(K) {
			editor = K.create('.kindeditor', {
				resizeType : 1,
				allowPreviewEmoticons : false,
				uploadJson : '/js/kindeditor/php/upload_json.php',
				fileManagerJson : '/js/kindeditor/php/file_manager_json.php',
				allowFileManager : true,				
				//allowImageUpload : false,
				items : [
					'source','|','fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
					'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
					'insertunorderedlist', '|', 'emoticons', 'image', 'link']
			});
		});

	
});