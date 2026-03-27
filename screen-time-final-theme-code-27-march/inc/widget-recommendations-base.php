<?php
/**
 * Abstract base class for recommendation widgets.
 *
 * Shares the admin-facing form() and update() logic that is identical
 * across all recommendation widget subclasses. Each subclass implements
 * its own widget() method for post-type-specific front-end rendering.
 *
 * @package ScreenTime
 */

/**
 * Abstract class Screentime_Recommendations_Widget_Base
 */
abstract class Screentime_Recommendations_Widget_Base extends WP_Widget {

	// ── Abstract configuration methods ────────────────────────────────────

	/**
	 * Returns the default taxonomy slug used when no value is saved.
	 *
	 * @return string
	 */
	abstract protected function get_default_taxonomy(): string;

	/**
	 * Returns an ordered map of taxonomy slug => human-readable label for
	 * the admin form select.
	 *
	 * @return array<string,string>
	 */
	abstract protected function get_taxonomy_labels(): array;

	// ── Shared admin methods ──────────────────────────────────────────────

	/**
	 * Renders the admin widget settings form.
	 *
	 * @param array $instance Current saved widget settings.
	 * @return string Empty string (output echoed directly per WP_Widget contract).
	 */
	public function form( $instance ) {
		$title           = sanitize_text_field( $instance['title'] ?? '' );
		$count           = max( 1, min( 12, absint( $instance['count'] ?? 3 ) ) );
		$taxonomy        = sanitize_key( $instance['taxonomy'] ?? $this->get_default_taxonomy() );
		$taxonomy_labels = $this->get_taxonomy_labels();
		$is_single       = 1 === count( $taxonomy_labels );

		if ( ! array_key_exists( $taxonomy, $taxonomy_labels ) ) {
			$taxonomy = $this->get_default_taxonomy();
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'screen-time' ); ?>
			</label>
			<input
				type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $title ); ?>"
				class="widefat screentime-widget-title"
				maxlength="100"
			>
			<span class="screentime-widget-error" aria-live="polite"></span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>">
				<?php esc_html_e( 'Count:', 'screen-time' ); ?>
			</label>
			<input
				type="number"
				id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
				value="<?php echo esc_attr( (string) $count ); ?>"
				min="1"
				max="12"
				step="1"
				class="tiny-text screentime-widget-count"
			>
			<span class="screentime-widget-error" aria-live="polite"></span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'taxonomy' ) ); ?>">
				<?php esc_html_e( 'Relation Criteria:', 'screen-time' ); ?>
			</label>
			<select
				id="<?php echo esc_attr( $this->get_field_id( 'taxonomy' ) ); ?>"
				<?php if ( ! $is_single ) : ?>
					name="<?php echo esc_attr( $this->get_field_name( 'taxonomy' ) ); ?>"
				<?php endif; ?>
				class="widefat"
				<?php disabled( $is_single, true ); ?>
			>
				<?php foreach ( $taxonomy_labels as $slug => $label ) : ?>
					<option
						value="<?php echo esc_attr( $slug ); ?>"
						<?php selected( $taxonomy, $slug ); ?>
					>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( $is_single ) : ?>
				<input
					type="hidden"
					name="<?php echo esc_attr( $this->get_field_name( 'taxonomy' ) ); ?>"
					value="<?php echo esc_attr( $this->get_default_taxonomy() ); ?>"
				>
				<span class="description">
					<?php esc_html_e( 'Only one taxonomy is available.', 'screen-time' ); ?>
				</span>
			<?php endif; ?>
		</p>
		<?php

		return '';
	}

	/**
	 * Sanitizes and saves the widget settings submitted from the admin form.
	 *
	 * @param array $new_instance New values submitted by the user.
	 * @param array $old_instance Previously saved values (unused).
	 * @return array Sanitized values to persist.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = sanitize_text_field( $new_instance['title'] ?? '' );
		$instance['count'] = max( 1, min( 12, absint( $new_instance['count'] ?? 3 ) ) );

		$submitted = sanitize_key( $new_instance['taxonomy'] ?? '' );

		if ( in_array( $submitted, $this->get_allowed_taxonomies(), true ) ) {
			$instance['taxonomy'] = $submitted;
		} else {
			$instance['taxonomy'] = $this->get_default_taxonomy();
		}

		return $instance;
	}

	// ── Helpers ────────────────────────────────────────────────────────────

	/**
	 * Returns the whitelist of allowed taxonomy slugs derived from the
	 * taxonomy label map. Concrete subclasses do not need to override this.
	 *
	 * @return string[]
	 */
	protected function get_allowed_taxonomies(): array {
		return array_keys( $this->get_taxonomy_labels() );
	}
}
