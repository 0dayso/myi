$(function()   
{   
  var hideDelay = 500;     
  var currentID;   
  var hideTimer = null;
  // One instance that's reused to show info for the current person  hover
  $('.personPopupTrigger').live('click', function()   
  {
	  var courier_url = $(this).attr('rev');
      // format of 'rel' tag: pageid,personguid   
      var settings = $(this).attr('rel').split(',');
      var sonid = settings[0];
	  var sonum = settings[1];
	  var container = $('#personPopupContainer'+sonid);      
      // If no guid in url rel tag, don't popup blank   
      if (sonum == '' || sonid == '')
          return;   
  
      if (hideTimer)
          clearTimeout(hideTimer);   
  
      var pos = $(this).offset();   
      var width = $(this).width();
      $('#personPopupContent'+sonid).html('<div style="height:63px;"><img src="/images/admin/ajax-loader.gif" /></div>');
	  $.ajax({
           url: courier_url,
           data: {'sonum':sonum,'sonid':sonid},
           type: 'post',
           dataType: 'html',
           success: function(html) {
               $('#personPopupContent'+sonid).html(html);   
           },
		   timeout:6000
	});
   container.css('display', 'block');
	
  $('.personPopupTrigger').live('mouseout', function()   
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