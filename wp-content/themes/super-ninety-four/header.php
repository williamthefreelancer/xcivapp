<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$snf_primary_items = snf_primary_menu_items();
$snf_is_member     = is_user_logged_in();
?>
<header class="snf-header snf-header--with-nav<?php echo $snf_is_member ? ' snf-header--member' : ' snf-header--guest'; ?>">
	<div class="snf-container snf-header__container">
		<a class="snf-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img
				src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/logo-white.svg' ); ?>"
				alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				width="209"
				height="20"
				decoding="async"
			>
		</a>

		<nav class="snf-primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'super-ninety-four' ); ?>">
			<button
				class="snf-primary-nav__toggle"
				type="button"
				aria-expanded="false"
				aria-controls="snf-primary-nav-panel"
			>
				<span class="snf-primary-nav__toggle-label"><?php esc_html_e( 'Menu', 'super-ninety-four' ); ?></span>
				<span class="snf-primary-nav__toggle-bars" aria-hidden="true"></span>
			</button>

			<div id="snf-primary-nav-panel" class="snf-primary-nav__panel">
				<ul class="snf-primary-nav__list">
					<?php foreach ( $snf_primary_items as $item ) : ?>
							<?php
							$type     = isset( $item['type'] ) ? $item['type'] : 'link';
							$label    = isset( $item['label'] ) ? $item['label'] : '';
							$url      = isset( $item['url'] ) ? $item['url'] : '#';
							$current  = ! empty( $item['current'] );
							$children = ! empty( $item['children'] ) && is_array( $item['children'] ) ? $item['children'] : array();
							$item_class = 'snf-primary-nav__item';
							if ( 'dropdown' === $type ) {
								$item_class .= ' snf-primary-nav__item--dropdown';
							}
							if ( $current ) {
								$item_class .= ' is-current';
							}
							?>
							<?php if ( 'dropdown' === $type ) : ?>
								<li class="<?php echo esc_attr( $item_class ); ?>">
									<button
										class="snf-primary-nav__trigger"
										type="button"
										aria-expanded="false"
										aria-haspopup="true"
										<?php echo $current ? ' aria-current="true"' : ''; ?>
									>
										<span><?php echo esc_html( $label ); ?></span>
										<svg class="snf-primary-nav__chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
											<path d="M2.5 4.25 6 7.75l3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</button>
									<div class="snf-primary-nav__dropdown" hidden>
										<ul class="snf-primary-nav__sublist" role="list">
											<?php foreach ( $children as $child ) : ?>
												<?php
												$child_type    = isset( $child['type'] ) ? $child['type'] : 'link';
												$child_label   = isset( $child['label'] ) ? $child['label'] : '';
												$child_url     = isset( $child['url'] ) ? $child['url'] : '#';
												$child_current = ! empty( $child['current'] );
												?>
												<?php if ( 'heading' === $child_type ) : ?>
													<li class="snf-primary-nav__subitem snf-primary-nav__subitem--heading<?php echo $child_current ? ' is-current' : ''; ?>">
														<a class="snf-primary-nav__heading" href="<?php echo esc_url( $child_url ); ?>"<?php echo $child_current ? ' aria-current="page"' : ''; ?>>
															<?php echo esc_html( $child_label ); ?>
														</a>
													</li>
												<?php else : ?>
													<li class="snf-primary-nav__subitem<?php echo $child_current ? ' is-current' : ''; ?>">
														<a class="snf-primary-nav__sublink" href="<?php echo esc_url( $child_url ); ?>"<?php echo $child_current ? ' aria-current="page"' : ''; ?>>
															<?php echo esc_html( $child_label ); ?>
														</a>
													</li>
												<?php endif; ?>
											<?php endforeach; ?>
										</ul>
									</div>
								</li>
							<?php else : ?>
								<li class="<?php echo esc_attr( $item_class ); ?>">
									<a class="snf-primary-nav__link" href="<?php echo esc_url( $url ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>>
										<?php echo esc_html( $label ); ?>
									</a>
								</li>
							<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>

		<div class="snf-header__actions">
			<div class="snf-header__meta">
				<a class="snf-header__phone" href="tel:9804619980">980-461-9980</a>
				<?php if ( $snf_is_member ) : ?>
					<a class="snf-header__login" href="<?php echo esc_url( snf_get_dashboard_url() ); ?>">
						<?php esc_html_e( 'Dashboard', 'super-ninety-four' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php if ( ! $snf_is_member ) : ?>
				<a class="snf-header__apply" href="<?php echo esc_url( snf_get_page_url( 'apply' ) ); ?>">
					<?php esc_html_e( 'Apply', 'super-ninety-four' ); ?>
				</a>
			<?php endif; ?>

			<div class="snf-account<?php echo $snf_is_member ? '' : ' snf-account--login'; ?>">
				<?php if ( $snf_is_member ) : ?>
					<button
						class="snf-account__toggle"
						type="button"
						aria-expanded="false"
						aria-controls="snf-account-menu"
						aria-haspopup="menu"
						aria-label="<?php esc_attr_e( 'Open account menu', 'super-ninety-four' ); ?>"
					>
						<img
							class="snf-account__avatar"
							src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/user-profile.svg' ); ?>"
							alt=""
							width="47"
							height="45"
							decoding="async"
						>
					</button>
					<nav id="snf-account-menu" class="snf-account__menu" hidden>
						<ul class="snf-account__list" role="menu">
							<?php foreach ( snf_account_menu_items() as $item ) : ?>
								<?php
								$type = isset( $item['type'] ) ? $item['type'] : 'link';
								?>
								<?php if ( 'search' === $type ) : ?>
									<li class="snf-account__item snf-account__item--search" role="none">
										<a
											class="snf-account__search"
											role="menuitem"
											href="<?php echo esc_url( $item['url'] ); ?>"
										>
											<span class="snf-account__search-icon" aria-hidden="true">
												<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.75"/>
													<path d="M16.2 16.2 21 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
												</svg>
											</span>
											<span class="snf-account__search-text"><?php echo esc_html( $item['placeholder'] ); ?></span>
										</a>
									</li>
								<?php elseif ( 'club' === $type ) : ?>
									<li class="snf-account__item snf-account__item--club" role="none">
										<a class="snf-account__club" role="menuitem" href="<?php echo esc_url( $item['url'] ); ?>">
											<?php echo esc_html( $item['label'] ); ?>
										</a>
										<?php if ( ! empty( $item['children'] ) ) : ?>
											<ul class="snf-account__sublist">
												<?php foreach ( $item['children'] as $child ) : ?>
													<li class="snf-account__item" role="none">
														<a class="snf-account__link snf-account__link--nested" role="menuitem" href="<?php echo esc_url( $child['url'] ); ?>">
															<?php echo esc_html( $child['label'] ); ?>
														</a>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>
									</li>
								<?php else : ?>
									<li class="snf-account__item" role="none">
										<a class="snf-account__link<?php echo ! empty( $item['icon'] ) ? ' snf-account__link--icon' : ''; ?>" role="menuitem" href="<?php echo esc_url( $item['url'] ); ?>">
											<?php if ( ! empty( $item['icon'] ) && 'messages' === $item['icon'] ) : ?>
												<span class="snf-account__item-icon" aria-hidden="true">
													<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
														<path d="M4.5 6.75h15a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5h-9.2L6 20.25v-3H4.5a1.5 1.5 0 0 1-1.5-1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
													</svg>
												</span>
											<?php endif; ?>
											<span><?php echo esc_html( $item['label'] ); ?></span>
										</a>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php else : ?>
					<a
						class="snf-account__toggle snf-account__toggle--link"
						href="<?php echo esc_url( snf_get_page_url( 'member-login' ) ); ?>"
						aria-label="<?php esc_attr_e( 'Member Login', 'super-ninety-four' ); ?>"
					>
						<img
							class="snf-account__avatar"
							src="<?php echo esc_url( get_template_directory_uri() . '/assets/icons/user-profile.svg' ); ?>"
							alt=""
							width="47"
							height="45"
							decoding="async"
						>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</header>
