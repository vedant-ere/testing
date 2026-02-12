<?php
/**
 * Person archive template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-home">
	<section class="movie-section">
		<div class="container">
			<h1 class="section-title">Celebrities</h1>
			<div class="movie-grid">
				<?php for ( $i = 0; $i < 6; $i++ ) : ?>
					<?php get_template_part( 'template-parts/person-card' ); ?>
				<?php endfor; ?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
