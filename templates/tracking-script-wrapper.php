<figure class="op-tracker">
	<iframe>
		<?php
		if ( is_array( $script ) ) {

			foreach ( $script as $s ) {
				if ( is_callable( $s ) ) {
					call_user_func( $s );
				}
			}

		} else {
			echo $script;
		}
		?>
	</iframe>
</figure>
