<?php
/**
 * Front-end asset loader (Vite dist).
 *
 * @package SuperNinetyFour
 */

namespace SNF\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	/**
	 * Register theme styles and scripts from dist/.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/**
	 * Enqueue built Vite assets and page-specific dependencies / localization.
	 */
	public static function enqueue(): void {
		$theme_uri = get_template_directory_uri();
		$theme_dir = get_template_directory();
		$version   = defined( 'SNF_VERSION' ) ? SNF_VERSION : null;

		$css_path  = $theme_dir . '/dist/main.css';
		$js_path   = $theme_dir . '/dist/main.js';
		$fonts_css = $theme_dir . '/assets/fonts/snf-fonts.css';

		if ( file_exists( $fonts_css ) ) {
			wp_enqueue_style(
				'snf-fonts',
				$theme_uri . '/assets/fonts/snf-fonts.css',
				array(),
				(string) filemtime( $fonts_css )
			);
		}

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'snf-main',
				$theme_uri . '/dist/main.css',
				array( 'snf-fonts' ),
				(string) filemtime( $css_path )
			);
		} else {
			// Fallback while dist/ is missing (local without npm build).
			wp_enqueue_style(
				'snf-main',
				$theme_uri . '/assets/css/main.css',
				array( 'snf-fonts' ),
				$version
			);
		}

		$script_deps      = array();
		$uses_marketplace = function_exists( 'snf_needs_marketplace_assets' )
			? snf_needs_marketplace_assets()
			: (
				( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() )
				|| ( function_exists( 'is_shop' ) && is_shop() )
				|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			);

		if ( $uses_marketplace ) {
			$script_deps[] = 'jquery';
		}

		if ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
			wp_enqueue_style(
				'mapbox-gl',
				'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.css',
				array(),
				'3.9.0'
			);
			wp_enqueue_script(
				'mapbox-gl',
				'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.js',
				array(),
				'3.9.0',
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			$script_deps[] = 'mapbox-gl';
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'snf-main',
				$theme_uri . '/dist/main.js',
				$script_deps,
				(string) filemtime( $js_path ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		} else {
			self::enqueue_legacy_fallback( $theme_uri, $version, $uses_marketplace );
			return;
		}

		self::localize( $uses_marketplace );
	}

	/**
	 * Attach wp_localize_script payloads used by Vue islands.
	 *
	 * @param bool $uses_marketplace Whether marketplace bootstrap is needed.
	 */
	private static function localize( bool $uses_marketplace ): void {
		if ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
			$expo_audience = ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
				? 'members'
				: 'non-members';
			if ( function_exists( 'snf_get_expo_js_bootstrap' ) ) {
				wp_localize_script( 'snf-main', 'snfExpo', snf_get_expo_js_bootstrap( $expo_audience ) );
			}
		}

		if ( $uses_marketplace && function_exists( 'snf_get_marketplace_js_bootstrap' ) ) {
			wp_localize_script( 'snf-main', 'snfMarketplace', snf_get_marketplace_js_bootstrap() );
		}

		$hub_templates = array(
			'page-templates/billing-details.php',
			'page-templates/member-login.php',
			'page-templates/apply.php',
			'page-templates/messages.php',
			'page-templates/community.php',
			'page-templates/public-content.php',
			'page-templates/under-consideration.php',
		);
		$hub_slugs     = array(
			'billing-details',
			'member-login',
			'apply',
			'messages',
			'community',
			'inquiries',
			'under-consideration',
		);
		$is_hub        = false;
		foreach ( $hub_templates as $hub_template ) {
			if ( snf_is_page_template( $hub_template ) ) {
				$is_hub = true;
				break;
			}
		}
		if ( ! $is_hub ) {
			foreach ( $hub_slugs as $hub_slug ) {
				if ( is_page( $hub_slug ) ) {
					$is_hub = true;
					break;
				}
			}
		}
		if ( $is_hub && function_exists( 'snf_get_page_url' ) ) {
			wp_localize_script(
				'snf-main',
				'snfHub',
				array(
					'considerationUrl' => snf_get_page_url( 'under-consideration' ),
				)
			);
		}

		$is_messages = snf_is_page_template( 'page-templates/messages.php' ) || is_page( 'messages' );
		if ( $is_messages && function_exists( 'snf_messages_get_bootstrap' ) ) {
			wp_localize_script( 'snf-main', 'snfMessages', snf_messages_get_bootstrap() );
		}

		$is_community = snf_is_page_template( 'page-templates/community.php' ) || is_page( 'community' );
		if ( $is_community && function_exists( 'snf_community_get_bootstrap' ) ) {
			wp_localize_script( 'snf-main', 'snfCommunity', snf_community_get_bootstrap() );
		}

		$uses_media       = function_exists( 'snf_is_media_library_request' ) && snf_is_media_library_request();
		$is_event_landing = ( function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) )
			|| ( is_singular( 'event' ) && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) );
		$uses_live        = $is_event_landing
			|| ( function_exists( 'is_product' ) && is_product() )
			|| is_singular( 'event' );

		if ( ( $uses_media || $uses_live ) && function_exists( 'snf_get_media_js_bootstrap' ) ) {
			wp_localize_script( 'snf-main', 'snfMedia', snf_get_media_js_bootstrap() );
		}
	}

	/**
	 * Legacy enqueue path when dist/ has not been built yet.
	 *
	 * @param string      $theme_uri Theme URI.
	 * @param string|null $version   Theme version.
	 * @param bool        $uses_marketplace Marketplace flag.
	 */
	private static function enqueue_legacy_fallback( string $theme_uri, ?string $version, bool $uses_marketplace ): void {
		wp_enqueue_script(
			'snf-header',
			$theme_uri . '/assets/js/header.js',
			array(),
			$version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$uses_dashboard_script = snf_is_page_template( 'page-templates/woocommerce-account.php' )
			|| snf_is_page_template( 'page-templates/select-events.php' )
			|| snf_is_page_template( 'page-templates/expo-marketplace.php' )
			|| is_page( 'select-events-by-members' )
			|| is_page( 'select-events-by-non-members' )
			|| is_page( 'expo' )
			|| ( function_exists( 'is_account_page' ) && is_account_page() )
			|| ( function_exists( 'snf_is_bp_member_profile' ) && snf_is_bp_member_profile() );

		if ( $uses_dashboard_script ) {
			wp_enqueue_script(
				'snf-dashboard',
				$theme_uri . '/assets/js/dashboard.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		if ( function_exists( 'snf_is_expo_marketplace_page' ) && snf_is_expo_marketplace_page() ) {
			$expo_audience = ( function_exists( 'snf_user_can_access_member_areas' ) && snf_user_can_access_member_areas() )
				? 'members'
				: 'non-members';
			wp_enqueue_script(
				'snf-expo-marketplace',
				$theme_uri . '/assets/js/expo-marketplace.js',
				array( 'mapbox-gl' ),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			if ( function_exists( 'snf_get_expo_js_bootstrap' ) ) {
				wp_localize_script(
					'snf-expo-marketplace',
					'snfExpo',
					snf_get_expo_js_bootstrap( $expo_audience )
				);
			}
		}

		if ( $uses_marketplace && function_exists( 'snf_get_marketplace_js_bootstrap' ) ) {
			wp_enqueue_script(
				'snf-marketplace',
				$theme_uri . '/assets/js/marketplace.js',
				array( 'jquery' ),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			wp_localize_script(
				'snf-marketplace',
				'snfMarketplace',
				snf_get_marketplace_js_bootstrap()
			);
		}

		$hub_templates = array(
			'page-templates/billing-details.php',
			'page-templates/member-login.php',
			'page-templates/apply.php',
			'page-templates/messages.php',
			'page-templates/public-content.php',
			'page-templates/under-consideration.php',
		);
		$is_hub        = false;
		foreach ( $hub_templates as $hub_template ) {
			if ( snf_is_page_template( $hub_template ) ) {
				$is_hub = true;
				break;
			}
		}
		if ( $is_hub ) {
			wp_enqueue_script(
				'snf-hub',
				$theme_uri . '/assets/js/hub.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			if ( function_exists( 'snf_get_page_url' ) ) {
				wp_localize_script(
					'snf-hub',
					'snfHub',
					array(
						'considerationUrl' => snf_get_page_url( 'under-consideration' ),
					)
				);
			}
		}

		if ( snf_is_page_template( 'page-templates/messages.php' ) || is_page( 'messages' ) ) {
			wp_enqueue_script(
				'snf-messages',
				$theme_uri . '/assets/js/messages.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			if ( function_exists( 'snf_messages_get_bootstrap' ) ) {
				wp_localize_script( 'snf-messages', 'snfMessages', snf_messages_get_bootstrap() );
			}
		}

		$is_service_quote = function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_service_quote_product' ) && snf_is_service_quote_product( get_the_ID() );
		if ( $is_service_quote ) {
			wp_enqueue_script(
				'snf-service-quote',
				$theme_uri . '/assets/js/service-quote.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		$is_workshop_product = function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_lifestyle_concierge_workshop' ) && snf_is_lifestyle_concierge_workshop( get_the_ID() );
		if ( $is_workshop_product ) {
			wp_enqueue_script(
				'snf-workshop',
				$theme_uri . '/assets/js/workshop.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		$is_event_landing = ( function_exists( 'is_product' ) && is_product() && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) )
			|| ( is_singular( 'event' ) && function_exists( 'snf_is_figma_event_landing' ) && snf_is_figma_event_landing( get_the_ID() ) );
		if ( $is_event_landing ) {
			wp_enqueue_script(
				'snf-event-single',
				$theme_uri . '/assets/js/event-single.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		$uses_media = function_exists( 'snf_is_media_library_request' ) && snf_is_media_library_request();
		$uses_live  = $is_event_landing
			|| ( function_exists( 'is_product' ) && is_product() )
			|| is_singular( 'event' );
		if ( $uses_media || $uses_live ) {
			wp_enqueue_script(
				'snf-media-library',
				$theme_uri . '/assets/js/media-library.js',
				array(),
				$version,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			if ( function_exists( 'snf_get_media_js_bootstrap' ) ) {
				wp_localize_script( 'snf-media-library', 'snfMedia', snf_get_media_js_bootstrap() );
			}
		}
	}
}
