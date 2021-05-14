jQuery(function()
{
	jQuery("div.notice button.notice-dismiss").click(function (event){
		data = {
			'action': 'mtekk_admin_message_dismiss',
			'uid': jQuery(this).parent().children("meta[property='uid']").attr("content"),
			'nonce': jQuery(this).parent().children("meta[property='nonce']").attr("content")
		};
		jQuery.post(ajaxurl, data);
	});
});