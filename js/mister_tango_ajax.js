MistertangoJq = jQuery.noConflict();
 MistertangoJq(document).ready(function(){
	 
	if(MistertangoJq('#mistertango_ajax_after_form').length >0)
	{
		MistertangoJq('#mistertango_ajax_after_form').css("display","block");
	}
	var ajax_check_process_call = function() {
			var mistertango_lightbox_display = MistertangoJq('.mistertango_lightbox').css("display");
			if(mistertango_lightbox_display=='block')
			{
				return true;
			}
			 
			//your jQuery ajax code			
			mrTangoUrlProcessCheck = decodeURIComponent(mrTangoUrlProcessCheck);
			MistertangoJq.ajax({
			  url: mrTangoUrlProcessCheck,
			  type: 'POST',
			  dataType: "json",
			  async: true,
			  headers: { "cache-control": "no-cache" },
			  cache: false,
			  data : MistertangoJq('form.checkout').serialize(),
			  success: function(response){					
					if(response['msg']=='processing')
					{
						window.location.reload();						 
					}					
			  }
			});
		};
		
	
		if(MistertangoJq('#mistertango_ajax_after_form').length >0)
		{
			var interval = 15000; // where X is your every X minutes
			setInterval(ajax_check_process_call, interval);
		}
		
		
	
	if (typeof MistertangoJq(".mistertango-button-pay").attr("data-ws-id") != 'undefined') {
		MistertangoJq('#payment').css("display", "none");

		MistertangoJq('input[name=payment_method]').each(function (){
		var payment_method_id_attr= MistertangoJq(this).attr("ID");
		//if(payment_method_id_attr!='payment_method_mistertango')
		//{
			MistertangoJq('li.'+payment_method_id_attr).css("display", "none");
			
		//}		
		});	 
	}
});
 
MistertangoJq(document).on('click', '#place_order', function (e) {
  if(MistertangoJq( "#payment_method_mistertango").prop('checked')==false)
  {
	  return true;
  }
   e.preventDefault();   
	if(MistertangoJq('#mistertango_ajax_after_form').length)
	{
		mrTangoOrderId= MistertangoJq( "#mrTangoOrderId").val(); 
		MistertangoJq(".mistertango-button-pay").trigger("click");
	}
	else
	{	
		 
		MistertangoJq.ajax({
		  url: '?wc-ajax=checkout',
		  type: 'POST',
		  dataType: "json",
		  async: true,
		  headers: { "cache-control": "no-cache" },
		  cache: false,
		  data : MistertangoJq('form.checkout').serialize(),
		  success: function(response){
				if(response.result=='failure')
				{
					MistertangoJq('form.checkout').prepend(response.messages);
				}
				else if(response.result=='success')
				{
					var mrTangoOrderId =response.order_id;
					
					MistertangoJq( "#mrTangoOrderId").val(mrTangoOrderId);
					var button_html = response.button_html;
					MistertangoJq( "#mistertango_ajax_form").append( button_html);
				
					MistertangoJq(".mistertango-button-pay").trigger("click");
				}
		  }
		});
	}
    return false;
});
