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
use RT_Movie_Library\Classes\Database\Meta_Repository;
use RT_Movie_Library\Classes\Database\Movie_Meta_Table;
use RT_Movie_Library\Classes\Database\Person_Meta_Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings.
 *
 * Handles the plugin's settings page and options.
 */
class Settings {

	use Singleton;

	/**
	 * Option name for TMDB API Read Access Token (Bearer token).
	 *
	 * @var string
	 */
	public const OPTION_API_KEY = 'rt_tmdb_api_key';

	/**
	 * Option name for movies-per-cron-run limit.
	 *
	 * @var string
	 */
	public const OPTION_MOVIE_LIMIT = 'rt_cron_movie_limit';

	/**
	 * Constructor.
	 *
	 * Registers hooks for settings page and options.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// Custom table management actions (not Options API — these are
		// destructive operations that warrant their own admin-post handlers).
		add_action( 'admin_post_rt_clear_custom_tables', array( $this, 'handle_clear_tables' ) );
		add_action( 'admin_post_rt_backup_custom_tables', array( $this, 'handle_backup_tables' ) );
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

		register_setting(
			'rt_movie_library_settings',
			self::OPTION_API_KEY,
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'rt_movie_library_settings',
			self::OPTION_MOVIE_LIMIT,
			array(
				'sanitize_callback' => 'absint',
				'default'           => 5,
			)
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
 
		$stats = Meta_Repository::get_stats();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RT Movie Library Settings', 'rt-movie-library' ); ?></h1>
 
			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Storage Performance (Movies):', 'rt-movie-library' ); ?></strong>
					<?php
					printf(
						/* translators: 1: migrated count, 2: total count */
						esc_html__( '%1$d of %2$d migrated.', 'rt-movie-library' ),
						absint( $stats['migrated_movies'] ),
						absint( $stats['total_movies'] )
					);
					?>
					|
					<strong><?php esc_html_e( 'Storage Performance (Persons):', 'rt-movie-library' ); ?></strong>
					<?php
					printf(
						/* translators: 1: migrated count, 2: total count */
						esc_html__( '%1$d of %2$d migrated.', 'rt-movie-library' ),
						absint( $stats['migrated_persons'] ),
						absint( $stats['total_persons'] )
					);
					?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields( 'rt_movie_library_settings' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'TMDB API Read Access Token', 'rt-movie-library' ); ?>
						</th>
						<td>
							<input
								type="password"
								name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
								value="<?php echo esc_attr( (string) get_option( self::OPTION_API_KEY, '' ) ); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Your TMDB API Read Access Token (Bearer). Found under TMDB Account > Settings > API.', 'rt-movie-library' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<?php esc_html_e( 'Movies Per Sync', 'rt-movie-library' ); ?>
						</th>
						<td>
							<input
								type="number"
								name="<?php echo esc_attr( self::OPTION_MOVIE_LIMIT ); ?>"
								value="<?php echo absint( get_option( self::OPTION_MOVIE_LIMIT, 5 ) ); ?>"
								min="1"
								max="5"
								class="small-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Number of movies to sync per cron run. Keep low to avoid TMDB rate limits.', 'rt-movie-library' ); ?>
							</p>
						</td>
					</tr>

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

			<hr>
			<h2><?php esc_html_e( 'Custom Table Management', 'rt-movie-library' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rt_clear_custom_tables">
				<?php wp_nonce_field( 'rt_clear_custom_tables_action' ); ?>
 
				<p class="description" style="color:#b32d2e; font-weight:bold;">
					<?php esc_html_e( 'CRITICAL: Since this plugin uses exclusive storage, clearing these tables will PERMANENTLY DELETE any metadata that has already been migrated. Only use this if you intend to re-sync or re-import all data. wp_postmeta backup logic NO LONGER APPLIES after migration.', 'rt-movie-library' ); ?>
				</p>
 
				<?php submit_button( __( 'Clear Custom Table Data', 'rt-movie-library' ), 'delete', 'rt_btn_clear', false ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="rt_backup_custom_tables">
				<?php wp_nonce_field( 'rt_backup_custom_tables_action' ); ?>
				<p class="description"><?php esc_html_e( 'Download a SQL INSERT backup of the custom meta tables.', 'rt-movie-library' ); ?></p>
				<?php submit_button( __( 'Backup Database (SQL)', 'rt-movie-library' ), 'secondary', 'rt_btn_backup', false, array( 'onclick' => 'return confirm("' . esc_js( __( 'This will download all custom table data. Continue?', 'rt-movie-library' ) ) . '");' ) ); ?>
			</form>

			</form>

		</div>
		<?php
	}

	/**
	 * Handles the "Clear Custom Table Data" admin-post action.
	 *
	 * Truncates both custom meta tables. Nonce-protected and capability-gated.
	 * Uses wp_safe_redirect() + exit to prevent response continuation after redirect.
	 *
	 * @return void
	 */
	public function handle_clear_tables(): void {
		check_admin_referer( 'rt_clear_custom_tables_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'rt-movie-library' ),
				esc_html__( 'Permission Denied', 'rt-movie-library' ),
				array( 'response' => 403 )
			);
		}

		Movie_Meta_Table::clear_table();
		Person_Meta_Table::clear_table();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'rt-movie-library',
					'rt_notice' => 'tables_cleared',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Handles the "Backup Database" admin-post action.
	 *
	 * Streams a .sql file of INSERT statements directly to the browser.
	 * No temp files are written to disk.
	 * Nonce-protected and capability-gated.
	 *
	 * @return void
	 */
	public function handle_backup_tables(): void {
		check_admin_referer( 'rt_backup_custom_tables_action' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to perform this action.', 'rt-movie-library' ),
				esc_html__( 'Permission Denied', 'rt-movie-library' ),
				array( 'response' => 403 )
			);
		}

		$site_slug = sanitize_key( get_bloginfo( 'name' ) );
		$filename  = ( $site_slug ? $site_slug . '.' : '' ) . 'rt-movie-library.' . gmdate( 'Y-m-d' ) . '.sql';

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary SQL output, not HTML.
		echo $this->generate_sql_dump();
		exit;
	}

	/**
	 * Generates SQL INSERT statements for both custom tables.
	 *
	 * Reads all rows from the tables and builds syntactically valid INSERT
	 * statements. Values are escaped via esc_sql() to prevent injection
	 * if the SQL file is later imported into a different environment.
	 *
	 * @return string Raw SQL.
	 */
	private function generate_sql_dump(): string {
		global $wpdb;

		$tables = array(
			Movie_Meta_Table::get_table_name(),
			Person_Meta_Table::get_table_name(),
		);

		$output  = '-- RT Movie Library custom table backup ' . gmdate( 'Y-m-d H:i:s' ) . " (UTC)\n";
		$output .= '-- Site: ' . esc_url( home_url() ) . "\n\n";

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is internal; backup logic.
			$rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );

			if ( empty( $rows ) ) {
				continue;
			}

			$output .= "-- Table: {$table}\n";

			foreach ( $rows as $row ) {
				$values  = array_map(
					static function ( $v ) {
						if ( null === $v ) {
							return 'NULL';
						}
						return "'" . esc_sql( (string) $v ) . "'";
					},
					$row
				);
				$cols    = '`' . implode( '`, `', array_keys( $row ) ) . '`';
				$vals    = implode( ', ', $values );
				$output .= "INSERT IGNORE INTO `{$table}` ({$cols}) VALUES ({$vals});\n";
			}

			$output .= "\n";
		}

		return $output;
	}
}
