jQuery(document).ready(function($) {

	var bar = $('.secure-attachments-upload-bar');
	var percent = $('.secure-attachments-upload-percent');
	var status = $('#secure-attachments-upload-status');
	var filelist = $('.secure-attachments-list');

    resetValuesForSecureAttachmentsFileDetails();
	
	$('#upload-secure-attachment-button').click(function(){

        if($('#ajaxaction[name=action]').length == 0) {
            var input = $("<input>").attr("type", "hidden").attr("name", "action").attr("id", "ajaxaction").val("saud");
            $('form#post').append($(input));
        }

        percent.fadeIn();
        bar.fadeIn();

        $('form#post').ajaxSubmit({url : ajaxUploadDocument.url,
			beforeSend: function() {
		        status.empty();
		        var percentVal = '0%';
		        bar.width(percentVal)
		        percent.html(ajaxUploadDocument.loading_text + percentVal);
		    },
		    uploadProgress: function(event, position, total, percentComplete) {
		        var percentVal = percentComplete + '%';
		        bar.width(percentVal)
		        percent.html(ajaxUploadDocument.loading_text + percentVal);
		    },
		    success: function(data) {
		        var percentVal = '100%';
		        bar.width(percentVal)
		        percent.html(ajaxUploadDocument.loading_complete);
		    },
			complete: function(xhr) {
		    	data = $.parseJSON(xhr.responseText);
		    	
		    	if(data.status > 0){
		    		resetFormAttachNewDocument();
		    		uniqueID = new Date().getTime();
		    		
		    		html_visibility = '';
		    		//if(data.visibility == 'sector') html_visibility = ' <a href="#" class="file-attached-permission" title="documento riservato agli utenti soci del comparto">(C)</a> ';
		    		//if(data.visibility == 'working-group') html_visibility = ' <a href="#" class="file-attached-permission" title="documento riservato ai gruppi di utenti: ' + data.working_groups_labels + '">(G)</a> ';
				    html = '<span class="secure-attachments-item" id="file-' + uniqueID + '" class="secure-attachments-doc">'
				    html += '<a href="' + data.name + '" class="ntdelbutton" data-value="' + uniqueID + '" data-action="remove-attached-document"></a> '
				    html += html_visibility
				    html += '<a href="' + ajaxUploadDocument.getDocUrl + '?post_ID=' + data.post + '&file_name=' + data.name + '"> '
				    html += data.title + " (" + data.size + ")"
				    html += '</a>'
				    html += '<br></span>'
				    $(html).hide().appendTo(filelist).fadeIn();
                    $('#no-secure-attachments').hide();
                    resetSecureAttachmentsFile();

		    	}else{
		    		html = '<div class="error">' + data.msg + '</div>'
		    		status.hide().html(html).fadeIn();
		    	}

                $('#ajaxaction').remove();
                percent.delay(500).fadeOut();
                bar.delay(500).fadeOut();

            }});
		
	});
	
	$(document).on('click', '[data-action=remove-attached-document]', function(e){
		e.preventDefault();
		data = {action: 'sard',
			file_name: $(this).attr('href'),
			span_file_id: $(this).attr('data-value'),
			post_ID: $('#post_ID').val(),
            _wpnonce_secure_attachments_remove: $('#_wpnonce_secure_attachments_remove').val()
		};
		
		$.post(ajaxurl, data, function(response) {
			if(response.status > 0){
				$('span#file-'+response.span_file_id).fadeOut(function() { $(this).remove(); })
                if($('.secure-attachments-item:visible').length <= 1){
                    $('#no-secure-attachments').slideDown('slow');
                }

			}else{
				html = '<div class="error">' + response.msg + '</div>'
			    status.hide().html(html).fadeIn();
			}
		}, 'json');

		return false;
		
	});


    // file details
    $('#secure-attachments-file').change(function(e){
        if($('#secure-attachments-file').val() != ''){
            $('#secure-attachments-file-details').slideDown('slow');
            defaultValuesForSecureAttachmentsFileDetails();
        }
    });

    $('#cancel-secure-attachment-button').click(function(e){
        resetSecureAttachmentsFile();
        $('.error').remove();
    });

    function resetSecureAttachmentsFile(){
        $('#secure-attachments-file-details').slideUp('slow');
        resetValuesForSecureAttachmentsFileDetails();
    }

    function defaultValuesForSecureAttachmentsFileDetails(){
        $('#secure-attachments-file-title').val('');
        $('#secure-attachments-file-description').val('');
        $('#secure-attachments-file-order').val('0');
    }

    function resetValuesForSecureAttachmentsFileDetails(){
        $('#secure-attachments-file').val('');
        $('#secure-attachments-file-title').val('');
        $('#secure-attachments-file-description').val('');
        $('#secure-attachments-file-order').val('');
    }




    // visibility not yet used leave them now
	$('.edit-visibility-file-attached').click(function(e){
		e.preventDefault();
		$('.edit-visibility-file-attached').hide();
		$('#file-attached-visibility-select').slideDown('slow');
	});
	
	$('.cancel-file-attached-visibility').click(function(e){
		e.preventDefault();
		
		restoreVisibilityNewFileAttached();

		$('div#notice-file-attached').hide();

		$('#file-attached-visibility-select').slideUp('slow');
		$('.edit-visibility-file-attached').show();
	});
	
	$('.save-file-attached-visibility').click(function(e){
		e.preventDefault();
		
		$('div#notice-file-attached').hide();
		
		if(!validateVisibilityNewFileAttached()){
			$('div#notice-file-attached').show();
			return;
		}
		
		new_value = $('input[name=visibility_file_attached]:checked', 'form#post').val();
		
		setVisibilityNewFileAttached(new_value);
		
		$('#file-attached-visibility-select').slideUp('slow');
		$('.edit-visibility-file-attached').show();
		
	});
	
	$('[name=visibility_file_attached]').change(function(e){
		toggleWorkingGroupsSelect();
	});
	
	$('.file-attached-permission').click(function(e){
		e.preventDefault();
	});
	
});

