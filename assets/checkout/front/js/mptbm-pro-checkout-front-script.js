(function ($) {
    "use strict";

	var allowDefaultBehavior = false;

	function reset_element(element)
	{
		element.find("label").removeClass("danger-text");
		element.find("input").removeClass("danger");
		element.find("span.danger-text").removeClass("danger-text").remove();
	}

	function set_element(element, spanText)
	{
		var text_of_span = '';
		element.find("label").addClass("danger-text");
		element.find("input").addClass("danger");
		if(spanText == " is required.")
		{
			text_of_span = element.find('label').text() + spanText;
		}
		else if(spanText == " is not valid phone number !")
		{
			text_of_span = 'Entered ' + element.find('label').text() + spanText
		}
		else if(spanText == " is not valid email address !")
		{
			text_of_span = 'Entered ' + element.find('label').text() + spanText
		}

		var spanElement = $("<span>", {
			class: 'danger-text',
			text: text_of_span
		});

		element.append(spanElement);
	}

	function isValidInput(element,validate_key)
	{
		if (element.hasClass("validate-required") && validate_key == 'validate-required')
		{
			if (element.find("input").val() == '')
			{
				return false;
			}
		}

		if (element.hasClass("validate-phone"))
		{
			if (!isValidPhone(element.find("input").val()) && validate_key == 'validate-phone')
			{
				return false;
			}
		}

		if (element.hasClass("validate-email"))
		{
			if (!isValidEmail(element.find("input").val()) && validate_key == 'validate-email')
			{
				return false;
			}
		}

		return true;

	}

    function customValidation()
	{
		var top = '';
		var fields = $('form[name="checkout"] .form-row');

		// Skip validation for custom fields that have values
		// Note: Field names might have spaces or underscores
		var customFields = [
			'billing_Passport_No', 'billing_Flight_No',
			'billing_Passport No', 'billing_Flight No'
		];
		var hasCustomFieldValues = false;

		// Check if custom fields have values
		$.each(customFields, function(index, fieldName) {
			// Try with both ID formats
			var fieldValue = $('#' + fieldName).val();
			var fieldId = fieldName + '_field';

			// If field not found, try with spaces replaced by underscores
			if (!fieldValue && fieldName.indexOf(' ') !== -1) {
				var altFieldName = fieldName.replace(/ /g, '_');
				fieldValue = $('#' + altFieldName).val();
				fieldId = altFieldName + '_field';
			}

			// If field not found, try with underscores replaced by spaces
			if (!fieldValue && fieldName.indexOf('_No') !== -1) {
				var altFieldName = fieldName.replace('_No', ' No');
				fieldValue = $('#' + altFieldName).val();
				fieldId = altFieldName + '_field';
			}

			if (fieldValue && fieldValue.trim() !== '') {
				console.log('Found value for field: ' + fieldName + ' = ' + fieldValue);
				hasCustomFieldValues = true;
				// Remove any validation classes
				$('#' + fieldId).removeClass('woocommerce-invalid woocommerce-invalid-required-field');
				$('#' + fieldId).addClass('woocommerce-validated');
			}
		});

		fields.each(function(index, element) {

			var invalid = false;
			var element = $(element);
			var fieldId = element.attr('id');

			// Skip validation for our custom fields
			var skipField = false;
			if (hasCustomFieldValues) {
				// Check all possible field ID formats
				for (var i = 0; i < customFields.length; i++) {
					var field = customFields[i];
					var possibleIds = [
						field + '_field',
						field.replace(/ /g, '_') + '_field',
						field.replace('_No', ' No') + '_field'
					];

					if (possibleIds.indexOf(fieldId) !== -1) {
						console.log('Skipping validation for field: ' + fieldId);
						skipField = true;
						break;
					}
				}
			}

			if (skipField) {
				return true; // Skip this field
			}

			reset_element(element);

			if (element.hasClass("validate-required"))
			{
				// Check if it's an input field
				var inputField = element.find("input");
				var isEmpty = inputField.length > 0 ? inputField.val() == '' : true;

				if (isEmpty && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is required.");
					invalid = true;
				}
			}

			if (element.hasClass("validate-phone"))
			{
				if (element.find("input").val().length > 0 && !isValidPhone(element.find("input").val()) && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is not valid phone number !");
					invalid = true;
				}
			}

			if (element.hasClass("validate-email"))
			{
				if (element.find("input").val().length > 0 && !isValidEmail(element.find("input").val()) && invalid == false)
				{
					if (top == '')
					{
						top = element.offset().top;
						top -= element.height();
					}

					set_element(element, " is not valid email address !");
					invalid = true;
				}
			}
		});

		if (top != '')
		{
			$('html, body').animate({
				scrollTop: top
			}, 1000);

			return false;
		}

		return true;
	}

	function isValidPhone(phoneNumber)
	{
		var pattern = new RegExp( /[\s\#0-9_\-\+\/\(\)\.]/g );

		if ( 0 < phoneNumber.replace( pattern, '' ).length )
		{
			return false;
		}

		return true;
	}

	function isValidEmail(email)
	{
		var pattern = new RegExp( /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i );
		return pattern.test(email);
	}

    $(document).ready(function ($) {

		$('#billing_country-custom, #billing_state-custom').select2();

		$('#billing_country-custom, #billing_state-custom').select2('destroy');

		$(document).on('change keyup focusout', 'input:not([type="file"])', function(e) {
    		reset_element($(this).closest('p#'+$(this).attr('id')+'_field'));
		});

		$(document).on('change', 'input[type="file"]', function(e) {
			var selectedFile = $(this).val().split('\\').pop();
			var hiddenInput = $(this).siblings('input[type="hidden"]');
			hiddenInput.val(selectedFile);
			reset_element(hiddenInput.closest('p#'+hiddenInput.attr('id')+'_field'));
		});

        $(document).on('click', '.mpwpb_order_proceed_area #place_order', function(e) {

			if (!allowDefaultBehavior)
			{
				e.preventDefault();
				if (customValidation())
				{
					allowDefaultBehavior = true;
                    $('#place_order').click();
				}
			}
			else
			{
				allowDefaultBehavior = false;
			}

		});

    });

})(jQuery);
