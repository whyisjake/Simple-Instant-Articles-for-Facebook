<figure class="op-tracker">
	<iframe>
		<?php
		if ( is_callable( $script ) ) {
			call_user_func( $script );
		} else {
			echo $script;
		}
		?>
	</iframe>
</figure>