function validateVisibilityNewFileAttached(){
	visibility = jQuery('input[name=visibility_file_attached]:checked', 'form#post').val();
	working_groups = jQuery("#file_attached_working_groups").val();
	
	if((visibility == 'working-group' && !working_groups) || (visibility == 'working-group' && working_groups.length == 1 && working_groups[0] == 0) )
		return false;
	
	return true;	
}

function setVisibilityNewFileAttached(new_value){
	jQuery('#hidden-file-attached-visibility-working-groups').val('');
	if(new_value == 'post') {
		jQuery('#hidden-file-attached-visibility').val('post');
		testo_da_visualizzare = 'Articolo';
	}else if(new_value == 'sector') {
		jQuery('#hidden-file-attached-visibility').val('sector');
		testo_da_visualizzare = 'Riservato al comparto';
	}else if(new_value == 'working-group') {
		jQuery('#hidden-file-attached-visibility').val('working-group');
		testo_da_visualizzare = 'Gruppo di utenti bbb';
		working_groups = jQuery("#file_attached_working_groups").val();
		if(working_groups[0] == 0 && working_groups.length > 1){
			working_groups.splice(0,1);
			jQuery('#hidden-file-attached-visibility-working-groups').val(working_groups.join(','));
		}else if(working_groups[0] != 0){
			jQuery('#hidden-file-attached-visibility-working-groups').val(working_groups.join(','));
		}
	}
	jQuery('#file-attached-visibility-display').html(testo_da_visualizzare);
}

function restoreVisibilityNewFileAttached(){
	prev_value =  jQuery('#hidden-file-attached-visibility').val();
	setVisibilityNewFileAttached(prev_value);	
	jQuery('input[name=visibility_file_attached]', 'form#post').filter('[value='+prev_value+']').prop('checked', true);
	toggleWorkingGroupsSelect();
}

function toggleWorkingGroupsSelect(){
	new_value = jQuery('input[name=visibility_file_attached]:checked', 'form#post').val();
	if(new_value == 'working-group'){
		displayWorkingGroupsFileAttached();
	}else{
		hideWorkingGroupsFileAttached();			
	}	
}

function displayWorkingGroupsFileAttached(){
	jQuery('#working-group-file-attached-span').show();
}

function hideWorkingGroupsFileAttached(){
	jQuery('#working-group-file-attached-span').hide();
}

function resetFormAttachNewDocument(){
	jQuery('#file-attached-visibility-display').html('Articolo');
	jQuery('#hidden-file-attached-visibility').val('post');
	jQuery('#hidden-file-attached-visibility-working-groups').val('');
	jQuery('#file_attached_working_groups').val('');
	jQuery('input[name=visibility_file_attached]', 'form#post').filter('[value=post]').prop('checked', true);
	jQuery('#acd-attach-documents-file').val('');
}
