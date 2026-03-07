<?php
/**
 * Plugin Settings Page.
 *
 * Adds an options page under Settings to control
 * destructive plugin behavior on uninstall.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes;

use RT_Movie_Library\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings.
 *
 * Handles the plugin's settings page and options.
 */
class Settings {

	use Singleton;

	/**
	 * Constructor.
	 *
	 * Registers hooks for settings page and options.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registers settings menu.
	 */
	public function register_menu(): void {
		add_options_page(
			'RT Movie Library',
			'RT Movie Library',
			'manage_options',
			'rt-movie-library',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Registers plugin settings.
	 */
	public function register_settings(): void {
		register_setting(
			'rt_movie_library_settings',
			'rt_movie_library_delete_data'
		);
	}

	/**
	 * Renders settings page.
	 * Verify user has admin capabilities.
	 */
	public function render_page(): void {
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'You do not have sufficient permissions to access this page.',
					'rt-movie-library'
				),
				esc_html__( 'Permission Denied', 'rt-movie-library' ),
				array( 'response' => 403 )
			);
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RT Movie Library Settings', 'rt-movie-library' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'rt_movie_library_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Uninstall Behavior', 'rt-movie-library' ); ?>
						</th>
						<td>
							<label>
								<input type="checkbox"
									name="rt_movie_library_delete_data"
									value="1"
									<?php checked( 1, get_option( 'rt_movie_library_delete_data' ) ); ?> />
								<strong><?php esc_html_e( 'Delete all plugin data on uninstall', 'rt-movie-library' ); ?></strong>
							</label>

							<p class="description" style="color:#b32d2e;">
								<span class="dashicons dashicons-warning" style="vertical-align:middle;"></span>
								<?php
								esc_html_e(
									'WARNING: If enabled, deleting this plugin will permanently remove all Movies, Persons, taxonomies, and metadata. This action cannot be undone.',
									'rt-movie-library'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
