<figure class="op-tracker">
	<iframe src="" hidden>

		<script type="text/javascript">
			var utag_data = <?php echo wp_json_encode( $data['omniture_data'] ); ?>;
		</script>

		<?php // @codingStandardsIgnoreLine ?>
		<script type="text/javascript" src="<?php echo $data['omniture_data']['url']; ?>"></script>

	</iframe>
</figure>
