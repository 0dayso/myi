$(function()   
{   
  var hideDelay = 500;     
  var currentID;   
  var hideTimer = null;
  // One instance that's reused to show info for the current person 
  $('.personPopupCart').live('hover', function()   
  {
	  var courier_url = $(this).attr('rev');
      var typeid = $(this).attr('rel');

	  var container = $('#personPopup'+typeid);      
      // If no guid in url rel tag, don't popup blank   
      if (typeid == '' || courier_url == '')
          return;   
  
      if (hideTimer)
          clearTimeout(hideTimer);
  
      var pos = $(this).offset();   
      var width = $(this).width();

	  $.ajax({
           url: courier_url,
           data: {},
           type: 'post',
           dataType: 'html',
           success: function(html) {
               $('#content'+typeid).html(html);   
           },
		   timeout:6000
	});
   container.css('display', 'block');
	
  $('.personPopupCart').live('mouseout', function()   
  {   
      if (hideTimer)   
          clearTimeout(hideTimer);   
      hideTimer = setTimeout(function()   
      {   
          container.css('display', 'none');   
      }, hideDelay);   
  });   
  
  // Allow mouse over of details without hiding details   
  container.mouseover(function()   
  {   
      if (hideTimer)   
          clearTimeout(hideTimer);   
  });   
  
  // Hide after mouseout   
  container.mouseout(function()   
  {   
      if (hideTimer)   
          clearTimeout(hideTimer);   
      hideTimer = setTimeout(function()   
      {   
          container.css('display', 'none');   
      }, hideDelay);   
  });  
  });    
});