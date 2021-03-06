jQuery(function($) {
   	$('#DayField').datepicker();

    $('.ghetto-checkbox').click(function() {
      var checked = [];
      $('.ghetto-checkbox').each(function() {
        var $this = $(this);

        if ($this.is(':checked')) {
          checked.push($this.val());
        }

      });

      if (checked.length > 1) {
        $('#DateSelect').val('all');
      } else {
        $('#DateSelect').val(checked[0]);
      }

    });
});

//window scroll function for navbar opacity

jQuery(function($) {
	$(window).scroll(function() {
		if ($(this).scrollTop() > 25){
			$('.navbar-default').addClass('opaque');
		} else {
			$('.navbar-default').removeClass('opaque');
		}
	});
});

jQuery(function($) {
  $('[data-toggle="tooltip"]').tooltip();

  var theSlider = $('#slider-range');
  var min = theSlider.data('min') || 0;
  var max = theSlider.data('max') || 500;

	theSlider.slider({
		range: true,
		min: 0,
		max: 500,
		values: [ min, max ],
		slide: function( event, ui ) {
			$( "#amount_left" ).val( ui.values[ 0 ]);
			$( "#amount_right" ).val( ui.values[ 1 ] );
		}
	});
	$( "#amount_left" ).val( $( "#slider-range" ).slider( "values", 0 ));
	$( "#amount_right" ).val( $( "#slider-range" ).slider( "values", 1 ) );
});
