<?php
/**
 * SNF Club wp-admin dashboards for Events, Circles, Videos & Audio, and critical settings.
 *
 * @package Super_Ninety_Four
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SNF_EVENT_SETTINGS_OPTION  = 'snf_event_settings';
const SNF_CIRCLE_SETTINGS_OPTION = 'snf_circle_settings';

/**
 * Default event club settings.
 *
 * @return array<string, mixed>
 */
function snf_event_default_settings() {
	return array(
		'default_attendance_mode' => 'hybrid',
		'default_live_provider'   => 'youtube_live',
		'archive_show_past'       => 1,
		'require_ticket_product'  => 0,
	);
}

/**
 * Merged event settings.
 *
 * @return array<string, mixed>
 */
function snf_get_event_settings() {
	$stored = get_option( SNF_EVENT_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_event_default_settings(), $stored );
}

/**
 * Default circle settings.
 *
 * @return array<string, mixed>
 */
function snf_circle_default_settings() {
	return array(
		'default_visibility' => 'members',
		'allow_self_join'    => 1,
		'hub_intro'          => '',
	);
}

/**
 * Merged circle settings.
 *
 * @return array<string, mixed>
 */
function snf_get_circle_settings() {
	$stored = get_option( SNF_CIRCLE_SETTINGS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	return array_merge( snf_circle_default_settings(), $stored );
}

/**
 * Circle visibility choices.
 *
 * @return array<string, string>
 */
function snf_circle_visibility_choices() {
	return array(
		'members'    => __( 'Club members', 'super-ninety-four' ),
		'reciprocal' => __( 'Members + reciprocal', 'super-ninety-four' ),
		'staff'      => __( 'Staff only', 'super-ninety-four' ),
	);
}

/**
 * Admin pages registry.
 *
 * @return array<string, array<string, string>>
 */
function snf_admin_dashboard_pages() {
	return array(
		'snf-club'          => array(
			'title' => __( 'Overview', 'super-ninety-four' ),
			'label' => __( 'Overview', 'super-ninety-four' ),
		),
		'snf-club-events'   => array(
			'title' => __( 'Events', 'super-ninety-four' ),
			'label' => __( 'Events', 'super-ninety-four' ),
		),
		'snf-club-circles'  => array(
			'title' => __( 'Circles', 'super-ninety-four' ),
			'label' => __( 'Circles', 'super-ninety-four' ),
		),
		'snf-club-media'    => array(
			'title' => __( 'Videos & Audio', 'super-ninety-four' ),
			'label' => __( 'Videos & Audio', 'super-ninety-four' ),
		),
		'snf-club-settings' => array(
			'title' => __( 'Critical settings', 'super-ninety-four' ),
			'label' => __( 'Critical settings', 'super-ninety-four' ),
		),
	);
}

/**
 * Whether the current screen is an SNF Club dashboard.
 *
 * @return bool
 */
function snf_admin_is_club_screen() {
	if ( ! is_admin() ) {
		return false;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( snf_admin_dashboard_pages()[ $page ] );
}

/**
 * Enqueue admin dashboard assets.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function snf_admin_dashboard_enqueue( $hook_suffix ) {
	$hook = (string) $hook_suffix;
	$load = snf_admin_is_club_screen()
		|| false !== strpos( $hook, 'snf-club' )
		|| false !== strpos( $hook, 'snf-media-settings' );

	if ( ! $load ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$types  = array( 'event', 'snf_circle', 'snf_video', 'snf_audio', 'snf_reservation' );
		if ( ! $screen || ! in_array( $screen->post_type, $types, true ) ) {
			return;
		}
	}

	$css = get_template_directory() . '/assets/css/admin-dashboard.css';
	wp_enqueue_style(
		'snf-admin-dashboard',
		get_template_directory_uri() . '/assets/css/admin-dashboard.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : ( defined( 'SNF_VERSION' ) ? SNF_VERSION : '1.0' )
	);
}
add_action( 'admin_enqueue_scripts', 'snf_admin_dashboard_enqueue' );

/**
 * Register top-level SNF Club menu + CPT dashboard shortcuts.
 */
function snf_admin_dashboard_menu() {
	add_menu_page(
		__( 'SNF Club', 'super-ninety-four' ),
		__( 'SNF Club', 'super-ninety-four' ),
		'edit_posts',
		'snf-club',
		'snf_admin_render_overview',
		'dashicons-shield-alt',
		3
	);

	// Parent slug already creates the Overview entry; add the rest as children.
	$pages = snf_admin_dashboard_pages();
	foreach ( $pages as $slug => $page ) {
		if ( 'snf-club' === $slug ) {
			continue;
		}

		$callback = 'snf_admin_render_overview';
		if ( 'snf-club-events' === $slug ) {
			$callback = 'snf_admin_render_events';
		} elseif ( 'snf-club-circles' === $slug ) {
			$callback = 'snf_admin_render_circles';
		} elseif ( 'snf-club-media' === $slug ) {
			$callback = 'snf_admin_render_media';
		} elseif ( 'snf-club-settings' === $slug ) {
			$callback = 'snf_admin_render_critical_settings';
		}

		$cap = ( 'snf-club-settings' === $slug ) ? 'manage_options' : 'edit_posts';

		add_submenu_page(
			'snf-club',
			$page['title'],
			$page['label'],
			$cap,
			$slug,
			$callback
		);
	}

	// Rename the auto submenu that mirrors the top-level item.
	add_action(
		'admin_menu',
		static function () {
			global $submenu;
			if ( isset( $submenu['snf-club'][0][0] ) ) {
				$submenu['snf-club'][0][0] = __( 'Overview', 'super-ninety-four' );
			}
		},
		999
	);
}
add_action( 'admin_menu', 'snf_admin_dashboard_menu', 9 );

/**
 * Count posts by type/status.
 *
 * @param string $post_type Post type.
 * @param string $status    Status.
 * @return int
 */
function snf_admin_count_posts( $post_type, $status = 'publish' ) {
	$counts = wp_count_posts( $post_type );
	if ( ! $counts || ! isset( $counts->{$status} ) ) {
		return 0;
	}
	return (int) $counts->{$status};
}

/**
 * Taxonomy term count helper.
 *
 * @param string $taxonomy Taxonomy.
 * @return int
 */
function snf_admin_count_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return 0;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'count',
		)
	);
	return is_wp_error( $terms ) ? 0 : (int) $terms;
}

