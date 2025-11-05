<?php

add_filter( 'email_essentials_ip_services', function () {
	$services = [
		'ipv4'       => 'https://ip4.acato.nl',
		'ipv6'       => 'https://ip6.acato.nl',
		'dual-stack' => 'https://ip.acato.nl',
	];

	return $services;
} );

add_filter( 'email_essentials_website_root_path', function ( $root_path ) {
	$wp_path_rel_to_home = email_essentials_get_wp_subdir();

	if ( '' !== $wp_path_rel_to_home ) {
		$pos       = strripos( str_replace( '\\', '/', ABSPATH ), trailingslashit( $wp_path_rel_to_home ) );
		$home_path = substr( ABSPATH, 0, $pos );
		$home_path = trailingslashit( $home_path );
		$root_path = email_essentials_nice_path( $home_path );
	}

	// Support Deployer style paths.
	if ( preg_match( '@/releases/(\d+)/@', $root_path, $matches ) ) {
		$path_named_current = str_replace( '/releases/' . $matches[1] . '/', '/current/', $root_path );
		if ( is_dir( $path_named_current ) && realpath( $path_named_current ) === realpath( $root_path ) ) {
			$root_path = $path_named_current;
		}

	}

	return $root_path;
} );

/**
 * Get the WordPress subdirectory relative to home URL.
 *
 * @return string The subdirectory path, or empty string if WordPress is installed in the root.
 */
function email_essentials_get_wp_subdir() {
	$home    = preg_replace( '@https?://@', 'http://', get_option( 'home' ) );
	$siteurl = preg_replace( '@https?://@', 'http://', get_option( 'siteurl' ) );

	if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
		return str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
	}

	return '';
}

/**
 * Cleanup a path a bit.
 *
 * @param string $path The path to cleanup.
 *
 * @return string
 */
function email_essentials_nice_path( $path ) {
	// Turn \ into / .
	$path = str_replace( '\\', '/', $path );
	// Remove "current" instances.
	$path = str_replace( '/./', '/', $path );
	// phpcs:ignore Generic.Commenting.Todo.TaskFound
	// @todo: remove  ../somethingotherthandotdot/ .

	return $path;
}
