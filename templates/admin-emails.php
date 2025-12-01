<?php
/**
 * View: email log.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

use stdClass;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}
global $wpdb;

// @phpcs:disable WordPress.Security.NonceVerification.Recommended
$acato_email_essentials_view_order_field = isset( $_GET['_ofield'] ) ? sanitize_text_field( wp_unslash( $_GET['_ofield'] ) ) : 'ID';

if (
	! in_array(
		$acato_email_essentials_view_order_field,
		[
			'subject',
			'sender',
			'thedatetime',
			'recipient',
		],
		true
	)
) {
	$acato_email_essentials_view_order_field = 'ID';
}

$acato_email_essentials_view_order_direction = isset( $_GET['_order'] ) ? ( 'DESC' === $_GET['_order'] ? 'DESC' : 'ASC' ) : ( 'ID' === $acato_email_essentials_view_order_field ? 'DESC' : 'ASC' );
$acato_email_essentials_view_items_per_page  = isset( $_GET['_limit'] ) && (int) $_GET['_limit'] > 0 ? (int) $_GET['_limit'] : 25;
$acato_email_essentials_view_current_page    = isset( $_GET['_page'] ) && (int) $_GET['_page'] > 0 ? (int) $_GET['_page'] : 0;
$acato_email_essentials_view_first_item      = $acato_email_essentials_view_current_page * $acato_email_essentials_view_items_per_page;
// @phpcs:enable WordPress.Security.NonceVerification.Recommended

$acato_email_essentials_default_sender = Plugin::get_config()['from_email'];
$acato_email_essentials_wp_admin_email = get_option( 'admin_email' );
?>
<div class="wrap wpes-wrap wpes-emails wpes-admin">
	<?php
	Plugin::template_header( __( 'Email History', 'email-essentials' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$acato_email_essentials_view_total_nr_items = $wpdb->get_var( "SELECT COUNT(ID) as thecount FROM {$wpdb->prefix}acato_email_essentials_history" );
	if ( $acato_email_essentials_view_first_item > $acato_email_essentials_view_total_nr_items ) {
		$acato_email_essentials_view_first_item = 0;
	}
	$acato_email_essentials_view_nr_pages  = ceil( $acato_email_essentials_view_total_nr_items / $acato_email_essentials_view_items_per_page );
	$acato_email_essentials_view_next_page = $acato_email_essentials_view_current_page + 1;
	$acato_email_essentials_view_prev_page = $acato_email_essentials_view_current_page - 1;
	if ( $acato_email_essentials_view_prev_page < 0 ) {
		$acato_email_essentials_view_prev_page = false;
	}
	if ( $acato_email_essentials_view_next_page > $acato_email_essentials_view_nr_pages - 1 ) {
		$acato_email_essentials_view_next_page = false;
	}
	?>
	<?php
	// Generate page numbers with smart ellipsis.
	$acato_email_essentials_view_page_range = 2; // Show 2 pages on each side of current page.
	$acato_email_essentials_view_pages      = [];

	for ( $acato_email_essentials_iterator = 0; $acato_email_essentials_iterator < $acato_email_essentials_view_nr_pages; $acato_email_essentials_iterator++ ) {
		// Always show first page, last page, and pages around current page.
		if ( 0 === $acato_email_essentials_iterator || $acato_email_essentials_iterator === $acato_email_essentials_view_nr_pages - 1 || abs( $acato_email_essentials_iterator - $acato_email_essentials_view_current_page ) <= $acato_email_essentials_view_page_range ) {
			$acato_email_essentials_view_pages[] = $acato_email_essentials_iterator;
		} elseif ( ! empty( $acato_email_essentials_view_pages ) && end( $acato_email_essentials_view_pages ) !== '...' ) {
			$acato_email_essentials_view_pages[] = '...';
		}
	}
	?>
	<div class="pager">
		<?php if ( $acato_email_essentials_view_current_page >= 2 ) { ?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', 0 ) ); ?>"><?php echo esc_html_x( '« First', 'Paginator', 'email-essentials' ); ?></a>
		<?php } ?>

		<?php if ( false !== $acato_email_essentials_view_prev_page ) { ?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_prev_page ) ); ?>"><?php echo esc_html_x( '< Previous', 'Paginator', 'email-essentials' ); ?></a>
		<?php } ?>

		<span>
			<?php foreach ( $acato_email_essentials_view_pages as $acato_email_essentials_page_num ) : ?>
				<?php if ( '...' === $acato_email_essentials_page_num ) : ?>
					<span class="ellipsis">...</span>
				<?php else : ?>
					<?php if ( $acato_email_essentials_page_num === $acato_email_essentials_view_current_page ) : ?>
						<strong><?php print esc_html( $acato_email_essentials_page_num + 1 ); ?></strong>
					<?php else : ?>
						<a
							class="button"
							href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_page_num ) ); ?>"><?php print esc_html( $acato_email_essentials_page_num + 1 ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</span>

		<?php if ( false !== $acato_email_essentials_view_next_page ) { ?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_next_page ) ); ?>"><?php echo esc_html_x( 'Next >', 'Paginator', 'email-essentials' ); ?></a>
		<?php } ?>

		<?php if ( $acato_email_essentials_view_current_page < $acato_email_essentials_view_nr_pages - 2 ) { ?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_nr_pages - 1 ) ); ?>"><?php echo esc_html_x( 'Last »', 'Paginator', 'email-essentials' ); ?></a>
		<?php } ?>

		<span>
			<?php
			// Show reset button if custom sorting is active.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation.
			if ( isset( $_GET['_ofield'] ) ) {
				$acato_email_essentials_reset_url = remove_query_arg( [ '_ofield', '_order' ] );
				?>
				<a class="button" href="<?php print esc_attr( $acato_email_essentials_reset_url ); ?>">
					<?php esc_html_e( 'Reset Sorting', 'email-essentials' ); ?>
				</a>
				<?php
			}
			?>
			<label
				for="wpes-page-size"><?php echo esc_html_x( 'Page size:', 'Paginator', 'email-essentials' ); ?></label>
			<select id="wpes-page-size" name="_limit">
				<?php foreach ( [ 25, 50, 100, 250 ] as $acato_email_essentials_page_size ) : ?>
					<option
						value="<?php print esc_attr( $acato_email_essentials_page_size ); ?>" <?php selected( $acato_email_essentials_view_items_per_page, $acato_email_essentials_page_size ); ?>>
						<?php print esc_html( $acato_email_essentials_page_size ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</span>
	</div>

	<div id="poststuff">
		<div class="postbox">
			<div class="postbox-header">
				<h2>
					<?php print wp_kses_post( __( 'Email History', 'email-essentials' ) ); ?>
				</h2>
			</div>
			<div class="inside">
				<div class="wpes-email-history">
					<table class="wp-list-table widefat fixed striped table-view-list">
						<thead>
						<tr>
							<td class="eml"><span class="dashicons dashicons-email-alt"></span></td>
							<?php
							// Sortable columns.
							$acato_email_essentials_sortable_columns = [
								'thedatetime' => __( 'Date/Time', 'email-essentials' ),
								'recipient'   => __( 'Recipient', 'email-essentials' ),
								'sender'      => __( 'Sender', 'email-essentials' ),
								'subject'     => __( 'Subject', 'email-essentials' ),
							];
							foreach ( $acato_email_essentials_sortable_columns as $acato_email_essentials_column_key => $acato_email_essentials_column_label ) {
								$acato_email_essentials_is_active_sort = ( $acato_email_essentials_view_order_field === $acato_email_essentials_column_key );
								// Toggle direction if clicking on active column.
								$acato_email_essentials_new_direction  = $acato_email_essentials_is_active_sort ? ( 'ASC' === $acato_email_essentials_view_order_direction ? 'DESC' : 'ASC' ) : 'ASC';
								$acato_email_essentials_sort_url       = add_query_arg(
									[
										'_ofield' => $acato_email_essentials_column_key,
										'_order'  => $acato_email_essentials_new_direction,
									]
								);
								$acato_email_essentials_direction_icon = '';
								if ( $acato_email_essentials_is_active_sort ) {
									$acato_email_essentials_direction_icon = 'ASC' === $acato_email_essentials_view_order_direction ? ' <span class="dashicons dashicons-arrow-up-alt2"></span>' : ' <span class="dashicons dashicons-arrow-down-alt2"></span>';
								}
								?>
								<td class="<?php print esc_attr( $acato_email_essentials_column_key ); ?>">
									<a href="<?php print esc_attr( $acato_email_essentials_sort_url ); ?>">
										<?php print esc_html( $acato_email_essentials_column_label ); ?><?php print wp_kses_post( $acato_email_essentials_direction_icon ); ?>
									</a>
								</td>
								<?php
							}
							?>
							<td class="status"><?php esc_html_e( 'Status', 'email-essentials' ); ?></td>
							<td></td>
						</tr>
						</thead>

						<tbody id="the-list">
						<?php
						/**
						 * Note to reviewers:
						 *
						 * All input in the query has been sanitized on top of this file.
						 *
						 * $acato_email_essentials_view_order_direction can only be ASC or DESC
						 * $acato_email_essentials_view_order_field can only be subject, sender, thedatetime, recipient or ID
						 * $acato_email_essentials_view_first_item and $acato_email_essentials_view_items_per_page are integers greater than or equal to 0
						 *
						 * The Prepare here is because automated review by WordPress.org has detected this.
						 * It serves no purpose other than that, as all data is sanitized before injection.
						 *
						 * Also; an ORDER BY direction indicator cannot be parameterized, so we have to inject those directly.
						 */

						// If WP Version >= 6.2, we can use %i for identifiers.
						if ( Plugin::wp_version_at_least_62() ) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All data is sanitized before injection.
							$acato_email_essentials_view_emails_list = $wpdb->get_results(
								$wpdb->prepare(
								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- give me a way to parameterize ORDER BY direction indicators and I'll use it.
									"SELECT subject, sender, thedatetime, recipient, ID, body, alt_body, headers, status, `debug`, errinfo, eml FROM {$wpdb->prefix}acato_email_essentials_history ORDER BY %i $acato_email_essentials_view_order_direction LIMIT %d,%d",
									$acato_email_essentials_view_order_field,
									$acato_email_essentials_view_first_item,
									$acato_email_essentials_view_items_per_page
								)
							);
						} else {
							$acato_email_essentials_view_order_field = esc_sql( $acato_email_essentials_view_order_field );
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All data is sanitized before injection.
							$acato_email_essentials_view_emails_list = $wpdb->get_results(
								$wpdb->prepare(
								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WP 6.2 supports %i, but older does not. We have no choice but to inject directly after sanitization. Note that we wrap it in backticks to prevent SQL injection.
									"SELECT subject, sender, thedatetime, recipient, ID, body, alt_body, headers, status, `debug`, errinfo, eml FROM {$wpdb->prefix}acato_email_essentials_history ORDER BY `$acato_email_essentials_view_order_field` $acato_email_essentials_view_order_direction LIMIT %d,%d",
									$acato_email_essentials_view_first_item,
									$acato_email_essentials_view_items_per_page
								)
							);
						}

						$acato_email_essentials_view_email_stati = [
							History::MAIL_NEW    => _x( 'Sent ??', 'Email log: this email is Sent', 'email-essentials' ),
							History::MAIL_SENT   => _x( 'Sent Ok', 'Email log: this email is Sent OK', 'email-essentials' ),
							History::MAIL_FAILED => _x( 'Failed', 'Email log: this email Failed sending', 'email-essentials' ),
							History::MAIL_RESENT => _x( 'Failed and resend attempted', 'Email log: this email Failed sending, but a resend was attempted', 'email-essentials' ),
							History::MAIL_OPENED => _x( 'Opened', 'Email log: this email is Opened by the receiver', 'email-essentials' ),
						];
						foreach ( $acato_email_essentials_view_emails_list as $acato_email_essentials_view_email ) {
							// Get the sender from the log. This might be replaced, if so, this is reply-to, indicated with * .
							$acato_email_essentials__sender   = $acato_email_essentials_view_email->sender;
							$acato_email_essentials__reply_to = '';
							// This is reply-to!
							if ( substr( $acato_email_essentials__sender, -2, 2 ) === ' *' ) {
								$acato_email_essentials__reply_to = trim( $acato_email_essentials__sender, ' *' );
								// So who sent it?
								// 1. Get from Debug data, Sender if available, From otherwise, and FromName if we have it.
								list( $acato_email_essentials_view_email->debug, $acato_email_essentials_view_email->log ) = explode( '----', $acato_email_essentials_view_email->debug );
								$acato_email_essentials__debug = json_decode( $acato_email_essentials_view_email->debug );
								// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								$acato_email_essentials__sender = $acato_email_essentials__debug->Sender ?: $acato_email_essentials__debug->From;
								if ( $acato_email_essentials__sender ) {
									// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									if ( $acato_email_essentials__debug->FromName ) {
										$acato_email_essentials__sender = Plugin::rfc_encode(
											[
												// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
												'name'  => $acato_email_essentials__debug->FromName,
												// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
												'email' => $acato_email_essentials__debug->Sender ?: $acato_email_essentials__debug->From,
											]
										);
										$acato_email_essentials__sender = esc_html( $acato_email_essentials__sender );
									}
								} else {
									// If not available, then assume it is the configured email address.
									$acato_email_essentials__sender = $acato_email_essentials_default_sender;
									if ( $acato_email_essentials__sender ) {
										$acato_email_essentials__sender = '<strong style="color: darkgreen">' . esc_html( $acato_email_essentials__sender ) . '</strong>';
									} else {
										// Unless that is not set-up yet, then we assume the WP default, which might not be accurate.
										$acato_email_essentials__sender = '<strong style="color: orange">' . esc_html( $acato_email_essentials_wp_admin_email ) . '</strong>';
									}
								}
								$acato_email_essentials__reply_to = esc_html( $acato_email_essentials__reply_to );
							} else {
								$acato_email_essentials__sender = esc_html( $acato_email_essentials__sender );
							}

							preg_match( '/X-Relates-To: (.*)/', $acato_email_essentials_view_email->headers, $matches );
							$acato_email_essentials_relates_to = isset( $matches[1] ) ? esc_html( $matches[1] ) : '';
							$acato_email_essentials_relates_to = explode( ',', $acato_email_essentials_relates_to );
							$acato_email_essentials_relates_to = array_map( 'trim', $acato_email_essentials_relates_to );
							$acato_email_essentials_relates_to = array_map( 'intval', $acato_email_essentials_relates_to );
							$acato_email_essentials_relates_to = array_filter( $acato_email_essentials_relates_to, 'is_int' );
							$acato_email_essentials_relates_to = implode( ',', $acato_email_essentials_relates_to );
							?>
							<tr
								class="email-item email-item__status-<?php print esc_attr( $acato_email_essentials_view_email->status ); ?>"
								data-relates-to="<?php print esc_attr( $acato_email_essentials_relates_to ); ?>"
								id="email-<?php print esc_attr( $acato_email_essentials_view_email->ID ); ?>">
								<td class="eml">
									<?php
									if ( $acato_email_essentials_view_email->eml ) {
										$acato_email_essentials_attachment_count = substr_count( $acato_email_essentials_view_email->eml, 'Content-Disposition: attachment;' );
										if ( 0 !== $acato_email_essentials_attachment_count ) {
											$acato_email_essentials_attachment_count = '<span class="dashicons dashicons-paperclip"></span>' . $acato_email_essentials_attachment_count;
										} else {
											$acato_email_essentials_attachment_count = '';
										}
										print '<a href="' . esc_attr( add_query_arg( 'download_eml', $acato_email_essentials_view_email->ID ) ) . '" class="dashicons dashicons-download"></a> ' . wp_kses_post( Plugin::nice_size( strlen( $acato_email_essentials_view_email->eml ) ) . $acato_email_essentials_attachment_count );
									}
									?>
								</td>
								<td class="thedatetime">
									<?php print esc_html( $acato_email_essentials_view_email->thedatetime ); ?>&nbsp;
								</td>
								<td class="recipient">
									<?php print esc_html( $acato_email_essentials_view_email->recipient ); ?>&nbsp;
								</td>
								<td class="sender">
									<?php print wp_kses_post( $acato_email_essentials__sender . ( $acato_email_essentials__reply_to ? '<br />Reply-To: ' . $acato_email_essentials__reply_to : '' ) ); ?>
								</td>
								<td class="subject">
									<?php print esc_html( $acato_email_essentials_view_email->subject ); ?>&nbsp;
								</td>
								<td class="status">
									<?php print esc_html( $acato_email_essentials_view_email_stati[ $acato_email_essentials_view_email->status ] ); ?><?php print ( $acato_email_essentials_view_email->errinfo ? '<br />' : '' ) . wp_kses_post( $acato_email_essentials_view_email->errinfo ); ?>
								</td>
								<td>
									<?php
									if ( History::MAIL_FAILED === (int) $acato_email_essentials_view_email->status ) {
										$acato_email_essentials_resend_link = add_query_arg(
											[
												'nonce'  => wp_create_nonce( 'wpes_resend_email_' . $acato_email_essentials_view_email->ID ),
												'action' => 'resend-failed-email',
												'email'  => $acato_email_essentials_view_email->ID,
											],
										);
										// If the email failed, then we show the debug info.
										printf( '<a href="%s" class="button button-secondary wpes-email-view">' . esc_html__( 'Resend', 'email-essentials' ) . '</a>', esc_url( $acato_email_essentials_resend_link ) );
									}
									?>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<style>
					.email-item__status-0 td:first-child {
						border-left: 3px solid orange;
					}

					.email-item__status-1 td:first-child {
						border-left: 3px solid green;
					}

					.email-item__status-2 td:first-child {
						border-left: 3px solid red;
					}

					.email-item__status-3 td:first-child {
						border-left: 3px solid yellowgreen;
					}

					.email-item__status-4 {
						opacity: 0.5;
					}
				</style>

				<div class="pager">
					<?php if ( $acato_email_essentials_view_current_page >= 2 ) { ?>
						<a
							class="button"
							href="<?php print esc_attr( add_query_arg( '_page', 0 ) ); ?>"><?php echo esc_html_x( '« First', 'Paginator', 'email-essentials' ); ?></a>
					<?php } ?>

					<?php if ( false !== $acato_email_essentials_view_prev_page ) { ?>
						<a
							class="button"
							href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_prev_page ) ); ?>"><?php echo esc_html_x( '< Previous', 'Paginator', 'email-essentials' ); ?></a>
					<?php } ?>

					<span>
						<?php foreach ( $acato_email_essentials_view_pages as $acato_email_essentials_page_num ) : ?>
							<?php if ( '...' === $acato_email_essentials_page_num ) : ?>
								<span class="ellipsis">...</span>
							<?php else : ?>
								<?php if ( $acato_email_essentials_page_num === $acato_email_essentials_view_current_page ) : ?>
									<strong><?php print esc_html( $acato_email_essentials_page_num + 1 ); ?></strong>
								<?php else : ?>
									<a
										class="button"
										href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_page_num ) ); ?>"><?php print esc_html( $acato_email_essentials_page_num + 1 ); ?></a>
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</span>

					<?php if ( false !== $acato_email_essentials_view_next_page ) { ?>
						<a
							class="button"
							href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_next_page ) ); ?>"><?php echo esc_html_x( 'Next >', 'Paginator', 'email-essentials' ); ?></a>
					<?php } ?>

					<?php if ( $acato_email_essentials_view_current_page < $acato_email_essentials_view_nr_pages - 2 ) { ?>
						<a
							class="button"
							href="<?php print esc_attr( add_query_arg( '_page', $acato_email_essentials_view_nr_pages - 1 ) ); ?>"><?php echo esc_html_x( 'Last »', 'Paginator', 'email-essentials' ); ?></a>
					<?php } ?>

					<span>
						<?php
						// Show reset button if custom sorting is active.
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only operation.
						if ( isset( $_GET['_ofield'] ) ) {
							$acato_email_essentials_reset_url = remove_query_arg( [ '_ofield', '_order' ] );
							?>
							<a class="button" href="<?php print esc_attr( $acato_email_essentials_reset_url ); ?>">
								<?php esc_html_e( 'Reset Sorting', 'email-essentials' ); ?>
							</a>
							<?php
						}
						?>
						<label
							for="wpes-page-size-bottom"><?php echo esc_html_x( 'Page size:', 'Paginator', 'email-essentials' ); ?></label>
						<select id="wpes-page-size-bottom" name="_limit">
							<?php foreach ( [ 25, 50, 100, 250 ] as $acato_email_essentials_page_size ) : ?>
								<option
									value="<?php print esc_attr( $acato_email_essentials_page_size ); ?>" <?php selected( $acato_email_essentials_view_items_per_page, $acato_email_essentials_page_size ); ?>>
									<?php print esc_html( $acato_email_essentials_page_size ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</span>
				</div>

				<div id="mail-viewer">
					<nav class="mail-viewer-tabs" role="tablist" style="display: none">
						<button type="button" class="mail-tab" data-view="body" role="tab" aria-selected="false">
							<span class="dashicons dashicons-email"></span>
							<?php echo esc_html_x( 'HTML Email', 'Email History Legend', 'email-essentials' ); ?>
						</button>
						<button type="button" class="mail-tab" data-view="body-source" role="tab" aria-selected="false">
							<span class="dashicons dashicons-html"></span>
							<?php echo esc_html_x( 'HTML Email Source', 'Email History Legend', 'email-essentials' ); ?>
						</button>
						<button type="button" class="mail-tab" data-view="headers" role="tab" aria-selected="false">
							<span class="dashicons dashicons-admin-settings"></span>
							<?php echo esc_html_x( 'Email Headers', 'Email History Legend', 'email-essentials' ); ?>
						</button>
						<button type="button" class="mail-tab" data-view="alt-body" role="tab" aria-selected="false">
							<span class="dashicons dashicons-text"></span>
							<?php echo esc_html_x( 'Plain Text Alternative', 'Email History Legend', 'email-essentials' ); ?>
						</button>
						<button type="button" class="mail-tab" data-view="debug" role="tab" aria-selected="false">
							<span class="dashicons dashicons-info"></span>
							<?php echo esc_html_x( 'Debug information', 'Email History Legend', 'email-essentials' ); ?>
						</button>
					</nav>
					<div id="mail-data-viewer">
						<?php
						$acato_email_essentials_mailer = new EEMailer();
						// We call this just for initialisation purposes, we do not actually care about the result.
						$acato_email_essentials_css = apply_filters_ref_array(
							'acato_email_essentials_css',
							[ '', &$acato_email_essentials_mailer ]
						);

						foreach ( $acato_email_essentials_view_emails_list as $acato_email_essentials_view_email ) {
							// @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- phpMailer thing. cannot help it.
							$acato_email_essentials_mailer->Subject = $acato_email_essentials_view_email->subject;
							// suffix '----' is to prevent errors like "Undefined array key 1".
							list( $acato_email_essentials_view_email->debug, $acato_email_essentials_view_email->log ) = explode( '----', $acato_email_essentials_view_email->debug . '----' );

							$acato_email_essentials_view_email->debug = json_decode( trim( $acato_email_essentials_view_email->debug ) );
							$acato_email_essentials_view_email->log   = trim( $acato_email_essentials_view_email->log );
							if ( ! $acato_email_essentials_view_email->debug ) {
								$acato_email_essentials_view_email->debug = new stdClass();
							}
							$acato_email_essentials_view_email->debug = wp_json_encode( $acato_email_essentials_view_email->debug, JSON_PRETTY_PRINT );
							$acato_email_essentials_view_email->debug = ( $acato_email_essentials_view_email->log ? $acato_email_essentials_view_email->log . "\n" : '' ) . $acato_email_essentials_view_email->debug;

							// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- how else am I supposed to base64_encode?.
							$acato_email_essentials_email_data_base64 = base64_encode(
								wp_kses(
									Plugin::maybe_convert_to_html( $acato_email_essentials_view_email->body, $acato_email_essentials_view_email->subject, $acato_email_essentials_mailer ),
									Plugin::allowed_html_for_displaying_an_entire_html_page()
								)
							);
							?>
							<div
								class="email-data"
								id="email-data-<?php print esc_attr( $acato_email_essentials_view_email->ID ); ?>">
								<div
									class="headers">
									<pre><?php print esc_html( $acato_email_essentials_view_email->headers ); ?></pre>
								</div>
								<div
									class="alt_body">
									<pre><?php print wp_kses_post( $acato_email_essentials_view_email->alt_body ); ?></pre>
								</div>
								<div
									class="body">
									<div class="body-disclaimer">
										<?php
										echo wp_kses_post(
											__( '<strong>Disclaimer:</strong> The email body is shown below as it was sent. For security reasons, output is sanitized. For actual HTML, see the source panel.', 'email-essentials' )
										);
										?>
									</div>
									<iframe
										class="autofit" width="100%" height="100%" border="0" frameborder="0"
										src="data:text/html;headers=<?php print rawurlencode( 'Content-Security-Policy: script-src none;' ); ?>;base64,<?php print $acato_email_essentials_email_data_base64; /* @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>">
									</iframe>
								</div>
								<div class="body-source">
									<pre><?php print esc_html( Plugin::maybe_convert_to_html( $acato_email_essentials_view_email->body, $acato_email_essentials_view_email->subject, $acato_email_essentials_mailer ) ); ?></pre>
								</div>
								<div
									class="debug">
									<pre><?php print esc_html( $acato_email_essentials_view_email->debug ); ?></pre>
								</div>
							</div>
							<?php
						}
						?>
					</div><!-- /mdv -->
				</div>
			</div>
		</div>
	</div>
