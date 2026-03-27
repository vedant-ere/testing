<?php
/**
 * Singleton Trait.
 *
 * Ensures a class has only one instance. Mirrors the pattern used by the
 * RT_Movie_Library plugin so the project follows a consistent convention.
 *
 * @package ScreenTime
 */

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
	 *
	 * @throws \Exception When unserialization is attempted.
	 */
	public function __wakeup() {
		throw new \Exception( 'Unserializing singleton instances is not allowed.' );
	}

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
