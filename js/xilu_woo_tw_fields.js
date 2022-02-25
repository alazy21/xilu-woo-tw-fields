(function($) {
		
	$('<div>').attr({ id: 'billing_twzipcode' }).insertBefore('#billing_address_1');
	$('#billing_twzipcode').twzipcode({
		'zipcodeSel': $("#billing_postcode").val(),
		'zipcodeIntoDistrict': true,
		'onDistrictSelect': function() {
			$('#billing_twzipcode').twzipcode('get', function (county, district, zipcode) {
				$("#billing_state").val(county);
				$("#billing_city").val(district);
				$("#billing_postcode").val(zipcode);
			});
			$(document.body).trigger("update_checkout"); // 更新運費計算
		}
	});

	$('<div>').attr({ id: 'shipping_twzipcode' }).insertBefore('#shipping_address_1');
	$('#shipping_twzipcode').twzipcode({
		'zipcodeSel': $("#shipping_postcode").val(),
		'zipcodeIntoDistrict': true,
		'onDistrictSelect': function() {
			$('#shipping_twzipcode').twzipcode('get', function (county, district, zipcode) {
				$("#shipping_state").val(county);
				$("#shipping_city").val(district);
				$("#shipping_postcode").val(zipcode);
			});
			$(document.body).trigger("update_checkout"); // 更新運費計算
		}
	});


	$("select#address_book").on("change", function(){
		if( $(this).val() == 'add_new' ) {
			$('#shipping_twzipcode').twzipcode('reset');
		} else {
			$.ajax({
				url: xilu_ajax_script.ajax_url,
				type: 'post',
				data: {
					action: 'xilu_get_postcode',
					nonce: xilu_ajax_script.get_postcode,
					shipping: $(this).val()
				},
				success: function(data) {
					$('#shipping_twzipcode').twzipcode('set', data);
				}
			});
		}
		$(document.body).trigger("update_checkout"); // 更新運費計算
	});
	
})( jQuery );