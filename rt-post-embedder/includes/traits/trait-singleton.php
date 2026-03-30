<?php
/**
 * Singleton trait.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait Singleton
 */
trait Singleton {

	/**
	 * Instance holder.
	 *
	 * @var static|null
	 */
	protected static $instance = null;

	/**
	 * Protected constructor.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Unserialization is disallowed.
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Unserializing singleton instances is not allowed.' );
	}

	/**
	 * Get class singleton instance.
	 *
	 * @return static
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}
