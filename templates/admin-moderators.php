<?php
/**
 * View: moderators interface.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}

$acato_email_essentials_config               = get_option( 'acato_email_essentials_moderator_keys', [] );
$acato_email_essentials_moderator_keys       = [ 'pingback', 'comment' ];
$acato_email_essentials_moderator_recipients = [
	'author'    => 'notification',
	'moderator' => 'moderation request',
];
if ( ! get_option( 'moderation_notify' ) ) {
	// moderations disabled, so only show notification.
	unset( $acato_email_essentials_moderator_recipients['moderator'] );
}
?>
<div class="wrap wpes-wrap wpes-moderators">
	<?php
	Plugin::template_header( __( 'Alternative Moderators', 'email-essentials' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	?>
	<form id="outpost" class="wpes-admin" method='POST' action="">
		<input type="hidden" name="form_id" value="acato-email-essentials/moderators"/>
		<?php wp_nonce_field( 'acato-email-essentials--moderators', 'wpes-nonce' ); ?>

		<div class="wpes-tools">
			<div class="wpes-tools--box">
				<input
					type="submit" name="op" value="<?php esc_attr_e( 'Save settings', 'email-essentials' ); ?>"
					class="button-primary action"/>
			</div>
		</div>

		<div id="poststuff">
			<div class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Alternative moderators', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<p>
							<?php
							// translators: %s: a special token.
							print wp_kses_post( sprintf( _x( '%s is allowed to disable sending the email.', 'A blackhole special token is allowed...', 'email-essentials' ), '<code>:blackhole:</code>' ) );
							?>
						</p>
					</div>

					<div class="wpes-notice--info">
						<p>
							<?php
							esc_html_e( 'Moderation for pingbacks and comments is', 'email-essentials' );
							print ': ';
							print wp_kses_post( '<strong>' . ( get_option( 'moderation_notify' ) ? __( 'enabled', 'email-essentials' ) : __( 'disabled', 'email-essentials' ) ) . '</strong>.' );
							?>
							<a href="<?php print esc_attr( admin_url( 'options-discussion.php' ) ); ?>#comment_order">
								<?php esc_html_e( 'Change this setting', 'email-essentials' ) . '.'; ?>
							</a>
						</p>
					</div>

					<table class="wpes-info-table equal">
						<tr>
							<th>
								<?php esc_html_e( 'Action', 'email-essentials' ); ?>
							</th>
							<th>
								<?php esc_html_e( 'Send to', 'email-essentials' ); ?>
							</th>
						</tr>
						<?php
						foreach ( $acato_email_essentials_moderator_recipients as $acato_email_essentials_moderator_recipient => $acato_email_essentials_moderator_action ) {
							foreach ( $acato_email_essentials_moderator_keys as $acato_email_essentials_moderator_key ) {
								foreach ( [ 'post' ] as $acato_email_essentials_post_type ) {
									if ( ! isset( $acato_email_essentials_config[ $acato_email_essentials_post_type ][ $acato_email_essentials_moderator_recipient ][ $acato_email_essentials_moderator_key ] ) ) {
										$acato_email_essentials_config[ $acato_email_essentials_post_type ][ $acato_email_essentials_moderator_recipient ][ $acato_email_essentials_moderator_key ] = '';
									}
									// translators: %s: post-type.
									$acato_email_essentials_placeholder = sprintf( __( 'default: owner of %s', 'email-essentials' ), $acato_email_essentials_post_type );
									?>
									<tr>
										<td>
											<label
												for="key-<?php print esc_attr( $acato_email_essentials_post_type ); ?>-<?php print esc_attr( $acato_email_essentials_moderator_recipient ); ?>-<?php print esc_attr( $acato_email_essentials_moderator_key ); ?>">
												<?php
												// translators: %1$s: email type like notification or request, %2$s: comment type like comment or pingback, %3$s: post_type .
												print wp_kses_post( sprintf( __( '<em>%1$s</em> to author on <em>%2$s</em> on <em>%3$s</em>', 'email-essentials' ), $acato_email_essentials_moderator_action, $acato_email_essentials_moderator_key, $acato_email_essentials_post_type ) ) . ':';
												?>
											</label>
										</td>
										<td>
											<input
												class="widefat"
												type="text"
												name="settings[keys][<?php print esc_attr( $acato_email_essentials_post_type ); ?>][<?php print esc_attr( $acato_email_essentials_moderator_recipient ); ?>][<?php print esc_attr( $acato_email_essentials_moderator_key ); ?>]"
												placeholder="<?php print esc_attr( $acato_email_essentials_placeholder ); ?>"
												value="<?php print esc_attr( $acato_email_essentials_config[ $acato_email_essentials_post_type ][ $acato_email_essentials_moderator_recipient ][ $acato_email_essentials_moderator_key ] ); ?>"
												id="key-<?php print esc_attr( $acato_email_essentials_post_type ); ?>-<?php print esc_attr( $acato_email_essentials_moderator_recipient ); ?>-<?php print esc_attr( $acato_email_essentials_moderator_key ); ?>"/>
										</td>
									</tr>
									<?php
								}
							}
						}
						?>
					</table>
				</div>
			</div>
		</div>
	</form>
</div>
