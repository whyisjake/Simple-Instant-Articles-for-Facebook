<figure class="op-ad">
	<iframe width="300" height="250">

		<script type="text/javascript">
		var googletag = googletag || {};
		googletag.cmd = googletag.cmd || [];
		(function() {
			var gads = document.createElement("script");
			gads.async = true;
			gads.type = "text/javascript";
			var useSSL = "https:" == document.location.protocol;
			gads.src = (useSSL ? "https:" : "http:") + "//www.googletagservices.com/tag/js/gpt.js";
			var node =document.getElementsByTagName("script")[0];
			node.parentNode.insertBefore(gads, node);
		})();
		</script>

		<div id='acm-ad-tag-div-gpt-ad-1374472344931-0'>
		  <script type='text/javascript'>
			googletag.cmd.push(function() {
			  googletag.defineSlot( '/7103/smg_blogtest/300x250_1a/sports/general', [[300,250],[300,600]], 'acm-ad-tag-div-gpt-ad-1374472344931-0' )
				.addService( googletag.pubads() )

				<?php

				$targeting_params_array = apply_filters( 'acm_targeting_params', array() );

				if ( ! empty( $targeting_params_array ) ) {
					foreach ( $targeting_params_array as $key => $value ) {

						$value = explode( ',', trim( $value, '[]') );
						$value = array_map( function( $val ) {
							return trim( $val, '"' );
						}, $value );

						printf( ".setTargeting( '%s', %s )\n", esc_js( $key ), wp_json_encode( $value ) );

					}
				}

				?>
			  googletag.enableServices();
			  googletag.display('acm-ad-tag-div-gpt-ad-1374472344931-0');
		  });
		  </script>
		</div>

	</iframe>
</figure>
