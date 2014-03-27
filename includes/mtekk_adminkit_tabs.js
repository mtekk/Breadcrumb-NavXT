jQuery(function()
{
	mtekk_admin_tabulator_init();
});
/**
 * Tabulator Bootup
 */
function mtekk_admin_tabulator_init(){
	if(!jQuery("#hasadmintabs").length) return;
	/* init markup for tabs */
	jQuery('#hasadmintabs').prepend('<ul class="nav-tab-wrapper"><\/ul>');
	jQuery('#hasadmintabs > fieldset').each(function(i){
		id = jQuery(this).attr('id');
		cssc = jQuery(this).attr('class');
		title = jQuery(this).find('h3.tab-title').attr('title');
		caption = jQuery(this).find('h3.tab-title').text();
		jQuery('#hasadmintabs > ul').append('<li><a href="#'+id+'" class="nav-tab '+cssc+'" title="'+title+'"><span>'+caption+"<\/span><\/a><\/li>");
		jQuery(this).find('h3.tab-title').hide();
	});
	var form   = jQuery('#'+objectL10n.mtad_uid+'-options');
	/* init the tabs plugin */
	var tabs = jQuery("#hasadmintabs").tabs({
		beforeActivate: function(event, ui){
			form.find('input').each(function(){
				if(!this.checkValidity()){
					form.find(':submit').click();
					event.preventDefault();
				}
			});
			/* Update form action for reload on tab traversal*/
			var action = form.attr("action").split('#', 1) + '#' + ui.newPanel[0].id;
			form.get(0).setAttribute("action", action);
		},
		create: function(event, ui){
			/* Update form action for reload of current tab on page load */
			var action = form.attr("action").split('#', 1) + '#' + ui.panel[0].id;
			form.get(0).setAttribute("action", action);
		}
		});
}