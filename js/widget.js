(function( $ ) {
	window.pl_recent_posts = function(context) {
		$.ajax({
			url: plrcp.json_url, 
			async: true,
			cache: false,
			dataType: "json",
			success: function( response ) {
				var $widgets = $( '.plrcp', context )

				$.each( $widgets, function( widget ) {
					var $widget = $(this)
					var identifier = $widget.data('id')

					if ( response[ identifier ] == undefined )
						return

					$widget.parent().css( 'display', 'block' )
					$widget.replaceWith(response[ identifier ])
				})
			}
		})
	}

	pl_recent_posts($('body'))
})( jQuery )
