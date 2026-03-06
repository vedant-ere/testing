<?php
/**
 * CSV helper for movie CLI import/export.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Csv_Helper
 */
class Csv_Helper {

	/**
	 * CSV header.
	 *
	 * @var array<string>
	 */
	public const HEADERS = array(
		'export_id',
		'export_hash',
		'post_title',
		'post_content',
		'post_excerpt',
		'post_status',
		'post_date',
		'post_author',
		'genres',
		'labels',
		'languages',
		'production_companies',
		'tags',
		'director_names',
		'producer_names',
		'writer_names',
		'actor_names',
		'actor_characters',
		'basic_rating',
		'basic_runtime',
		'basic_release_date',
		'basic_content_rating',
		'featured_image_url',
		'gallery_image_urls',
		'gallery_video_urls',
		'carousel_url',
		'comments_json',
	);
}
