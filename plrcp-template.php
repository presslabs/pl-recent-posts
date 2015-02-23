<?php
foreach ( $recent_posts as $recent ):
	$link = get_permalink( $recent['ID'] );
	?>
	
	<div id='story-<?php echo $recent['ID'] ?>' class='plrcp-post-box'>
		<div class="plrcp-thumbnail-box">
		<a href="<?php echo $link ?>">
				<?php echo plrcp_get_thumb( $recent['ID'], $default_thumb ) ?>
			</a>
		</div>
		<div class="plrcp-content-box">
			<h4><a href="<?php echo $link ?>"><?php echo $recent['post_title'] ?></a></h4>
		</div>
	</div>

<?php endforeach; ?>