/**
 * Page health rows for critical surfaces.
 *
 * @return array<int, array<string, mixed>>
 */
function snf_admin_page_health_rows() {
	$slugs = array(
		'videos-audio'                 => __( 'Videos & Audio hub', 'super-ninety-four' ),
		'community'                    => __( 'Community', 'super-ninety-four' ),
		'messages'                     => __( 'Messages', 'super-ninety-four' ),
		'expo'                         => __( 'Expo marketplace', 'super-ninety-four' ),
		'select-events-by-members'     => __( 'Select Events (members)', 'super-ninety-four' ),
		'select-events-by-non-members' => __( 'Select Events (public)', 'super-ninety-four' ),
		'apply'                        => __( 'Apply', 'super-ninety-four' ),
		'member-login'                 => __( 'Member login', 'super-ninety-four' ),
	);

	$rows = array();
	foreach ( $slugs as $slug => $label ) {
		$page = get_page_by_path( $slug );
		$url  = $page ? get_permalink( $page ) : '';
		$rows[] = array(
			'label'  => $label,
			'slug'   => $slug,
			'ok'     => (bool) $page,
			'edit'   => $page ? get_edit_post_link( $page->ID, 'raw' ) : '',
			'view'   => $url,
		);
	}
	return $rows;
}

/**
 * Shared dashboard chrome.
 *
 * @param string $active Active page slug.
 * @param string $title  Page title.
 * @param string $lede   Supporting sentence.
 */
