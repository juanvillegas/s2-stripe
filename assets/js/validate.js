var __DEBUG = false;

( function($){ 

	$.fn.validate = function(){
		var errors = new Array();
		var field_errors = new Array();
		var nicename = 'Field';

		this.find('.validate').each(function(){
			var value = $(this).val();
			var rules = jQuery(this).attr( 'data-rules' ).split(",");
			var in_error = false;
			$.each( rules, function(index, rule){
				if( rule == 'email' ){
					if( ! _validate_email( value ) ){
						field_errors.push( 'Unrecognized format for email address' );
						in_error = true;
					}
				}else if( rule == 'required' ){
					if( ! _validate_required( value ) ){
						field_errors.push( nicename + ' is required' );
						in_error = true;
					}
				}else if( rule == 'cc' ){
					if( ! _validate_cc( value.replace( /\s/g, '' ) ) ){
						field_errors.push( 'Credit Card Number must be 16 digits' );
						in_error = true;
					}
				}else if( rule == 'numeric' ){
					if( ! _validate_numeric( value ) ){
						field_errors.push( nicename + ' must only contain digits' );
						in_error = true;
					}
				}else if( rule == 'phone' ){
					if( ! _validate_phone( value ) ){
						field_errors.push( nicename + ' must be a valid North American phone' );
						in_error = true;
					}
				}else if( rule.indexOf('confirm_email') >= 0 ){
					var selector = rule.split(":")[1];
					if( ! _validate_confirm_email( value, selector ) ){
						field_errors.push( 'Emails must match' );
						in_error = true;
					}
				}else if( rule.indexOf('min_length') >= 0 ){
					var min = rule.split(":")[1];
					if( ! _validate_min_length( value, min ) ){
						field_errors.push( nicename + ' must contain at least ' + min + ' characters' );
						in_error = true;
					}
				}else if( rule.indexOf('max_length') >= 0 ){
					var max = rule.split(":")[1];
					if( ! _validate_max_length( value, max ) ){
						field_errors.push( nicename + ' cannot contain more than ' + max + ' characters' );
						in_error = true;
					}
				}else if( rule.indexOf('exact_length') >= 0 ){
					var exact = rule.split(":")[1];
					if( ! _validate_exact_length( value, exact ) ){
						field_errors.push( nicename + ' must contain exactly ' + exact + ' characters' );
						in_error = true;
					}
				}
			});

			// push error to stack
			if( in_error ){
				shout_error(this, field_errors );
				errors.push( field_errors );
			}

		});

		if( errors.length == 0 ){
			return true;
		}else{
			if( __DEBUG ){ console.log(errors); }
			return false;
		}
	}

	function shout_error( field, errors ){
		jQuery(field).addClass('error-field').val( errors[0] ).focus(function(){
			jQuery(field).val("").removeClass('error-field').unbind('focus');
		});
	}

	function _validate_exact_length( value, exact ){
		if( value.length == exact ){
			return true;
		}else{
			return false;
		}
	}
	function _validate_min_length( value, min ){
		if( value.length >= min ){
			return true;
		}else{
			return false;
		}
	}

	function _validate_max_length( value, max ){
		if( value.length <= max ){
			return true;
		}else{
			return false;
		}
	}

	function _validate_confirm_email( value, selector ){
		if( value == jQuery( selector ).val() ){
			return true;
		}else{
			return false;
		}
	}

	function _validate_cc( value ){
		return _validate_numeric(value) && _validate_exact_length(value, 16);
	}

	function _validate_numeric( value ){
		var re = /^[0-9]+$/;
		return re.test( value );
	}

	function _validate_email( value ){
		var re = /^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/;
		return re.test( value );
	}

	function _validate_phone( value ){
		var re = /^((([0-9]{1})*[- .(]*([0-9]{3})[- .)]*[0-9]{3}[- .]*[0-9]{4})+)*$/;
		return re.test( value );
	}

	function _validate_required( value ){
		if( value == '' ){
			return false;
		}else{
			return true;
		}
	}
	
}( jQuery ) );
