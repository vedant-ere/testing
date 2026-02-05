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
	 */
	public function render_page(): void {
		?>
		<div class="wrap">
			<h1>RT Movie Library</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'rt_movie_library_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							Delete all plugin data on uninstall
						</th>
						<td>
							<label>
								<input type="checkbox"
									name="rt_movie_library_delete_data"
									value="1"
									<?php checked( 1, get_option( 'rt_movie_library_delete_data' ) ); ?>
								/>
								<strong>Enable destructive cleanup</strong>
							</label>

							<p class="description" style="color:#b32d2e;">
								⚠️ WARNING:  
								If enabled, deleting this plugin will permanently
								delete all Movies, Persons, and related metadata.
								This action cannot be undone.
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