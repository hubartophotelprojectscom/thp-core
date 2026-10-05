<?php
/**
 * Minimal WPML language helper. Every method is safe without WPML: active()
 * returns '' ("feature off") and no WPML filter result is ever relied upon.
 */

namespace THP\Core\I18n;

defined( 'ABSPATH' ) || exit;

final class Lang {

	/** @var array<string,string>|null */
	private static $languages = null;

	/** True when WPML is providing languages. */
	public static function enabled(): bool {
		return false !== has_filter( 'wpml_current_language' );
	}

	/** The WPML default language code, or '' when WPML is off. */
	public static function default_code(): string {
		if ( ! self::enabled() ) {
			return '';
		}

		$code = apply_filters( 'wpml_default_language', null );

		return is_string( $code ) ? $code : '';
	}

	/**
	 * Active languages, code => native name. Empty without WPML.
	 *
	 * @return array<string,string>
	 */
	public static function list(): array {
		if ( null !== self::$languages ) {
			return self::$languages;
		}

		self::$languages = array();

		if ( ! self::enabled() ) {
			return self::$languages;
		}

		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );

		if ( is_array( $languages ) ) {
			foreach ( $languages as $code => $language ) {
				$name                          = is_array( $language ) ? (string) ( $language['native_name'] ?? '' ) : '';
				self::$languages[ (string) $code ] = '' !== $name ? $name : (string) $code;
			}
		}

		return self::$languages;
	}

	/** True for the default language and for "WPML off" (''). */
	public static function is_default( string $code ): bool {
		return '' === $code || $code === self::default_code();
	}

	/**
	 * The language to read/edit: in wp-admin a valid ?lang= wins (also read from
	 * the referer on a form POST, e.g. the CMB2 save), else WPML's current
	 * language. 'all', empty or unknown -> the default language. '' without WPML.
	 */
	public static function active(): string {
		if ( ! self::enabled() ) {
			return '';
		}

		$default = self::default_code();
		$code    = '';

		if ( is_admin() ) {
			$requested = self::requested_admin_code();

			if ( '' !== $requested && isset( self::list()[ $requested ] ) ) {
				$code = $requested;
			}
		}

		if ( '' === $code ) {
			$current = apply_filters( 'wpml_current_language', null );
			$code    = is_string( $current ) ? $current : '';
		}

		return ( '' === $code || 'all' === $code ) ? $default : $code;
	}

	private static function requested_admin_code(): string {
		$raw = isset( $_GET['lang'] ) ? $_GET['lang'] : null;

		// A settings form POSTs to admin-post.php without the query string; the
		// page URL (with its lang) is in the referer.
		if ( null === $raw && isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$referer = wp_get_referer();
			$query   = $referer ? (string) wp_parse_url( $referer, PHP_URL_QUERY ) : '';
			$args    = array();
			parse_str( $query, $args );
			$raw = $args['lang'] ?? null;
		}

		return is_string( $raw ) ? sanitize_key( wp_unslash( $raw ) ) : '';
	}
}
