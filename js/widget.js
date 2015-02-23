(function( $ ) {

	window.pl_recent_posts = function(context) {
		$.getJSON( plrcp.json_url, function( response ) {
			var $widgets = $( '.plrcp', context )

			$.each( $widgets, function( widget ) {
				var $widget = $(this)
				var identifier = $widget.data('id')

				if ( response[ identifier ] == undefined )
					return

				$widget.parent().css( 'display', 'block' )
				$widget.replaceWith(response[ identifier ])
			})
		})
	}

	pl_recent_posts($('body'))
})( jQuery )
