<?php
/**
 * View: email log.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

use stdClass;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}
global $current_user, $wpdb;

// @phpcs:disable WordPress.Security.NonceVerification.Recommended
$wpes_view_order_field = Plugin::get_get_data('_ofield') ?: 'ID';

if ( ! in_array( $wpes_view_order_field, [ 'subject', 'sender', 'thedatetime', 'recipient' ], true ) ) {
	$wpes_view_order_field = 'ID';
}

$wpes_view_order_direction = isset( $_GET['_order'] ) ? ( 'DESC' === $_GET['_order'] ? 'DESC' : 'ASC' ) : ( 'ID' === $wpes_view_order_field ? 'DESC' : 'ASC' );
$wpes_view_items_per_page  = isset( $_GET['_limit'] ) && (int) $_GET['_limit'] > 0 ? (int) $_GET['_limit'] : 25;
$wpes_view_current_page    = isset( $_GET['_page'] ) && (int) $_GET['_page'] > 0 ? (int) $_GET['_page'] : 0;
$wpes_view_first_item      = $wpes_view_current_page * $wpes_view_items_per_page;
// @phpcs:enable WordPress.Security.NonceVerification.Recommended

$wpes_default_sender = Plugin::get_config()['from_email'];
$wpes_wp_admin_email = get_option( 'admin_email' );
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
	$wpes_view_total_nr_items = $wpdb->get_var( "SELECT COUNT(ID) as thecount FROM {$wpdb->prefix}wpes_hist" );
	if ( $wpes_view_first_item > $wpes_view_total_nr_items ) {
		$wpes_view_first_item = 0;
	}
	$wpes_view_nr_pages  = ceil( $wpes_view_total_nr_items / $wpes_view_items_per_page );
	$wpes_view_next_page = $wpes_view_current_page + 1;
	$wpes_view_prev_page = $wpes_view_current_page - 1;
	if ( $wpes_view_prev_page < 0 ) {
		$wpes_view_prev_page = false;
	}
	if ( $wpes_view_next_page > $wpes_view_nr_pages - 1 ) {
		$wpes_view_next_page = false;
	}
	?>
	<div class="pager">
		<span>
			<?php
			if ( false !== $wpes_view_prev_page ) {
				?>
				<a
					class="button"
					href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_prev_page ) ); ?>">
					&lt; Previous page</a> <?php } ?></span>
		<span>
			<?php
			if ( false !== $wpes_view_next_page ) {
				?>
				<a
					class="button"
					href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_next_page ) ); ?>">
					Next page &gt;</a> <?php } ?></span>
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
							<td class="thedatetime"><?php esc_html_e( 'Date/Time', 'email-essentials' ); ?></td>
							<td class="recipient"><?php esc_html_e( 'Recipient', 'email-essentials' ); ?></td>
							<td class="sender"><?php esc_html_e( 'Sender', 'email-essentials' ); ?></td>
							<td class="subject"><?php esc_html_e( 'Subject', 'email-essentials' ); ?></td>
							<td class="status"><?php esc_html_e( 'Status', 'email-essentials' ); ?></td>
							<td></td>
						</tr>
						</thead>

						<tbody id="the-list">
						<?php
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All data is sanitized before injection.
						$wpes_view_emails_list = $wpdb->get_results( "SELECT subject, sender, thedatetime, recipient, ID, body, alt_body, headers, status, `debug`, errinfo, eml FROM {$wpdb->prefix}wpes_hist ORDER BY $wpes_view_order_field $wpes_view_order_direction LIMIT $wpes_view_first_item,$wpes_view_items_per_page" );
						$wpes_view_email_stati = [
							History::MAIL_NEW    => _x( 'Sent ??', 'Email log: this email is Sent', 'email-essentials' ),
							History::MAIL_SENT   => _x( 'Sent Ok', 'Email log: this email is Sent OK', 'email-essentials' ),
							History::MAIL_FAILED => _x( 'Failed', 'Email log: this email Failed sending', 'email-essentials' ),
							History::MAIL_RESENT => _x( 'Failed and resend attempted', 'Email log: this email Failed sending, but a resend was attempted', 'email-essentials' ),
							History::MAIL_OPENED => _x( 'Opened', 'Email log: this email is Opened by the receiver', 'email-essentials' ),
						];
						foreach ( $wpes_view_emails_list as $wpes_view_email ) {
							// Get the sender from the log. This might be replaced, if so, this is reply-to, indicated with * .
							$wpes__sender   = $wpes_view_email->sender;
							$wpes__reply_to = '';
							// This is reply-to!
							if ( substr( $wpes__sender, -2, 2 ) === ' *' ) {
								$wpes__reply_to = trim( $wpes__sender, ' *' );
								// So who sent it?
								// 1. Get from Debug data, Sender if available, From otherwise, and FromName if we have it.
								list( $wpes_view_email->debug, $wpes_view_email->log ) = explode( '----', $wpes_view_email->debug );
								$wpes__debug = json_decode( $wpes_view_email->debug );
								// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								$wpes__sender = $wpes__debug->Sender ?: $wpes__debug->From;
								if ( $wpes__sender ) {
									// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
									if ( $wpes__debug->FromName ) {
										$wpes__sender = Plugin::rfc_encode(
											[
												// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
												'name'  => $wpes__debug->FromName,
												// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
												'email' => $wpes__debug->Sender ?: $wpes__debug->From,
											]
										);
										$wpes__sender = esc_html( $wpes__sender );
									}
								} else {
									// If not available, then assume it is the configured email address.
									$wpes__sender = $wpes_default_sender;
									if ( $wpes__sender ) {
										$wpes__sender = '<strong style="color: darkgreen">' . esc_html( $wpes__sender ) . '</strong>';
									} else {
										// Unless that is not set-up yet, then we assume the WP default, which might not be accurate.
										$wpes__sender = '<strong style="color: orange">' . esc_html( $wpes_wp_admin_email ) . '</strong>';
									}
								}
								$wpes__reply_to = esc_html( $wpes__reply_to );
							} else {
								$wpes__sender = esc_html( $wpes__sender );
							}

							preg_match( '/X-Relates-To: (.*)/', $wpes_view_email->headers, $matches );
							$wpes_relates_to = isset( $matches[1] ) ? esc_html( $matches[1] ) : '';
							$wpes_relates_to = explode( ',', $wpes_relates_to );
							$wpes_relates_to = array_map( 'trim', $wpes_relates_to );
							$wpes_relates_to = array_map( 'intval', $wpes_relates_to );
							$wpes_relates_to = array_filter( $wpes_relates_to, 'is_int' );
							$wpes_relates_to = implode( ',', $wpes_relates_to );
							?>
							<tr
								class="email-item email-item__status-<?php print esc_attr( $wpes_view_email->status ); ?>"
								data-relates-to="<?php print esc_attr( $wpes_relates_to ); ?>"
								id="email-<?php print esc_attr( $wpes_view_email->ID ); ?>">
								<td class="eml">
									<?php
									if ( $wpes_view_email->eml ) {
										$wpes_attachment_count = substr_count( $wpes_view_email->eml, 'Content-Disposition: attachment;' );
										if ( 0 !== $wpes_attachment_count ) {
											$wpes_attachment_count = '<span class="dashicons dashicons-paperclip"></span>' . $wpes_attachment_count;
										} else {
											$wpes_attachment_count = '';
										}
										print '<a href="' . esc_attr( add_query_arg( 'download_eml', $wpes_view_email->ID ) ) . '" class="dashicons dashicons-download"></a> ' . wp_kses_post( Plugin::nice_size( strlen( $wpes_view_email->eml ) ) . $wpes_attachment_count );
									}
									?>
								</td>
								<td class="thedatetime">
									<?php print esc_html( $wpes_view_email->thedatetime ); ?>&nbsp;
								</td>
								<td class="recipient">
									<?php print esc_html( $wpes_view_email->recipient ); ?>&nbsp;
								</td>
								<td class="sender">
									<?php print wp_kses_post( $wpes__sender . ( $wpes__reply_to ? '<br />Reply-To: ' . $wpes__reply_to : '' ) ); ?>
								</td>
								<td class="subject">
									<?php print esc_html( $wpes_view_email->subject ); ?>&nbsp;
								</td>
								<td class="status">
									<?php print esc_html( $wpes_view_email_stati[ $wpes_view_email->status ] ); ?><?php print ( $wpes_view_email->errinfo ? '<br />' : '' ) . wp_kses_post( $wpes_view_email->errinfo ); ?>
								</td>
								<td>
									<?php
									if ( History::MAIL_FAILED === (int) $wpes_view_email->status ) {
										$wpes_resend_link = add_query_arg(
											[
												'nonce'  => wp_create_nonce( 'wpes_resend_email_' . $wpes_view_email->ID ),
												'action' => 'resend-failed-email',
												'email'  => $wpes_view_email->ID,
											],
										);
										// If the email failed, then we show the debug info.
										printf( '<a href="%s" class="button button-secondary wpes-email-view">' . esc_html__( 'Resend', 'email-essentials' ) . '</a>', esc_url( $wpes_resend_link ) );
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

				<div id="mail-viewer">
					<div id="mail-data-viewer">
						<?php
						$wpes_mailer = new EEMailer();
						// We call this just for initialisation purposes, we do not actually care about the result.
						$wpes_css = apply_filters_ref_array( 'acato_email_essentials_css', [ '', &$wpes_mailer ] );

						foreach ( $wpes_view_emails_list as $wpes_view_email ) {
							// @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- phpMailer thing. cannot help it.
							$wpes_mailer->Subject = $wpes_view_email->subject;
							// suffix '----' is to prevent errors like "Undefined array key 1".
							list( $wpes_view_email->debug, $wpes_view_email->log ) = explode( '----', $wpes_view_email->debug . '----' );

							$wpes_view_email->debug = json_decode( trim( $wpes_view_email->debug ) );
							$wpes_view_email->log   = trim( $wpes_view_email->log );
							if ( ! $wpes_view_email->debug ) {
								$wpes_view_email->debug = new stdClass();
							}
							$wpes_view_email->debug = wp_json_encode( $wpes_view_email->debug, JSON_PRETTY_PRINT );
							$wpes_view_email->debug = ( $wpes_view_email->log ? $wpes_view_email->log . "\n" : '' ) . $wpes_view_email->debug;

							// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- how else am I supposed to base64_encode?.
							$wpes_email_data_base64 = base64_encode(
								str_ireplace(
									[
										'onload',
										'<script',
										'</script>',
									],
									[
										'nonload',
										'[SCRIPT',
										'[/SCRIPT]',
									],
									Plugin::maybe_convert_to_html( $wpes_view_email->body, $wpes_view_email->subject, $wpes_mailer )
								)
							);
							?>
							<div class="email-data" id="email-data-<?php print esc_attr( $wpes_view_email->ID ); ?>">
								<span
									class="headers"
									data-legend="<?php echo esc_html_x( 'Email Headers', 'Email History Legend', 'email-essentials' ); ?>">
									<pre><?php print esc_html( $wpes_view_email->headers ); ?></pre>
								</span>
								<span
									class="alt_body"
									data-legend="<?php echo esc_html_x( 'Plain Text Alternative', 'Email History Legend', 'email-essentials' ); ?>">
									<pre><?php print wp_kses_post( $wpes_view_email->alt_body ); ?></pre>
								</span>
								<span
									class="body"
									data-legend="<?php echo esc_html_x( 'HTML Email Body', 'Email History Legend', 'email-essentials' ); ?>">
									<iframe
										class="autofit" width="100%" height="100%" border="0" frameborder="0"
										src="data:text/html;headers=<?php print rawurlencode( 'Content-Security-Policy: script-src none;' ); ?>;base64,<?php print $wpes_email_data_base64; /* @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>">
									</iframe>
								</span>
								<span
									class="debug"
									data-legend="<?php echo esc_html_x( 'Debug information', 'Email History Legend', 'email-essentials' ); ?>">
									<pre><?php print esc_html( $wpes_view_email->debug ); ?></pre>
								</span>
							</div>
							<?php
						}
						?>
					</div><!-- /mdv -->
				</div>
			</div>
		</div>
	</div>
