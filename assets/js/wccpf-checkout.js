jQuery(function($) {
function toggleFieldGroup($group, show) {
		$group.toggleClass('wccpf-hidden', !show);
		$group.prop('hidden', !show);
		$group.attr('aria-hidden', show ? 'false' : 'true');
		$group.find('input, select, textarea').prop('disabled', !show);
	}

	function updateWccpfFields() {
		var $container = $('#wccpf-dynamic-fields');
		if (!$container.length) {
			return;
		}

		var enabled = $('#wccpf_enable_fee').is(':checked');
		var $personType = $('.wccpf-person-type');
		var $individual = $('#wccpf_national_code_field');
		var $legal = $('#wccpf_legal_name_field, #wccpf_legal_id_field, #wccpf_legal_phone_field');

		$container.attr('data-selected', enabled ? 'yes' : 'no');

		if (!enabled) {
			$container.addClass('wccpf-hidden');
			$personType.find('input[type="radio"]').prop('checked', false).prop('disabled', true);
			toggleFieldGroup($individual, false);
			toggleFieldGroup($legal, false);
			$container.attr('data-person-type', '');
			return;
		}

		$container.removeClass('wccpf-hidden');
		$personType.find('input[type="radio"]').prop('disabled', false);

		var type = $('input[name="wccpf_person_type"]:checked').val();
		$container.attr('data-person-type', type || '');
		if (type === 'individual') {
			toggleFieldGroup($individual, true);
			toggleFieldGroup($legal, false);
		} else if (type === 'legal') {
			toggleFieldGroup($individual, false);
			toggleFieldGroup($legal, true);
		} else {
			toggleFieldGroup($individual, false);
			toggleFieldGroup($legal, false);
		}
	}

	$(document.body).on('change', '#wccpf_enable_fee, input[name="wccpf_person_type"]', function() {
		updateWccpfFields();
		$(document.body).trigger('update_checkout');
	});

	$(document.body).on('updated_checkout', function() {
		updateWccpfFields();
	});

	updateWccpfFields();
});
