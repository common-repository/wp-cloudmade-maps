<article id="infoWindowed-post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'YOUR-THEME-TEXTDOMAIN' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
	</header>

	<div class="entry-content">
		<?php the_content( ); ?>
	</div><!-- .entry-content -->

	<footer class="entry-utility">

		<?php if ( comments_open() ) : ?>
		<p class="comments-link">
				<?php comments_popup_link( __( 'Leave a comment', 'YOUR-THEME-TEXTDOMAIN' ), __( '1 Comment', 'YOUR-THEME-TEXTDOMAIN' ), __( '% Comments', 'YOUR-THEME-TEXTDOMAIN' ), 'jump-to-comments', __( 'Comments are closed.', 'YOUR-THEME-TEXTDOMAIN' ) ); ?>
		</p>
		<?php endif; ?>

		<?php edit_post_link( __( 'Edit', 'YOUR-THEME-TEXTDOMAIN' ), '', '' ); ?>

	</footer><!-- .entry-utility -->
	
</article><!-- #infoWindowed-post-## -->