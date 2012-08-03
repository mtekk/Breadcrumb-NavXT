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
	/* init the tabs plugin */
	var tabs = jQuery("#hasadmintabs").tabs();
	var form   = jQuery('#'+objectL10n.mtad_uid+'-options');
	var action = form.attr("action").split('#', 1) + '#' + jQuery('#hasadmintabs > fieldset').eq(tabs.tabs('option', 'selected')).attr('id');
	form.get(0).setAttribute("action", action);
	/* handler for opening the last tab after submit (compability version) */
	jQuery('#hasadmintabs ul a').click(function(i){
		var form   = jQuery('#'+objectL10n.mtad_uid+'-options');
		var action = form.attr("action").split('#', 1) + jQuery(this).attr('href');
		form.get(0).setAttribute("action", action);
	});
}