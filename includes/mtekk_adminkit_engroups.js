jQuery(function()
{
	jQuery(".adminkit-engroup input:checkbox.adminkit-enset-ctrl").each(mtekk_admin_enable_group);
	jQuery("input:checkbox.adminkit-enset-ctrl").each(mtekk_admin_enable_set);
});
function mtekk_admin_enable_group(){
	var setting = this;
	jQuery(this).parents(".adminkit-engroup").find("input").each(function(){
		if(this != setting){
			if(jQuery(setting).prop("checked")){
				jQuery(this).prop("disabled", false);
				jQuery(this).removeClass("disabled");
			}
			else{
				jQuery(this).prop("disabled", true);
				jQuery(this).addClass("disabled");
			}
		}
	});
}
function mtekk_admin_enable_set(){
	var setting = this;
	jQuery(this).parents(".adminkit-enset-top").find("input.adminkit-enset").each(function(){
		if(this != setting){
			if(jQuery(setting).prop("checked")){
				jQuery(this).prop("disabled", false);
				jQuery(this).removeClass("disabled");
			}
			else{
				jQuery(this).prop("disabled", true);
				jQuery(this).addClass("disabled");
			}
		}
	});
}
jQuery(".adminkit-engroup input:checkbox.adminkit-enset-ctrl").change(mtekk_admin_enable_group);
jQuery("input:checkbox.adminkit-enset-ctrl").change(mtekk_admin_enable_set);