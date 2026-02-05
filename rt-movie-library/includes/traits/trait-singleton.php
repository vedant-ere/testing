<?php
/**
 * Singleton Trait.
 *
 * Ensures a class has only one instance.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Traits;

defined( 'ABSPATH' ) || exit;

trait Singleton {

	/**
	 * Holds the single instance of the class.
	 *
	 * @var static|null
	 */
	protected static $instance = null;

	/**
	 * Protected constructor to prevent direct instantiation.
	 */
	protected function __construct() {}

	/**
	 * Prevent object cloning.
	 */
	protected function __clone() {}

	/**
	 * Prevent unserializing of the instance.
	 */
	public function __wakeup() {}

	/**
	 * Returns the single instance of the class.
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