function snf_admin_render_shell_start( $active, $title, $lede ) {
	$pages = snf_admin_dashboard_pages();
	?>
	<div class="wrap snf-admin">
		<header class="snf-admin__hero">
			<p class="snf-admin__eyebrow"><?php esc_html_e( 'Super Ninety Four', 'super-ninety-four' ); ?></p>
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="snf-admin__lede"><?php echo esc_html( $lede ); ?></p>
		</header>
		<nav class="snf-admin__nav" aria-label="<?php esc_attr_e( 'SNF Club sections', 'super-ninety-four' ); ?>">
			<?php foreach ( $pages as $slug => $page ) : ?>
				<?php
				if ( 'snf-club-settings' === $slug && ! current_user_can( 'manage_options' ) ) {
					continue;
				}
				?>
				<a class="<?php echo $active === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
					<?php echo esc_html( $page['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="snf-admin__notice snf-admin__notice--success">
				<?php esc_html_e( 'Settings saved.', 'super-ninety-four' ); ?>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['flushed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="snf-admin__notice snf-admin__notice--success">
				<?php esc_html_e( 'Permalinks flushed.', 'super-ninety-four' ); ?>
			</div>
		<?php endif; ?>
		<?php if ( isset( $_GET['styles_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="snf-admin__notice snf-admin__notice--success">
				<?php esc_html_e( 'Global Styles reset to theme.json defaults.', 'super-ninety-four' ); ?>
			</div>
		<?php endif; ?>
	<?php
}

/**
 * Close dashboard shell.
 */
function snf_admin_render_shell_end() {
	echo '</div>';
}

/**
 * Overview dashboard.
 */
function snf_admin_render_overview() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club',
		__( 'Club operations', 'super-ninety-four' ),
		__( 'Dashboards for Events, Circles, Videos & Audio, plus the settings those surfaces depend on.', 'super-ninety-four' )
	);

	$stats = array(
		array( snf_admin_count_posts( 'event' ), __( 'Published events', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_circle' ), __( 'Circles', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_video' ), __( 'Videos', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_audio' ), __( 'Audio', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_reservation', 'publish' ) + snf_admin_count_posts( 'snf_reservation', 'pending' ), __( 'Reservations', 'super-ninety-four' ) ),
		array( snf_admin_count_posts( 'snf_rfq' ), __( 'RFQs', 'super-ninety-four' ) ),
	);
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<?php foreach ( $stats as $stat ) : ?>
			<div class="snf-admin__stat">
				<span class="snf-admin__stat-value"><?php echo esc_html( (string) $stat[0] ); ?></span>
				<span class="snf-admin__stat-label"><?php echo esc_html( $stat[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Content desks', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Jump into the CPT lists or their dedicated dashboards.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-events' ) ); ?>"><?php esc_html_e( 'Events dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>"><?php esc_html_e( 'All events', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-circles' ) ); ?>"><?php esc_html_e( 'Circles dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'All circles', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-media' ) ); ?>"><?php esc_html_e( 'Media dashboard', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'All videos', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Integrations pulse', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Payments, maps, and media defaults that unlock front-end features.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Stripe', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ( function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready() ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ( function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready() ) ? esc_html__( 'Ready', 'super-ninety-four' ) : esc_html__( 'Needs keys', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Mapbox', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ( function_exists( 'snf_get_mapbox_token' ) && snf_get_mapbox_token() ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ( function_exists( 'snf_get_mapbox_token' ) && snf_get_mapbox_token() ) ? esc_html__( 'Token set', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
					</span>
				</li>
				<li>
					<span><?php esc_html_e( 'Live audio rooms', 'super-ninety-four' ); ?></span>
					<?php $media = function_exists( 'snf_get_media_settings' ) ? snf_get_media_settings() : array(); ?>
					<span class="snf-admin__pill <?php echo ! empty( $media['enable_live_audio_rooms'] ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ! empty( $media['enable_live_audio_rooms'] ) ? esc_html__( 'Enabled', 'super-ninety-four' ) : esc_html__( 'Off', 'super-ninety-four' ); ?>
					</span>
				</li>
			</ul>
			<div class="snf-admin__actions">
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-club-settings' ) ); ?>"><?php esc_html_e( 'Critical settings', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
				<?php if ( current_user_can( 'manage_woocommerce' ) ) : ?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-stripe' ) ); ?>"><?php esc_html_e( 'SNF Stripe', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
		</section>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Events dashboard.
 */
function snf_admin_render_events() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-events',
		__( 'Events', 'super-ninety-four' ),
		__( 'Select Events, workshops, broadcast modes, ticket experiences, and reservation intake.', 'super-ninety-four' )
	);

	$settings = snf_get_event_settings();
	$modes    = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
	$providers = function_exists( 'snf_live_provider_choices' ) ? snf_live_provider_choices() : array();
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'event' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Published', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'event', 'draft' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Drafts', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_event_experience' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Experiences', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_broadcast_format' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Broadcast formats', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( 'snf_ticket_addon_kind' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Addon kinds', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_reservation' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Reservations', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Quick actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Create content or open taxonomies used by ticket commerce and live broadcast.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event' ) ); ?>"><?php esc_html_e( 'Add event', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>"><?php esc_html_e( 'All events', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_reservation' ) ); ?>"><?php esc_html_e( 'Reservations', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_event_experience&post_type=event' ) ); ?>"><?php esc_html_e( 'Experiences', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_broadcast_format&post_type=event' ) ); ?>"><?php esc_html_e( 'Formats', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=snf_ticket_addon_kind&post_type=event' ) ); ?>"><?php esc_html_e( 'Addon kinds', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Front-end surfaces', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Member and public Select Events pages plus the CPT archive.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<?php
				$links = array(
					array( __( 'Members Select Events', 'super-ninety-four' ), get_page_by_path( 'select-events-by-members' ) ),
					array( __( 'Public Select Events', 'super-ninety-four' ), get_page_by_path( 'select-events-by-non-members' ) ),
				);
				foreach ( $links as $row ) :
					$page = $row[1];
					?>
					<li>
						<span><?php echo esc_html( $row[0] ); ?></span>
						<?php if ( $page ) : ?>
							<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
						<?php else : ?>
							<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing page', 'super-ninety-four' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li>
					<span><?php esc_html_e( 'Events archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
			</ul>
		</section>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Event defaults', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Defaults applied when new event products / CPT items lean on club broadcast settings.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_event_save_settings">
				<?php wp_nonce_field( 'snf_event_settings', 'snf_event_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default attendance mode', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_attendance_mode">
								<?php foreach ( $modes as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_attendance_mode'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default live provider', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_live_provider">
								<?php foreach ( $providers as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_live_provider'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Archive', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="archive_show_past" value="1" <?php checked( ! empty( $settings['archive_show_past'] ) ); ?>>
								<?php esc_html_e( 'Include past events on the archive when dates are present', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ticketing', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="require_ticket_product" value="1" <?php checked( ! empty( $settings['require_ticket_product'] ) ); ?>>
								<?php esc_html_e( 'Prefer WooCommerce ticket products for paid Select Events', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save event settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Circles dashboard.
 */
function snf_admin_render_circles() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-circles',
		__( 'Circles', 'super-ninety-four' ),
		__( 'Member community rooms, visibility, and join settings for the Club hub.', 'super-ninety-four' )
	);

	$settings = snf_get_circle_settings();
	$circles  = get_posts(
		array(
			'post_type'      => 'snf_circle',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	global $wpdb;
	$members_table = function_exists( 'snf_community_members_table' ) ? snf_community_members_table() : '';
	$total_joins   = 0;
	if ( $members_table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_joins = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$members_table}" );
	}
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_circle' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Published circles', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) $total_joins ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Membership rows', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo get_option( 'snf_community_circles_seeded' ) ? 'OK' : '—'; ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Defaults seeded', 'super-ninety-four' ); ?></span>
		</div>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Circle roster', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Visibility meta and join counts for each circle.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Circle', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Visibility', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Members', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $circles ) : ?>
						<tr><td colspan="4" class="snf-admin__muted"><?php esc_html_e( 'No circles yet.', 'super-ninety-four' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $circles as $circle ) : ?>
						<?php
						$vis = (string) get_post_meta( $circle->ID, '_snf_circle_visibility', true );
						if ( '' === $vis ) {
							$vis = 'members';
						}
						$count = 0;
						if ( $members_table ) {
							$count = (int) $wpdb->get_var(
								$wpdb->prepare(
									"SELECT COUNT(*) FROM {$members_table} WHERE circle_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									(int) $circle->ID
								)
							);
						}
						$choices = snf_circle_visibility_choices();
						?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $circle->ID ) ); ?>"><?php echo esc_html( get_the_title( $circle ) ); ?></a></td>
							<td><span class="snf-admin__pill"><?php echo esc_html( $choices[ $vis ] ?? $vis ); ?></span></td>
							<td><?php echo esc_html( (string) $count ); ?></td>
							<td><?php echo esc_html( get_post_status( $circle ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'Add circle', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_circle' ) ); ?>"><?php esc_html_e( 'All circles', 'super-ninety-four' ); ?></a>
				<?php
				$community = get_page_by_path( 'community' );
				if ( $community ) :
					?>
					<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( get_permalink( $community ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Community hub', 'super-ninety-four' ); ?></a>
				<?php endif; ?>
			</div>
		</section>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Circle settings', 'super-ninety-four' ); ?></h2>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_circle_save_settings">
				<?php wp_nonce_field( 'snf_circle_settings', 'snf_circle_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default visibility', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_visibility">
								<?php foreach ( snf_circle_visibility_choices() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['default_visibility'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Self-join', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="allow_self_join" value="1" <?php checked( ! empty( $settings['allow_self_join'] ) ); ?>>
								<?php esc_html_e( 'Allow eligible members to join circles from the Community hub', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hub intro', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="hub_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $settings['hub_intro'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Optional copy for the Community hub (theme can read snf_get_circle_settings).', 'super-ninety-four' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save circle settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Videos & Audio dashboard (includes media settings form).
 */
function snf_admin_render_media() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-media',
		__( 'Videos & Audio', 'super-ninety-four' ),
		__( 'YouTube library, audio channels, topics/series taxonomies, and live-room defaults.', 'super-ninety-four' )
	);

	$tax_rows = array(
		array( 'snf_media_topic', __( 'Topics', 'super-ninety-four' ), 'snf_video' ),
		array( 'snf_media_series', __( 'Video series', 'super-ninety-four' ), 'snf_video' ),
		array( 'snf_audio_channel', __( 'Audio channels', 'super-ninety-four' ), 'snf_audio' ),
		array( 'snf_broadcast_format', __( 'Broadcast formats', 'super-ninety-four' ), 'snf_video' ),
	);
	?>
	<div class="snf-admin__grid snf-admin__grid--stats">
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_video' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Videos', 'super-ninety-four' ); ?></span>
		</div>
		<div class="snf-admin__stat">
			<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_posts( 'snf_audio' ) ); ?></span>
			<span class="snf-admin__stat-label"><?php esc_html_e( 'Audio', 'super-ninety-four' ); ?></span>
		</div>
		<?php foreach ( $tax_rows as $row ) : ?>
			<div class="snf-admin__stat">
				<span class="snf-admin__stat-value"><?php echo esc_html( (string) snf_admin_count_terms( $row[0] ) ); ?></span>
				<span class="snf-admin__stat-label"><?php echo esc_html( $row[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Library actions', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Manage CPTs and taxonomies that power /videos-audio/ and library archives.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'Add video', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=snf_audio' ) ); ?>"><?php esc_html_e( 'Add audio', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_video' ) ); ?>"><?php esc_html_e( 'All videos', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'edit.php?post_type=snf_audio' ) ); ?>"><?php esc_html_e( 'All audio', 'super-ninety-four' ); ?></a>
			</div>
			<table class="snf-admin__table" style="margin-top:1rem">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Taxonomy', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Terms', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Manage', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tax_rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row[1] ); ?></td>
							<td><?php echo esc_html( (string) snf_admin_count_terms( $row[0] ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $row[0] . '&post_type=' . $row[2] ) ); ?>">
									<?php esc_html_e( 'Edit terms', 'super-ninety-four' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Public hubs', 'super-ninety-four' ); ?></h2>
			<ul class="snf-admin__list">
				<?php
				$hub = get_page_by_path( 'videos-audio' );
				?>
				<li>
					<span><?php esc_html_e( 'Videos & Audio page', 'super-ninety-four' ); ?></span>
					<?php if ( $hub ) : ?>
						<a href="<?php echo esc_url( get_permalink( $hub ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
					<?php else : ?>
						<span class="snf-admin__pill snf-admin__pill--bad"><?php esc_html_e( 'Missing', 'super-ninety-four' ); ?></span>
					<?php endif; ?>
				</li>
				<li>
					<span><?php esc_html_e( 'Video archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'snf_video' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
				<li>
					<span><?php esc_html_e( 'Audio archive', 'super-ninety-four' ); ?></span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'snf_audio' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
				</li>
			</ul>
		</section>

		<?php if ( current_user_can( 'manage_options' ) && function_exists( 'snf_get_media_settings' ) ) : ?>
			<?php $s = snf_get_media_settings(); ?>
		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Media & live-room settings', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Access defaults, YouTube embeds, WebRTC rooms, and hub intro copy.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_media_save_settings">
				<input type="hidden" name="snf_media_redirect" value="snf-club-media">
				<?php wp_nonce_field( 'snf_media_settings', 'snf_media_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Hub intro', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="hub_intro" class="large-text" rows="3"><?php echo esc_textarea( (string) $s['hub_intro'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'YouTube privacy-enhanced embeds', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="youtube_privacy_enhanced" value="1" <?php checked( ! empty( $s['youtube_privacy_enhanced'] ) ); ?>>
								<?php esc_html_e( 'Use youtube-nocookie.com', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default video access', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_video_access">
								<?php foreach ( snf_media_access_levels() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['default_video_access'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default audio access', 'super-ninety-four' ); ?></th>
						<td>
							<select name="default_audio_access">
								<?php foreach ( snf_media_access_levels() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['default_audio_access'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default live provider', 'super-ninety-four' ); ?></th>
						<td>
							<select name="live_provider">
								<?php foreach ( snf_live_provider_choices() as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $s['live_provider'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'WebRTC live audio rooms', 'super-ninety-four' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enable_live_audio_rooms" value="1" <?php checked( ! empty( $s['enable_live_audio_rooms'] ) ); ?>>
								<?php esc_html_e( 'Enable browser audio rooms for hybrid / virtual events', 'super-ninety-four' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'ICE servers (one per line)', 'super-ninety-four' ); ?></th>
						<td>
							<textarea name="ice_servers" class="large-text code" rows="4"><?php echo esc_textarea( (string) $s['ice_servers'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Room signal TTL (seconds)', 'super-ninety-four' ); ?></th>
						<td>
							<input type="number" name="audio_room_ttl" value="<?php echo esc_attr( (string) $s['audio_room_ttl'] ); ?>" min="30" step="1">
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save media settings', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>
		<?php endif; ?>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Critical settings dashboard.
 */
function snf_admin_render_critical_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	snf_admin_render_shell_start(
		'snf-club-settings',
		__( 'Critical settings', 'super-ninety-four' ),
		__( 'Integrations and maintenance tools the Events, Circles, and Media stacks rely on.', 'super-ninety-four' )
	);

	$mapbox = function_exists( 'snf_get_mapbox_token' ) ? (string) snf_get_mapbox_token() : (string) get_option( 'snf_mapbox_token', '' );
	?>
	<div class="snf-admin__grid snf-admin__grid--panels">
		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Mapbox', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Lead rider / Expo map token. Constant SNF_MAPBOX_TOKEN overrides the option when defined.', 'super-ninety-four' ); ?></p>
			<form class="snf-admin__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="snf_club_save_critical">
				<?php wp_nonce_field( 'snf_club_critical', 'snf_club_critical_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mapbox token', 'super-ninety-four' ); ?></th>
						<td>
							<input type="text" class="large-text code" name="snf_mapbox_token" value="<?php echo esc_attr( $mapbox ); ?>" autocomplete="off">
							<?php if ( defined( 'SNF_MAPBOX_TOKEN' ) && SNF_MAPBOX_TOKEN ) : ?>
								<p class="description"><?php esc_html_e( 'A wp-config constant is currently defining the live token.', 'super-ninety-four' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Mapbox token', 'super-ninety-four' ), 'primary', 'submit', false ); ?>
			</form>
		</section>

		<section class="snf-admin__panel">
			<h2><?php esc_html_e( 'Payments & CRM', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Stripe / Supabase live on the WooCommerce SNF Stripe screen.', 'super-ninety-four' ); ?></p>
			<ul class="snf-admin__list">
				<li>
					<span><?php esc_html_e( 'Stripe readiness', 'super-ninety-four' ); ?></span>
					<span class="snf-admin__pill <?php echo ( function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready() ) ? 'snf-admin__pill--ok' : 'snf-admin__pill--warn'; ?>">
						<?php echo ( function_exists( 'snf_stripe_is_ready' ) && snf_stripe_is_ready() ) ? esc_html__( 'Ready', 'super-ninety-four' ) : esc_html__( 'Check keys', 'super-ninety-four' ); ?>
					</span>
				</li>
			</ul>
			<div class="snf-admin__actions">
				<a class="snf-admin__btn" href="<?php echo esc_url( admin_url( 'admin.php?page=snf-stripe' ) ); ?>"><?php esc_html_e( 'Open SNF Stripe', 'super-ninety-four' ); ?></a>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>"><?php esc_html_e( 'Woo payments', 'super-ninety-four' ); ?></a>
			</div>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Page health', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Club pages auto-created by the theme. Missing rows usually mean bootstrap has not run.', 'super-ninety-four' ); ?></p>
			<table class="snf-admin__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Surface', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Status', 'super-ninety-four' ); ?></th>
						<th><?php esc_html_e( 'Links', 'super-ninety-four' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( snf_admin_page_health_rows() as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
							<td>
								<span class="snf-admin__pill <?php echo $row['ok'] ? 'snf-admin__pill--ok' : 'snf-admin__pill--bad'; ?>">
									<?php echo $row['ok'] ? esc_html__( 'Present', 'super-ninety-four' ) : esc_html__( 'Missing', 'super-ninety-four' ); ?>
								</span>
							</td>
							<td>
								<?php if ( $row['edit'] ) : ?>
									<a href="<?php echo esc_url( $row['edit'] ); ?>"><?php esc_html_e( 'Edit', 'super-ninety-four' ); ?></a>
									·
									<a href="<?php echo esc_url( $row['view'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'super-ninety-four' ); ?></a>
								<?php else : ?>
									<span class="snf-admin__muted">—</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="snf-admin__panel snf-admin__panel--wide">
			<h2><?php esc_html_e( 'Maintenance', 'super-ninety-four' ); ?></h2>
			<p class="snf-admin__panel-desc"><?php esc_html_e( 'Safe ops tools after theme sync or Site Editor experiments.', 'super-ninety-four' ); ?></p>
			<div class="snf-admin__actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_club_flush_rewrites">
					<?php wp_nonce_field( 'snf_club_flush', 'snf_club_flush_nonce' ); ?>
					<button type="submit" class="snf-admin__btn"><?php esc_html_e( 'Flush permalinks', 'super-ninety-four' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="snf_club_reset_styles">
					<?php wp_nonce_field( 'snf_club_reset_styles', 'snf_club_reset_styles_nonce' ); ?>
					<button type="submit" class="snf-admin__btn snf-admin__btn--ghost"><?php esc_html_e( 'Reset Global Styles', 'super-ninety-four' ); ?></button>
				</form>
				<a class="snf-admin__btn snf-admin__btn--ghost" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Site Editor', 'super-ninety-four' ); ?></a>
			</div>
		</section>
	</div>
	<?php
	snf_admin_render_shell_end();
}

/**
 * Save event settings.
 */
function snf_admin_handle_event_settings_save() {
	if ( ! isset( $_POST['snf_event_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_event_settings', 'snf_event_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current   = snf_get_event_settings();
	$modes     = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
	$providers = function_exists( 'snf_live_provider_choices' ) ? snf_live_provider_choices() : array();

	$mode     = isset( $_POST['default_attendance_mode'] ) ? sanitize_key( wp_unslash( $_POST['default_attendance_mode'] ) ) : $current['default_attendance_mode'];
	$provider = isset( $_POST['default_live_provider'] ) ? sanitize_key( wp_unslash( $_POST['default_live_provider'] ) ) : $current['default_live_provider'];

	$current['default_attendance_mode'] = isset( $modes[ $mode ] ) ? $mode : 'hybrid';
	$current['default_live_provider']   = isset( $providers[ $provider ] ) ? $provider : 'youtube_live';
	$current['archive_show_past']       = isset( $_POST['archive_show_past'] ) ? 1 : 0;
	$current['require_ticket_product']  = isset( $_POST['require_ticket_product'] ) ? 1 : 0;

	update_option( SNF_EVENT_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-events&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_event_save_settings', 'snf_admin_handle_event_settings_save' );

/**
 * Save circle settings.
 */
function snf_admin_handle_circle_settings_save() {
	if ( ! isset( $_POST['snf_circle_settings_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_circle_settings', 'snf_circle_settings_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$current = snf_get_circle_settings();
	$choices = snf_circle_visibility_choices();
	$vis     = isset( $_POST['default_visibility'] ) ? sanitize_key( wp_unslash( $_POST['default_visibility'] ) ) : $current['default_visibility'];

	$current['default_visibility'] = isset( $choices[ $vis ] ) ? $vis : 'members';
	$current['allow_self_join']    = isset( $_POST['allow_self_join'] ) ? 1 : 0;
	$current['hub_intro']          = isset( $_POST['hub_intro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hub_intro'] ) ) : '';

	update_option( SNF_CIRCLE_SETTINGS_OPTION, $current, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-circles&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_circle_save_settings', 'snf_admin_handle_circle_settings_save' );

/**
 * Save critical settings (Mapbox).
 */
function snf_admin_handle_critical_save() {
	if ( ! isset( $_POST['snf_club_critical_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_critical', 'snf_club_critical_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$token = isset( $_POST['snf_mapbox_token'] ) ? sanitize_text_field( wp_unslash( $_POST['snf_mapbox_token'] ) ) : '';
	update_option( 'snf_mapbox_token', $token, false );

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&updated=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_save_critical', 'snf_admin_handle_critical_save' );

/**
 * Flush rewrites from critical settings.
 */
function snf_admin_handle_flush_rewrites() {
	if ( ! isset( $_POST['snf_club_flush_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_flush', 'snf_club_flush_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	flush_rewrite_rules( false );
	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&flushed=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_flush_rewrites', 'snf_admin_handle_flush_rewrites' );

/**
 * Reset Global Styles from critical settings.
 */
function snf_admin_handle_reset_styles() {
	if ( ! isset( $_POST['snf_club_reset_styles_nonce'] ) ) {
		return;
	}
	check_admin_referer( 'snf_club_reset_styles', 'snf_club_reset_styles_nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden.', 'super-ninety-four' ) );
	}

	$stylesheet = get_stylesheet();
	$template   = get_template();
	$posts      = get_posts(
		array(
			'post_type'      => 'wp_global_styles',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $post ) {
		$name = (string) $post->post_name;
		if (
			false !== strpos( $name, $stylesheet )
			|| false !== strpos( $name, $template )
			|| false !== strpos( $name, 'super-ninety-four' )
		) {
			wp_delete_post( (int) $post->ID, true );
		}
	}

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=snf-club-settings&styles_reset=1' ) );
	exit;
}
add_action( 'admin_post_snf_club_reset_styles', 'snf_admin_handle_reset_styles' );

/**
 * Circle visibility meta box.
 */
function snf_admin_circle_meta_boxes() {
	add_meta_box(
		'snf_circle_visibility',
		__( 'Circle access', 'super-ninety-four' ),
		'snf_admin_render_circle_visibility_box',
		'snf_circle',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_snf_circle', 'snf_admin_circle_meta_boxes' );

/**
 * Render circle visibility box.
 *
 * @param WP_Post $post Post.
 */
function snf_admin_render_circle_visibility_box( $post ) {
	wp_nonce_field( 'snf_circle_visibility', 'snf_circle_visibility_nonce' );
	$value = (string) get_post_meta( $post->ID, '_snf_circle_visibility', true );
	if ( '' === $value ) {
		$settings = snf_get_circle_settings();
		$value    = (string) $settings['default_visibility'];
	}
	?>
	<p>
		<label for="snf_circle_visibility"><strong><?php esc_html_e( 'Visibility', 'super-ninety-four' ); ?></strong></label>
	</p>
	<select name="snf_circle_visibility" id="snf_circle_visibility" style="width:100%">
		<?php foreach ( snf_circle_visibility_choices() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Save circle visibility.
 *
 * @param int $post_id Post ID.
 */
function snf_admin_save_circle_visibility( $post_id ) {
	if ( ! isset( $_POST['snf_circle_visibility_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['snf_circle_visibility_nonce'] ) ), 'snf_circle_visibility' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$choices = snf_circle_visibility_choices();
	$value   = isset( $_POST['snf_circle_visibility'] ) ? sanitize_key( wp_unslash( $_POST['snf_circle_visibility'] ) ) : 'members';
	if ( ! isset( $choices[ $value ] ) ) {
		$value = 'members';
	}
	update_post_meta( $post_id, '_snf_circle_visibility', $value );
}
add_action( 'save_post_snf_circle', 'snf_admin_save_circle_visibility' );

/**
 * Default visibility when publishing a new circle.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 * @param bool    $update  Whether update.
 */
function snf_admin_circle_set_default_visibility( $post_id, $post, $update ) {
	if ( $update || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! $post || 'snf_circle' !== $post->post_type ) {
		return;
	}
	if ( get_post_meta( $post_id, '_snf_circle_visibility', true ) ) {
		return;
	}
	$settings = snf_get_circle_settings();
	update_post_meta( $post_id, '_snf_circle_visibility', $settings['default_visibility'] );
}
add_action( 'wp_insert_post', 'snf_admin_circle_set_default_visibility', 20, 3 );

/**
 * List-table columns for key CPTs.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_event_columns( $columns ) {
	$columns['snf_attendance'] = __( 'Attendance', 'super-ninety-four' );
	$columns['snf_live']       = __( 'Live', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_event_posts_columns', 'snf_admin_event_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_event_column_content( $column, $post_id ) {
	if ( 'snf_attendance' === $column ) {
		$mode  = (string) get_post_meta( $post_id, '_snf_attendance_mode', true );
		$modes = function_exists( 'snf_event_attendance_modes' ) ? snf_event_attendance_modes() : array();
		echo esc_html( $modes[ $mode ] ?? ( $mode ?: '—' ) );
	}
	if ( 'snf_live' === $column ) {
		$status = (string) get_post_meta( $post_id, '_snf_live_status', true );
		echo esc_html( $status ? $status : '—' );
	}
}
add_action( 'manage_event_posts_custom_column', 'snf_admin_event_column_content', 10, 2 );

/**
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_circle_columns( $columns ) {
	$columns['snf_visibility'] = __( 'Visibility', 'super-ninety-four' );
	$columns['snf_members']    = __( 'Members', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_snf_circle_posts_columns', 'snf_admin_circle_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_circle_column_content( $column, $post_id ) {
	if ( 'snf_visibility' === $column ) {
		$vis     = (string) get_post_meta( $post_id, '_snf_circle_visibility', true );
		$choices = snf_circle_visibility_choices();
		echo esc_html( $choices[ $vis ] ?? ( $vis ?: '—' ) );
	}
	if ( 'snf_members' === $column && function_exists( 'snf_community_members_table' ) ) {
		global $wpdb;
		$table = snf_community_members_table();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE circle_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $post_id
			)
		);
		echo esc_html( (string) $count );
	}
}
add_action( 'manage_snf_circle_posts_custom_column', 'snf_admin_circle_column_content', 10, 2 );

/**
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function snf_admin_video_columns( $columns ) {
	$columns['snf_access'] = __( 'Access', 'super-ninety-four' );
	return $columns;
}
add_filter( 'manage_snf_video_posts_columns', 'snf_admin_video_columns' );
add_filter( 'manage_snf_audio_posts_columns', 'snf_admin_video_columns' );

/**
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function snf_admin_media_column_content( $column, $post_id ) {
	if ( 'snf_access' !== $column ) {
		return;
	}
	$access = (string) get_post_meta( $post_id, '_snf_media_access', true );
	if ( ! $access ) {
		$access = (string) get_post_meta( $post_id, '_snf_access', true );
	}
	$levels = function_exists( 'snf_media_access_levels' ) ? snf_media_access_levels() : array();
	echo esc_html( $levels[ $access ] ?? ( $access ?: '—' ) );
}
add_action( 'manage_snf_video_posts_custom_column', 'snf_admin_media_column_content', 10, 2 );
add_action( 'manage_snf_audio_posts_custom_column', 'snf_admin_media_column_content', 10, 2 );
