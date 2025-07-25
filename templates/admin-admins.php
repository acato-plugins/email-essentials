<?php
/**
 * View: alternative admins.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}
global $current_user;
$wpes_config          = get_option( 'mail_key_admins', [] );
$wpes_mail_keys       = Plugin::mail_key_database();
$wpes_wordpress_admin = get_option( 'admin_email' );
?>
<div class="wrap wpes-wrap wpes-admins">
	<?php
	Plugin::template_header( __( 'Alternative Admins', 'email-essentials' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	?>

	<form id="outpost" class="wpes-admin" method='POST' action="">
		<input type="hidden" name="form_id" value="wpes-admins"/>
		<?php wp_nonce_field( 'wp-email-essentials--admins', 'wpes-nonce' ); ?>

		<div class="wpes-tools">
			<div class="wpes-tools--box">
				<input
					type="submit" name="op" value="<?php print  esc_attr__( 'Save settings', 'email-essentials' ); ?>"
					class="button-primary action"/>
			</div>
		</div>

		<div id="poststuff">
			<div class="postbox">
				<div class="postbox-header">
					<h2>
						<?php
						// translators: %s: the admin email address.
						print wp_kses_post( sprintf( __( 'Outgoing emails to the site-administrator ( %s )', 'email-essentials' ), $wpes_wordpress_admin ) );
						?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<p>
							<?php print wp_kses_post( __( 'Emails to the site administrator are often better off with a different person or department.', 'email-essentials' ) ); ?>
							<?php print wp_kses_post( __( 'Here you can assign a different recipient for a specific set of emails sent by WordPress to the administrator.', 'email-essentials' ) ); ?>
						</p>
					</div>
				</div>
				<div class="inside">
					<table class="wpes-info-table equal">
						<tr>
							<th><?php esc_html_e( 'Email sent in the following situations', 'email-essentials' ); ?></th>
							<th><?php esc_html_e( 'Instead of the site administrator, send these emails to', 'email-essentials' ); ?></th>
						</tr>
						<?php
						foreach ( $wpes_mail_keys as $wpes_mail_key => $wpes_mail_key_description ) {
							if ( ! isset( $wpes_config[ $wpes_mail_key ] ) ) {
								$wpes_config[ $wpes_mail_key ] = '';
							}
							?>
							<tr>
								<td>
									<label
										for="key-<?php print esc_attr( $wpes_mail_key ); ?>"><?php print esc_html( $wpes_mail_key_description ?: $wpes_mail_key ); ?></label>
								</td>
								<td>
									<input
										type="text"
										name="settings[keys][<?php print esc_attr( $wpes_mail_key ); ?>]"
										class="widefat"
										placeholder="<?php print esc_attr( $wpes_wordpress_admin ); ?>"
										value="<?php print esc_attr( $wpes_config[ $wpes_mail_key ] ); ?>"
										id="key-<?php print esc_attr( $wpes_mail_key ); ?>"/>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>

			<div class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'RegExp', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<p>
							<?php
							// translators: %1$s: the regexp barrier "/" .
							print wp_kses_post( sprintf( __( 'You must include the boundaries, so start with %1$s and end with %1$s.', 'email-essentials' ), '<code>/</code>' ) );
							?>
							<br/>
							<?php
							// translators: %1$s: the flag for case-insensitive matching, %2$s: the example of a regular expression.
							print wp_kses_post( sprintf( __( 'You can add the %1$s flag to create a case-insensitive match (like so: %2$s).', 'email-essentials' ), '<code>i</code>', '<code>/some[expression]/i</code>' ) );
							?>
							<br/>
							<?php print wp_kses_post( __( 'If you are unfamiliar with regular expressions, you can ignore this section, ask for help or learn the magic and power of regular expressions yourself.', 'email-essentials' ) ); ?>
						</p>
					</div>

					<table class="wpes-info-table equal">
						<tr>
							<th><?php esc_html_e( 'Emails sent with subject matching the following regular expression', 'email-essentials' ); ?></th>
							<th><?php esc_html_e( 'Instead of the site administrator, send these emails to', 'email-essentials' ); ?></th>
						</tr>

						<?php
						$wpes_loop_iterator_0 = 0;
						$wpes_regexp_list     = get_option( 'mail_key_list', [] );
						foreach ( $wpes_regexp_list as $wpes_regexp => $wpes_mail_key ) {
							?>
							<tr>
								<td style="width: 50%">
									<input
										type="text"
										name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][regexp]"
										class="a-regexp widefat"
										value="<?php print esc_attr( $wpes_regexp ); ?>"/>
								</td>
								<td>
									<input
										type="text"
										name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][key]"
										class="widefat"
										value="<?php print esc_attr( $wpes_mail_key ); ?>"/>
								</td>
							</tr>
							<?php
							$wpes_loop_iterator_0++;
						}
						?>
						<?php for ( $wpes_loop_iterator_1 = 0; $wpes_loop_iterator_1 < 5; $wpes_loop_iterator_1++ ) { ?>
							<tr>
								<td>
									<input
										type="text"
										name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_1 + $wpes_loop_iterator_0 ); ?>][regexp]"
										class="a-regexp widefat"
										value=""/>
								</td>
								<td>
									<input
										type="text" class="widefat"
										name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_1 + $wpes_loop_iterator_0 ); ?>][key]"
										value=""/>
								</td>
							</tr>
						<?php } ?>
					</table>

					<?php
					$wpes_missed_subjects = get_option( 'mail_key_fails', [] );
					$wpes_missed_subjects = array_filter(
						$wpes_missed_subjects,
						function ( $item ) {
							return ! Plugin::mail_subject_match( $item ) && ! Plugin::get_mail_key( $item );
						}
					);
					update_option( 'mail_key_fails', array_values( $wpes_missed_subjects ) );

					if ( ! empty( $wpes_missed_subjects ) ) {
						?>
						<div class="wpes-notice--info">
							<strong class="title">
								<?php esc_html_e( 'Unmatched subjects', 'email-essentials' ); ?>
							</strong>

							<p>
								<?php print wp_kses_post( __( 'This is a list of email subjects of emails that have been sent to the site administrator.', 'email-essentials' ) ); ?>
								<?php print wp_kses_post( __( 'You can use the table above to input regular expressions for emails that should have gone to an alternative email address.', 'email-essentials' ) ); ?>
							</p>
						</div>

						<?php foreach ( $wpes_missed_subjects as $wpes_missed_subject ) { ?>
							<code class="a-fail"><?php print esc_html( $wpes_missed_subject ); ?></code>
						<?php } ?>
					<?php } else { ?>
						<div class="wpes-notice--success">
							<p>
								<?php print wp_kses_post( __( 'There are currently no email subjects that have not been matched.', 'email-essentials' ) ); ?>
							</p>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>

	</form>
</div>

