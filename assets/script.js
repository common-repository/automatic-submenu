jQuery(document).ready(function($){
	// initial showing fields
	$('input[type="checkbox"][name^="menu-item-automatic"]').each(function(index,el){
		var id = $(el).attr('id').split('-').pop();
		if($(el).is(':checked')){
			$('#menu-item-settings-'+id).find('.field-automatic-max, .field-automatic-pos, .field-automatic-order').removeClass('hidden-field');
		} else {
			$('#menu-item-settings-'+id).find('.field-automatic-max, .field-automatic-pos, .field-automatic-order').addClass('hidden-field');
		}
	});
	// check event handler
	$('input[type="checkbox"][name^="menu-item-automatic"]').change(function(){
		var id = $(this).attr('id').split('-').pop();
		if($(this).is(':checked')){
			$('#menu-item-settings-'+id).find('.field-automatic-max, .field-automatic-pos, .field-automatic-order').removeClass('hidden-field');
		} else {
			$('#menu-item-settings-'+id).find('.field-automatic-max, .field-automatic-pos, .field-automatic-order').addClass('hidden-field');
		}
	});
});