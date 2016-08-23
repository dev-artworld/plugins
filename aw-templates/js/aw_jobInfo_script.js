( function( $ ) {
    $( function() {

      	//** Validation for Copy Widget Form. **//
      	$('form#aw_jobInfo_form').submit(function() {			  		
			
			var flag = true;

			$("#jobInfoFile").removeClass('aw_error');
			

			var jobInfoFile 	= $("#jobInfoFile").val();
			if (jobInfoFile == '') {
				
				$("#jobInfoMessage").addClass('aw_errorMessage');
				$("#jobInfoMessage").text('Please select a file first.');
								
				flag = false;

			}else if( !(/\.(csv)$/i).test( jobInfoFile ) ){
				
				$("#jobInfoMessage").addClass('aw_errorMessage');
				$("#jobInfoMessage").text('Please select file(.csv).');
				
				$("#jobInfoFile").val('');				
				flag = false;
			};


			if(flag == true ){
				return true;
			}else{
				return false;
			}
			
		});


    } );
} ( jQuery ) );