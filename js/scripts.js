/* JS SPR-IMPORT-POSTS */

jQuery(function($){
	$('.no-js').hide();
	
	// Preeeeetty lists
	$('#spr-import-posts .how-to li').each(function(){
		$(this).html('<span>'+$(this).html()+'</span>');
	});
	
	// Preeeeetty notes
	var notes_count = 0;
	$('#spr-import-posts .general-notes p').each(function(){
		var num_notes = $('#spr-import-posts .general-notes p').length;
		if(notes_count == 0 || notes_count == 1) $(this).addClass('first');
		if(notes_count == num_notes || notes_count == (num_notes - 1)) $(this).addClass('last');
		if(notes_count % 2) $(this).addClass('even').append('<span class="brder"><em></em></span>');
		else $(this).addClass('odd').append('');
		
		notes_count++;
	});
	
	// File upload
	$('input[name="src-file"]').change(function(){ $(this).closest('form').submit(); });
	
	// Automated full import
	var total	= parseInt($('input[name="total-entries"]').val());
	var e 		= parseInt($('input[name="entries-checked"]').val());
	//console.log(total,e);

	if($('input:checked[name="keep-going"]').val() == 'y' && total >= e) {		
		// Go on now, keep it moving
		var nextoffset = parseInt($('input[name="offset"]').val()) + parseInt($('input[name="limit"]').val());
		$('input[name="offset"]').delay(2000).val(nextoffset);
		$('form#spr-settings').submit();
	}
	
	// All posts have been imported
	else if(e >= total){
		$('#all-done').fadeIn();
	}
});
