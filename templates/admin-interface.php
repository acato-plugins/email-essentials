<?php
/**
 * View: admin interface.
 *
 * @package Acato_Email_Essentials
 */

namespace Acato\Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}

$acato_email_essentials_config = Plugin::get_config();
if ( empty( $acato_email_essentials_config['dkimfolder'] ) ) {
	$acato_email_essentials_config['dkimfolder'] = '';
}
if ( empty( $acato_email_essentials_config['certfolder'] ) ) {
	$acato_email_essentials_config['certfolder'] = '';
}
$acato_email_essentials_host             = Plugin::get_hostname_by_blogurl();
$acato_email_essentials_smime_identities = [];
$acato_email_essentials_dkim_identities  = [];

?>
<div class="wrap wpes-wrap wpes-settings">
	<?php
	Plugin::template_header( __( 'Email Configuration', 'email-essentials' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}
	?>

	<form id="outpost" class="wpes-admin" method='POST' action="" enctype="multipart/form-data">
		<input type="hidden" name="form_id" value="acato-email-essentials"/>
		<?php wp_nonce_field( 'acato-email-essentials--settings', 'wpes-nonce' ); ?>

		<div class="wpes-tools">
			<div class="wpes-tools--box">
				<input
					type="submit" name="op"
					value="<?php esc_attr_e( 'Save settings', 'email-essentials' ); ?>"
					class="button-primary action"/>
				<a class="button action" href="#email-test" style="text-align: center">
					<?php esc_html_e( 'Send sample mail', 'email-essentials' ); ?>
				</a>
			</div>

			<div class="wpes-tools--box__toc">
				<strong>
					<?php print wp_kses_post( __( 'Jump to', 'email-essentials' ) ); ?>
				</strong>

				<?php
				$acato_email_essentials_blocks = [
					'basic-information'                   => _x( 'Basic information', 'Item in jump-to list', 'email-essentials' ),
					'how-to-validate-sender'              => _x( 'How to validate sender?', 'Item in jump-to list', 'email-essentials' ),
					'what-to-do-in-case-sender-not-valid' => _x( 'What to do in case the sender is not valid for this domain?', 'Item in jump-to list', 'email-essentials' ),
					'email-history'                       => _x( 'Email History', 'Item in jump-to list', 'email-essentials' ),
					'email-queue'                         => _x( 'Email Throttling', 'Item in jump-to list', 'email-essentials' ),
					'email-settings'                      => _x( 'Email Settings', 'Item in jump-to list', 'email-essentials' ),
					'email-content'                       => _x( 'Email content', 'Item in jump-to list', 'email-essentials' ),
					'content-charset-recoding'            => _x( 'Content charset re-coding', 'Item in jump-to list', 'email-essentials' ),
					'content-handling'                    => _x( 'Content handling', 'Item in jump-to list', 'email-essentials' ),
					'digital-smime'                       => _x( 'Digital Email Signing (S/MIME)', 'Item in jump-to list', 'email-essentials' ),
					'digital-dkim'                        => _x( 'Digital Email Signing (DKIM)', 'Item in jump-to list', 'email-essentials' ),
					'email-styling-filters'               => _x( 'Email styling, and filters for HTML head/body', 'Item in jump-to list', 'email-essentials' ),
					'email-preview'                       => _x( 'Example Email', 'Item in jump-to list', 'email-essentials' ),
				];
				?>
				<ul class="toc">
					<?php foreach ( $acato_email_essentials_blocks as $acato_email_essentials_block_id => $acato_email_essentials_block_name ) { ?>
						<li>
							<a href="#<?php echo esc_attr( $acato_email_essentials_block_id ); ?>">
								<?php echo esc_html( $acato_email_essentials_block_name ); ?>
							</a>
						</li>
					<?php } ?>
				</ul>
				<strong>
					<?php print wp_kses_post( __( 'Related settings', 'email-essentials' ) ); ?>
				</strong>
				<ul class="toc">
					<?php
					$acato_email_essentials_urls = [
						__( 'Blog settings', 'email-essentials' )      => admin_url( 'options-general.php' ),
						__( 'Site settings', 'email-essentials' )      => is_multisite() ? network_admin_url( 'settings.php' ) : false,
						__( 'Alternative Admins', 'email-essentials' ) => add_query_arg( 'page', 'acato-email-essentials/admins', admin_url( 'admin.php' ) ),
					];
					$acato_email_essentials_urls = array_filter( $acato_email_essentials_urls );
					foreach ( $acato_email_essentials_urls as $acato_email_essentials_url_name => $acato_email_essentials_url ) {
						print '<li><a target="_blank" href="' . esc_attr( $acato_email_essentials_url ) . '">' . esc_html( $acato_email_essentials_url_name ) . '</a></li>';
					}
					?>
				</ul>
			</div>
		</div>

		<div id="poststuff">
			<div id="basic-information" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Basic information', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<div class="wpes-notice--info">
								<?php
								// translators: %s: hostname.
								print wp_kses_post( sprintf( __( 'Out of the box, WordPress will use name "WordPress" and email "wordpress@%s" as default sender. This is far from optimal. Your first step is therefore to set an appropriate name and email address.', 'email-essentials' ), $acato_email_essentials_host ) );
								?>
							</div>
							<label
								for="from-name"><?php print wp_kses_post( __( 'Default from name', 'email-essentials' ) ); ?></label>
							<input
								type="text"
								name="settings[from_name]"
								value="<?php print esc_attr( $acato_email_essentials_config['from_name'] ); ?>"
								placeholder="WordPress"
								id="from-name"/>
						</div>
						<div class="wpes-form-item">
							<label for="from-email">
								<?php print wp_kses_post( __( 'Default from email', 'email-essentials' ) ); ?>
							</label>
							<input
								type="text" name="settings[from_email]"
								value="<?php print esc_attr( $acato_email_essentials_config['from_email'] ); ?>"
								placeholder="wordpress@<?php print esc_attr( $acato_email_essentials_host ); ?>"
								id="from-email"/>
							<div
								class="wpes-notice--error on-regexp-test"
								data-regexp="(no-?reply)@"
								data-field="from-email">
								<?php print wp_kses_post( __( 'Under GDPR, from May 25th, 2018, using a no-reply@ (or any variation of a not-responded-to email address) is prohibited. Please make sure the default sender address is valid and used in the setting below.', 'email-essentials' ) ); ?>
							</div>
						</div>

						<?php
						if ( $acato_email_essentials_config['spf_lookup_enabled'] ) {
							// SPF match.
							$acato_email_essentials_spf_result = Plugin::i_am_allowed_to_send_in_name_of( $acato_email_essentials_config['from_email'] );
							if ( ! $acato_email_essentials_spf_result ) {
								?>
								<div class="wpes-notice--error">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Records are checked', 'email-essentials' ) ); ?>
									</strong>

									<p>
										<?php print wp_kses_post( __( 'you are NOT allowed to send mail with this domain.', 'email-essentials' ) ); ?>
										<br/>
										<?php print wp_kses_post( __( 'If you really need to use this sender email address, you need to change the SPF record to include the sending-IP of this server', 'email-essentials' ) ); ?>
									</p>

									<table class="wpes-info-table">
										<tr>
											<th>
												<?php print wp_kses_post( __( 'Old', 'email-essentials' ) ); ?>
											</th>
											<td>
												<code><?php print wp_kses_post( Plugin::get_spf( $acato_email_essentials_config['from_email'], false, true ) ); ?></code>
											</td>
										</tr>
										<tr>
											<th>
												<?php print wp_kses_post( __( 'New', 'email-essentials' ) ); ?>
											</th>
											<td>
												<code><?php print wp_kses_post( Plugin::get_spf( $acato_email_essentials_config['from_email'], true, true ) ); ?></code>
											</td>
										</tr>
									</table>
								</div>
							<?php } else { ?>
								<div class="wpes-notice--info">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Records are checked', 'email-essentials' ) ); ?>
									</strong>

									<p>
										<?php print wp_kses_post( __( 'You are allowed to send mail with this domain.', 'email-essentials' ) ); ?>
									</p>
								</div>
								<div class="wpes-notice--info">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Record', 'email-essentials' ) ); ?>
									</strong>

									<p>
										<code>
											<?php print wp_kses_post( Plugin::get_spf( $acato_email_essentials_config['from_email'], false, true ) ); ?>
										</code>
									</p>
								</div>
								<?php
							}
							?>
							<div class="wpes-notice--info">
								<strong class="title">
									<?php
									print wp_kses_post( __( 'Sending IP', 'email-essentials' ) );
									?>
								</strong>
								<p>
									<code>
										<?php
										print wp_kses_post( Plugin::get_sending_ip() );
										?>
									</code>
								</p>
								<strong class="title">
									<?php
									print wp_kses_post( __( 'Matches', 'email-essentials' ) );
									?>
								</strong>
								<p>
									<code>
										<?php
										print $acato_email_essentials_spf_result ? wp_kses_post( $acato_email_essentials_spf_result ) : esc_html__( 'Nothing ;( - This IP is not found in any part of the SPF.', 'email-essentials' );
										?>
									</code>
								</p>
							</div>
							<?php
						} elseif ( ! Plugin::i_am_allowed_to_send_in_name_of( $acato_email_essentials_config['from_email'] ) ) {
							// domain match.
							?>
							<div class="wpes-notice--error">
								<strong class="title">
									<?php
									print wp_kses_post( __( 'You are NOT allowed to send mail with this domain; it should match the domainname of the website.', 'email-essentials' ) );
									?>
								</strong>

								<p>
									<?php
									print wp_kses_post( __( 'If you really need to use this sender email address, you need to switch to SPF-record checking and make sure the SPF for this domain matches this server.', 'email-essentials' ) );
									?>
								</p>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>


			<div id="email-settings" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Email Settings', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( isset( $acato_email_essentials_config['smtp'] ) && $acato_email_essentials_config['smtp'] ); ?>
							type="checkbox" name="settings[smtp-enabled]"
							value="1"
							id="smtp-enabled"/>
						<label for="smtp-enabled">
							<?php print wp_kses_post( __( 'Enable sending mail over SMTP?', 'email-essentials' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Using an SMTP improves reliability, helps reducing the chance of your emails being marked as spam and gives the option to use an external mail service like MailJet, MailGun, SparkPost etc.', 'email-essentials' ) ); ?>
					</div>

					<div class="wpes-form on-smtp-enabled">
						<div class="wpes-form-item">
							<label for="smtp-hostname">
								<?php print wp_kses_post( __( 'Hostname or -ip', 'email-essentials' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[host]"
								value="<?php print esc_attr( $acato_email_essentials_config['smtp'] ? $acato_email_essentials_config['smtp']['host'] : '' ); ?>"
								id="smtp-hostname"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-port">
								<?php print wp_kses_post( __( 'SMTP Port', 'email-essentials' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[port]"
								value="<?php print esc_attr( $acato_email_essentials_config['smtp'] ? $acato_email_essentials_config['smtp']['port'] : '' ); ?>"
								id="smtp-port"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-username">
								<?php print wp_kses_post( __( 'Username', 'email-essentials' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[username]"
								value="<?php print esc_attr( $acato_email_essentials_config['smtp'] ? $acato_email_essentials_config['smtp']['username'] : '' ); ?>"
								id="smtp-username"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-password">
								<?php print wp_kses_post( __( 'Password', 'email-essentials' ) ); ?>
							</label>
							<input
								type="password"
								name="settings[password]"
								value="<?php print esc_attr( $acato_email_essentials_config['smtp'] ? str_repeat( '*', strlen( $acato_email_essentials_config['smtp']['password'] ) ) : '' ); ?>"
								id="smtp-password"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-secure">
								<?php print wp_kses_post( __( 'Use encrypted connection?', 'email-essentials' ) ); ?>
							</label>
							<select name="settings[secure]" id="smtp-secure">
								<option value="" data-smtp-port="25">
									<?php print wp_kses_post( __( 'No', 'email-essentials' ) ); ?>
								</option>
								<option disabled>
									───────────────────────
								</option>
								<option disabled>
									<?php print wp_kses_post( __( 'Use encrypted connection', 'email-essentials' ) ); ?>
									- <?php print wp_kses_post( __( 'strict SSL verify', 'email-essentials' ) ); ?>
								</option>
								<option
									data-smtp-port="465"
									value="ssl" <?php selected( $acato_email_essentials_config['smtp'] && 'ssl' === $acato_email_essentials_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'SSL', 'email-essentials' ) ); ?>
								</option>
								<option
									data-smtp-port="587"
									value="tls" <?php selected( $acato_email_essentials_config['smtp'] && 'tls' === $acato_email_essentials_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'StartTLS', 'email-essentials' ) ); ?>
								</option>
								<option disabled>
									───────────────────────
								</option>
								<option disabled>
									<?php print wp_kses_post( __( 'Use encrypted connection', 'email-essentials' ) ); ?>
									- <?php print wp_kses_post( __( 'allow self-signed SSL', 'email-essentials' ) ); ?>
								</option>
								<option
									data-smtp-port="465"
									value="ssl-" <?php selected( $acato_email_essentials_config['smtp'] && 'ssl-' === $acato_email_essentials_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'SSL', 'email-essentials' ) ); ?>
								</option>
								<option
									data-smtp-port="587"
									value="tls-" <?php selected( $acato_email_essentials_config['smtp'] && 'tls-' === $acato_email_essentials_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'StartTLS', 'email-essentials' ) ); ?>
								</option>
							</select>
						</div>
						<script>
							jQuery(document).ready(function () {
								const smtpPort = document.getElementById('smtp-port');
								const smtpSecure = document.getElementById('smtp-secure');
								smtpSecure.addEventListener('change', function () {
									smtpPort.placeholder = this.options[this.selectedIndex].dataset.smtpPort;
								});
								smtpSecure.dispatchEvent(new Event('change'));
							});
						</script>
						<div class="wpes-form-item">
							<label for="timeout">
								<?php print wp_kses_post( __( 'phpMailer Timeout', 'email-essentials' ) ); ?>
							</label>
							<select id="timeout" name="settings[timeout]">
								<?php
								$acato_email_essentials_timeouts = [
									60  => __( '1 minute', 'email-essentials' ),
									300 => __( '5 minutes (default)', 'email-essentials' ),
									600 => __( '10 minutes (for very slow hosts)', 'email-essentials' ),
								];
								if ( ! isset( $acato_email_essentials_config['timeout'] ) || ! $acato_email_essentials_config['timeout'] ) {
									$acato_email_essentials_config['timeout'] = 300;
								}
								foreach ( $acato_email_essentials_timeouts as $acato_email_essentials_key => $acato_email_essentials_val ) {
									print '<option value="' . esc_attr( $acato_email_essentials_key ) . '" ' . selected( (int) $acato_email_essentials_config['timeout'], $acato_email_essentials_key, false ) . '>' . esc_html( $acato_email_essentials_val ) . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="wpes-radio-list">
						<input
							<?php checked( isset( $acato_email_essentials_config['SingleTo'] ) && $acato_email_essentials_config['SingleTo'] ); ?>
							type="checkbox"
							name="settings[SingleTo]"
							value="1"
							id="smtp-singleto"/>
						<label for="smtp-singleto">
							<?php print wp_kses_post( __( 'Split mail with more than one Recipient into separate mails?', 'email-essentials' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Sending an email to multiple recipients is often regarded as spamming, using this option will send individual emails and reduces the chance of the email being rejected.', 'email-essentials' ) ); ?>
					</div>
				</div>
			</div>

			<div id="how-to-validate-sender" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'How to validate sender?', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<p>
						<em>
							<?php print wp_kses_post( __( 'You have 2 options', 'email-essentials' ) ); ?>:
						</em>
					</p>

					<div class="wpes-radio-list">
						<input
							<?php checked( ! isset( $acato_email_essentials_config['spf_lookup_enabled'] ) || ! $acato_email_essentials_config['spf_lookup_enabled'] ); ?>
							type="radio"
							name="settings[spf_lookup_enabled]"
							value="0"
							id="spf_lookup_enabled_0"/>
						<label for="spf_lookup_enabled_0">
							<?php print wp_kses_post( '<b>' . __( 'Domain name', 'email-essentials' ) . '</b>:<br />' . __( 'Use a simple match on hostname; any email address that matches the base domainname of this website is considered valid.', 'email-essentials' ) ); ?>
						</label>

						<input
							<?php checked( isset( $acato_email_essentials_config['spf_lookup_enabled'] ) && $acato_email_essentials_config['spf_lookup_enabled'] ); ?>
							type="radio" name="settings[spf_lookup_enabled]" value="1"
							id="spf_lookup_enabled_1"/>
						<label for="spf_lookup_enabled_1">
							<?php print wp_kses_post( '<b>' . __( 'SPF records', 'email-essentials' ) . '</b>:<br />' . __( 'Use SPF records to validate the sender. If the SPF record of the domain of the email address used as sender matches the IP-address this website is hosted on, the email address is considered valid.', 'email-essentials' ) ); ?>
						</label>

					</div>
					<div
						class="wpes-notice--warning"><?php echo esc_html__( 'Please note that using SPF can fail if the sender (admin email) is on the same server and thus allowed as sender by SPF. You might want to use the SPF-method just to verify the server set-up, then switch back to domain verification.', 'email-essentials' ); ?></div>
				</div>
			</div>

			<div id="what-to-do-in-case-sender-not-valid" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'What to do in case the sender is not valid for this domain?', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<strong class="title">
							<?php print wp_kses_post( __( 'Fix sender-address?', 'email-essentials' ) ); ?>
						</strong>

						<p>
							<?php print wp_kses_post( __( 'Emails sent as different domain will probably be marked as spam. Use the options here to fix the sender-address to always match the sending domain.', 'email-essentials' ) ); ?>
							<br/>
							<?php print wp_kses_post( __( 'The actual sender of the email will be used as <code>Reply-To</code>; you can still use the Reply button in your email application to send a reply easily.', 'email-essentials' ) ); ?>
						</p>
					</div>

					<p>
						<strong>
							<?php print esc_html( _x( 'When the sender email address', 'start of a sentence, will be suffixed with ...', 'email-essentials' ) ); ?>
							...
						</strong>
					</p>

					<div class="wpes-radio-list arrow-down">
						<input
							<?php checked( 'when_sender_invalid', $acato_email_essentials_config['make_from_valid_when'] ); ?>
							id="wpes-settings-make_from_valid_when-when_sender_invalid"
							type="radio"
							name="settings[make_from_valid_when]"
							value="when_sender_invalid"/>
						<label
							class="on-regexp-test"
							data-field="spf_lookup_enabled_0"
							data-regexp="0"
							for="wpes-settings-make_from_valid_when-when_sender_invalid">...
							<?php print wp_kses_post( _x( 'is not on the website domain', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'email-essentials' ) ); ?>
							;
						</label>
						<label
							class="on-regexp-test"
							data-field="spf_lookup_enabled_1"
							data-regexp="1"
							for="wpes-settings-make_from_valid_when-when_sender_invalid">...
							<?php print wp_kses_post( _x( 'is not allowed by SPF from this website', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'email-essentials' ) ); ?>
							;
						</label>

						<input
							<?php checked( 'when_sender_not_as_set', $acato_email_essentials_config['make_from_valid_when'] ); ?>
							id="wpes-settings-make_from_valid_when-when_sender_not_as_set"
							type="radio"
							name="settings[make_from_valid_when]"
							value="when_sender_not_as_set"/>
						<label for="wpes-settings-make_from_valid_when-when_sender_not_as_set">...
							<?php print wp_kses_post( _x( 'is not the "Default from email" as set above', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'email-essentials' ) ); ?>
							;
						</label>
					</div>

					<div>
						<label for="make_from_valid"></label>
						<select class="widefat" name="settings[make_from_valid]" id="make_from_valid">
							<option
								value=""><?php print wp_kses_post( __( 'Keep the possibly-invalid sender as is. (might cause your mails to be marked as spam!)', 'email-essentials' ) ); ?></option>
							<option disabled>────────────────────────────────────────────────────────────</option>
							<option
								value="-at-" <?php selected( '-at-', $acato_email_essentials_config['make_from_valid'] ); ?>>
								<?php
								// translators: %s: the hostname of the website.
								print esc_html( sprintf( __( 'Rewrite email@addre.ss to email-at-addre-dot-ss@%s', 'email-essentials' ), $acato_email_essentials_host ) );
								?>
							</option>
							<option
								value="noreply" <?php selected( 'noreply', $acato_email_essentials_config['make_from_valid'] ); ?>>
								<?php
								// translators: %s: the hostname of the website.
								print esc_html( sprintf( __( 'Rewrite email@addre.ss to noreply@%s', 'email-essentials' ), $acato_email_essentials_host ) );
								print esc_html( __( '(Not GDPR Compliant)', 'email-essentials' ) );
								?>
							</option>
							<?php
							$acato_email_essentials_default_sender_mail = Plugin::wp_mail_from( $acato_email_essentials_config['from_email'] );
							if ( Plugin::i_am_allowed_to_send_in_name_of( $acato_email_essentials_default_sender_mail ) ) {
								?>
								<option
									value="default" <?php selected( 'default', $acato_email_essentials_config['make_from_valid'] ); ?>>
									<?php
									// translators: %s: the default sender email address.
									print esc_html( sprintf( __( 'Rewrite email@addre.ss to %s', 'email-essentials' ), $acato_email_essentials_default_sender_mail ) );
									?>
								</option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<div id="email-history" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Email History', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( $acato_email_essentials_config['enable_history'] ); ?>
							type="checkbox" name="settings[enable_history]"
							value="1"
							id="enable_history"/>
						<label for="enable_history">
							<?php print wp_kses_post( __( 'Enable Email History', 'email-essentials' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--warning on-enable_history">
						<?php print wp_kses_post( __( '<strong class="title">Warning</strong> Storing emails in your database is a BAD idea and illegal in most countries. Use this for DEBUGGING only!', 'email-essentials' ) ); ?>
						<br/>
						<?php print wp_kses_post( __( 'Enabling the history feature will also add a tracker to all outgoing emails to check receipt.', 'email-essentials' ) ); ?>
						<br/>
						<?php print wp_kses_post( __( 'Disabling this feature will delete the email history database tables.', 'email-essentials' ) ); ?>
						<br/>
						<strong class="warning">
							<?php print wp_kses_post( __( 'If you insist on storing emails, please note that you need to implement the appropriate protocols for compliance with GDPR. The responsibility lies with the owner of the website, not the creator or hosting company.', 'email-essentials' ) ); ?>
						</strong>
					</div>
				</div>
			</div>

			<div id="email-queue" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Email Throttling', 'email-essentials' ) ); ?> <em
							class="beta"><?php esc_html_e( 'Beta feature', 'email-essentials' ); ?>
							- <?php esc_html_e( 'It works but not without flaws.', 'email-essentials' ); ?></em>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( $acato_email_essentials_config['enable_queue'] ); ?>
							type="checkbox" name="settings[enable_queue]"
							value="1"
							id="enable_queue"/>
						<label for="enable_queue">
							<?php print wp_kses_post( __( 'Enable Email Throttling', 'email-essentials' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--warning on-enable_queue">
						<?php
						print wp_kses_post( __( 'Enabling the throttling feature will prevent sending large amounts of emails in quick succession, for example a spam-run.', 'email-essentials' ) );
						?>
						<br/>
						<?php
						// translators: %1$d: the maximum number of emails per time window, %2$d: the time window in seconds.
						print wp_kses_post( sprintf( __( 'Once activated, when more than %1$d emails are sent within %2$d seconds from the same IP-address, all other emails will be held until released.', 'email-essentials' ), Queue::get_max_count_per_time_window(), Queue::get_time_window() ) );
						?>
						<br/>
						<?php
						// translators: %d: the number of emails per minute.
						print wp_kses_post( sprintf( __( 'Emails will be sent in batches of %d per minute, the trigger is a hit on the website, the admin panel or the cron (wp-cron.php).', 'email-essentials' ), Queue::get_batch_size() ) );
						?>
					</div>
				</div>
			</div>

			<div id="email-content" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Email content', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="smtp-is_html">
								<?php print wp_kses_post( __( 'Send as HTML?', 'email-essentials' ) ); ?>
							</label>
							<input
								<?php checked( isset( $acato_email_essentials_config['is_html'] ) && $acato_email_essentials_config['is_html'] ); ?>
								type="checkbox"
								name="settings[is_html]"
								value="1"
								id="smtp-is_html"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'This will convert non-html body to html-ish body', 'email-essentials' ) ); ?>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-css_inliner">
								<?php print wp_kses_post( __( 'Convert CSS to Inline Styles', 'email-essentials' ) ); ?>
							</label>
							<input
								<?php checked( isset( $acato_email_essentials_config['css_inliner'] ) && $acato_email_essentials_config['css_inliner'] ); ?>
								type="checkbox"
								name="settings[css_inliner]"
								value="1"
								id="smtp-css_inliner"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'Works for Outlook Online, Yahoo Mail, Google Mail, Hotmail, etc.', 'email-essentials' ) ); ?>
						</div>
					</div>
				</div>
			</div>

			<div id="content-charset-recoding" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Content charset re-coding', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Some servers have f*cked-up content-encoding settings, resulting in wrongly encoded diacritics. If you expect a character like &eacute; and all you get is something like &euro;&tilde;&Itilde;, experiment with this setting.', 'email-essentials' ) ); ?>
					</div>

					<label for="content-precoding"></label><select
						id="content-precoding" name="settings[content_precode]">
						<?php
						$acato_email_essentials_encoding_table         = explode( ',', '0,auto,' . Plugin::ENCODINGS );
						$acato_email_essentials_encoding_table         = array_combine( $acato_email_essentials_encoding_table, $acato_email_essentials_encoding_table );
						$acato_email_essentials_encoding_table         = array_map(
							function ( $item ) {
								// translators: %s: a content-encoding, like UTF-8.
								return sprintf( _x( 'From: %s', 'E.g.: From: UTF-8', 'email-essentials' ), strtoupper( $item ) );
							},
							$acato_email_essentials_encoding_table
						);
						$acato_email_essentials_encoding_table['0']    = __( 'No charset re-coding (default)', 'email-essentials' );
						$acato_email_essentials_encoding_table['auto'] = __( 'Autodetect with mb_check_encoding()', 'email-essentials' );
						foreach ( $acato_email_essentials_encoding_table as $acato_email_essentials_encoding => $acato_email_essentials_nice_encoding ) {
							print '<option value="' . esc_attr( $acato_email_essentials_encoding ) . '" ' . selected( $acato_email_essentials_config['content_precode'], $acato_email_essentials_encoding, false ) . '>' . esc_html( $acato_email_essentials_nice_encoding ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div id="content-handling" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php esc_html_e( 'Content handling', 'email-essentials' ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="smtp-alt_body">
								<?php print wp_kses_post( __( 'Derive plain-text alternative?', 'email-essentials' ) ); ?>
							</label>
							<input
								<?php checked( isset( $acato_email_essentials_config['alt_body'] ) && $acato_email_essentials_config['alt_body'] ); ?>
								type="checkbox"
								name="settings[alt_body]"
								value="1"
								id="smtp-alt_body"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'This will derive text-ish body from html body as AltBody', 'email-essentials' ) ); ?>
						</div>
						<div class="wpes-form-item">
							<label for="do_shortcodes">
								<?php print wp_kses_post( __( 'Process the body with <code>do_shortcode()</code>', 'email-essentials' ) ); ?>
							</label>
							<input
								<?php checked( isset( $acato_email_essentials_config['do_shortcodes'] ) && $acato_email_essentials_config['do_shortcodes'] ); ?>
								type="checkbox"
								name="settings[do_shortcodes]"
								value="1"
								id="do_shortcodes"/>
						</div>
					</div>
				</div>
			</div>

			<?php if ( function_exists( 'openssl_pkcs7_sign' ) ) { ?>
				<div id="digital-smime" class="postbox">
					<div class="postbox-header">
						<h2>
							<?php print wp_kses_post( __( 'Digital Email Signing (S/MIME)', 'email-essentials' ) ); ?>
						</h2>
					</div>
					<div class="inside">
						<div class="wpes-form">
							<div class="wpes-form-item">
								<label for="enable-smime">
									<?php print wp_kses_post( __( 'Sign emails with S/MIME certificate', 'email-essentials' ) ); ?>
								</label>
								<input
									<?php checked( isset( $acato_email_essentials_config['enable_smime'] ) && $acato_email_essentials_config['enable_smime'] ); ?>
									type="checkbox"
									name="settings[enable_smime]"
									value="1"
									id="enable-smime"/>
							</div>

							<div class="wpes-form-item on-enable-smime">
								<label for="certfolder">
									<?php print wp_kses_post( __( 'S/MIME Certificate/Private-Key path', 'email-essentials' ) ); ?>
								</label>
								<input
									type="text"
									name="settings[certfolder]"
									value="<?php print esc_attr( $acato_email_essentials_config['certfolder'] ); ?>"
									id="certfolder"/>
							</div>

							<?php
							if ( Plugin::path_is_in_web_root( $acato_email_essentials_config['certificate_folder'] ) ) {
								?>
								<div class="wpes-notice--error on-enable-smime">
									<strong class="title">
										<?php
										// translators: %s: a path.
										print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'email-essentials' ), Plugin::suggested_safe_path_for( '.smime' ) ) );
										?>
									</strong>
								</div>
								<?php
							}
							?>

							<?php
							if ( isset( $acato_email_essentials_config['certfolder'] ) ) {
								$acato_email_essentials_smime_certificate_folder = $acato_email_essentials_config['certificate_folder'];
								if ( is_dir( $acato_email_essentials_smime_certificate_folder ) ) {
									$acato_email_essentials_smime_identities = Plugin::list_smime_identities();
									$acato_email_essentials_smime_identities = array_keys( $acato_email_essentials_smime_identities );
									?>
								<?php } else { ?>
									<div class="wpes-notice--error on-enable-smime">
										<strong class="title">
											<?php
											// translators: %s: a path.
											print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'email-essentials' ), $acato_email_essentials_config['certfolder'] ) );
											if ( $acato_email_essentials_smime_certificate_folder !== $acato_email_essentials_config['certfolder'] ) {
												// translators: %s: a path.
												print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'email-essentials' ), $acato_email_essentials_smime_certificate_folder ) );
											}
											// translators: %s: a path.
											print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'email-essentials' ), realpath( $acato_email_essentials_smime_certificate_folder ) ) );
											?>
										</strong>
									</div>
								<?php } ?>
							<?php } ?>

							<div class="wpes-notice--info on-enable-smime">
								<p>
									<?php
									print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress installation).', 'email-essentials' ) ) . '<br />';
									print wp_kses_post( __( 'The file-naming convention is', 'email-essentials' ) ) . ':<br />';
									?>
								</p>

								<label
									for="smime-sample-email"><?php esc_html_e( 'Type an email address to see the correct filenames', 'email-essentials' ); ?></label>
								<input type="email" id="smime-sample-email" class="smime-sample-email">
								<script>
									jQuery(document).ready(function ($) {
										$('#smime-sample-email').on('input', function () {
											var email = $(this).val();
											$('.smime-sample-email-output').each(function () {
												var pattern = $(this).data('pattern');
												var output = $(this).data('default');
												if (email) {
													output = email.replace(/[^a-z0-9-_.@]/gi, '_');
												}
												output = pattern.replace('?', output);
												$(this).text(output);
											});
										}).on('keypress', function (e) {
											if (e.which === 13 || e.which === 10) {
												e.preventDefault();
											}
										});
									});
								</script>

								<table class="wpes-info-table">
									<tr>
										<th>
											<?php print wp_kses_post( __( 'certificate', 'email-essentials' ) ); ?>
										</th>
										<td>
											<code
												class="smime-sample-email-output" data-pattern="?.crt"
												data-default="email@addre.ss">email@addre.ss.crt</code>
										</td>
									</tr>
									<tr>
										<th>
											<?php print wp_kses_post( __( 'private key', 'email-essentials' ) ); ?>
										</th>
										<td>
											<code
												class="smime-sample-email-output" data-pattern="?.key"
												data-default="email@addre.ss">email@addre.ss.key</code>
										</td>
									</tr>
									<tr>
										<th>
											<?php print wp_kses_post( __( '(optional) passphrase', 'email-essentials' ) ); ?>
										</th>
										<td>
											<code
												class="smime-sample-email-output" data-pattern="?.pass"
												data-default="email@addre.ss">email@addre.ss.pass</code>
										</td>
									</tr>
								</table>

								<?php if ( isset( $acato_email_essentials_config['certfolder'] ) ) { ?>
									<?php if ( $acato_email_essentials_smime_identities ) { ?>
										<div class="wpes-notice--info on-enable-smime">
											<p>
												<?php
												// translators: %s: a list of S/MIME identities.
												print wp_kses_post( sprintf( __( 'Found S/MIME identities for the following senders: <code>%s</code>', 'email-essentials' ), implode( '</code>, <code>', $acato_email_essentials_smime_identities ) ) );
												?>
											</p>
										</div>
									<?php } ?>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			<?php } else { ?>
				<input type="hidden" name="settings[enable_smime]" value="0"/>
			<?php } ?>

			<div id="digital-dkim" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Digital Email Signing (DKIM)', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="enable-dkim">
								<?php print wp_kses_post( __( 'Sign emails with DKIM certificate', 'email-essentials' ) ); ?>
							</label>
							<input
								<?php checked( isset( $acato_email_essentials_config['enable_dkim'] ) && $acato_email_essentials_config['enable_dkim'] ); ?>
								type="checkbox"
								name="settings[enable_dkim]"
								value="1"
								id="enable-dkim"/>
						</div>
						<div class="wpes-form-item on-enable-dkim">
							<label for="dkimfolder">
								<?php print wp_kses_post( __( 'DKIM Certificate/Private-Key path', 'email-essentials' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[dkimfolder]"
								value="<?php print esc_attr( $acato_email_essentials_config['dkimfolder'] ); ?>"
								id="dkimfolder"/>
						</div>

						<?php if ( Plugin::path_is_in_web_root( $acato_email_essentials_config['dkim_certificate_folder'] ) ) { ?>
							<div class="wpes-notice--error on-enable-dkim">
								<strong class="title">
									<?php
									// translators: %s: a path.
									print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'email-essentials' ), Plugin::suggested_safe_path_for( '.dkim' ) ) );
									?>
								</strong>
							</div>
						<?php } ?>

						<?php
						if ( isset( $acato_email_essentials_config['dkimfolder'] ) ) {
							$acato_email_essentials_dkim_certificate_folder = $acato_email_essentials_config['dkim_certificate_folder'];
							if ( is_dir( $acato_email_essentials_dkim_certificate_folder ) ) {
								$acato_email_essentials_dkim_identities = Plugin::list_dkim_identities();
								$acato_email_essentials_dkim_identities = array_keys( $acato_email_essentials_dkim_identities );
							} else {
								?>
								<div class="wpes-notice--error on-enable-dkim">
									<strong class="title">
										<?php
										// translators: %s: a path.
										print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'email-essentials' ), $acato_email_essentials_config['dkimfolder'] ) );
										if ( $acato_email_essentials_dkim_certificate_folder !== $acato_email_essentials_config['dkimfolder'] ) {
											// translators: %s: a path.
											print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'email-essentials' ), $acato_email_essentials_dkim_certificate_folder ) );
										}
										// translators: %s: a path.
										print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'email-essentials' ), realpath( $acato_email_essentials_dkim_certificate_folder ) ) );
										?>
									</strong>
								</div>
								<?php
							}
						}
						?>

						<div class="wpes-notice--info on-enable-dkim">
							<p>
								<?php print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress installation).', 'email-essentials' ) ); ?>
								<br/>
								<?php print wp_kses_post( __( 'The file-naming convention is', 'email-essentials' ) ); ?>
							</p>


							<label
								for="dkim-sample-domain"><?php esc_html_e( 'Type a domain to see the correct filenames and records', 'email-essentials' ); ?></label>
							<input type="text" id="dkim-sample-domain" class="dkim-sample-input">
							<label
								for="dkim-sample-domainkey"><?php esc_html_e( 'Type the desired domainkey to see the correct DNS records', 'email-essentials' ); ?></label>
							<input type="text" id="dkim-sample-domainkey" class="dkim-sample-input">
							<label
								for="dkim-sample-password"><?php esc_html_e( 'Type a sample password to see the correct scripts', 'email-essentials' ); ?></label>
							<input type="text" id="dkim-sample-password" class="dkim-sample-input">

							<table class="wpes-info-table">
								<tr>
									<th>
										<?php print wp_kses_post( __( 'certificate', 'email-essentials' ) ); ?>
									</th>
									<td>
										<code
											class="dkim-sample-output preload">domain.tld.crt</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( 'private key', 'email-essentials' ) ); ?>
									</th>
									<td>
										<code
											class="dkim-sample-output preload">domain.tld.key</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( 'DKIM Selector', 'email-essentials' ) ); ?>
									</th>
									<td>
										<code
											class="dkim-sample-output preload">domain.tld.selector</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( '(optional) passphrase', 'email-essentials' ) ); ?>
									</th>
									<td>
										<code
											class="dkim-sample-output preload">domain.tld.pass</code>
									</td>
								</tr>
							</table>

							<strong class="title">
								<?php print wp_kses_post( __( 'To generate DKIM keys, use', 'email-essentials' ) ); ?>
							</strong>

							<?php
							print wp_kses_post( '<code class="dkim-sample-output preload">openssl genrsa -aes256 -passout pass:"' . _x( 'YOUR-PASSWORD', 'A sample password', 'email-essentials' ) . '" -out domain.tld.key 2048</code>' );
							print wp_kses_post( '<code class="dkim-sample-output preload">openssl rsa -in domain.tld.key -pubout > domain.tld.crt</code>' );
							print wp_kses_post( '<code class="dkim-sample-output preload">echo "' . _x( 'YOUR-PASSWORD', 'A sample password', 'email-essentials' ) . '" > domain.tld.pass</code>' );
							print wp_kses_post( '<code class="dkim-sample-output preload">echo "' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'email-essentials' ) . '" > domain.tld.selector</code>' );
							print wp_kses_post( __( 'Upload these files to the specified path on the server and again; this should not be publicly queryable!!!', 'email-essentials' ) );
							?>
							<script>
								// items with class preload will generate pattern data on the fly
								var preload_data = [
									"domain.tld",
									<?php print wp_json_encode( _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'email-essentials' ) ); ?>,
									<?php print wp_json_encode( _x( 'YOUR-PASSWORD', 'A sample password', 'email-essentials' ) ); ?>
								], sample_data = [], sample_data_key_numbers = {
									domain: 0,
									domainkey: 1,
									password: 2
								};
								jQuery(document).ready(function ($) {
									// Set-up the preload data
									$('.preload').each(function () {
										var $this = $(this);
										var pattern = $this.text();
										$.each(preload_data, function (key, value) {
											sample_data[key] = value;
											pattern = pattern.replace(new RegExp(value, 'g'), '?' + key);
											$this.data("default" + (key + 1), value);
										});
										$this.data('pattern', pattern);
									}).removeClass('preload');

									$('.dkim-sample-input').on('input', function () {
										var sample_data_key = $(this).attr('id').replace('dkim-sample-', '');
										sample_data_key = sample_data_key_numbers[sample_data_key];
										sample_data[sample_data_key] = $(this).val();
										if (!sample_data[sample_data_key]) {
											sample_data[sample_data_key] = preload_data[sample_data_key];
										}

										$('.dkim-sample-output').each(function () {
											var output = $(this).data('pattern');
											sample_data.forEach(function (value, key) {
												output = output.replace(new RegExp('\\?' + key, 'g'), value);
											});
											$(this).text(output);
										});
									}).on('keypress', function (e) {
										if (e.which === 13 || e.which === 10) {
											e.preventDefault();
										}
									});
								});
							</script>
							<strong class="title">
								<?php esc_html_e( 'Finally, register the domain key in the DNS', 'email-essentials' ); ?>
							</strong>

							<?php print wp_kses_post( '<code class="dkim-sample-output preload" data-default1="" data-default2="" data-default3="" data-pattern="">' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'email-essentials' ) . '._domainkey.domain.tld. IN TXT "v=DKIM1; k=rsa; p=' . _x( 'CONTENT-OF', 'A tag that tells the user to get the content of a file', 'email-essentials' ) . '-domain.tld.crt"</code>' ); ?>

							<?php
							// translators: %1$s and %2$s are sample content lines to be removed from the key.
							print esc_html( sprintf( __( 'Remove the lines "%1$s" and "%2$s" and place the rest of the content on a single line.', 'email-essentials' ), '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----' ) );
							?>

							<p>
								<?php
								// translators: %s: a URL: to a testing site.
								print wp_kses_post( sprintf( __( 'Test your settings with <a href="%s" target="_blank">DMARC Analyser</a> (unaffiliated)', 'email-essentials' ), esc_attr( 'https://www.dmarcanalyzer.com/dkim/dkim-check/' ) ) );
								?>
							</p>
						</div>
						<?php
						if ( isset( $acato_email_essentials_config['dkimfolder'] ) && $acato_email_essentials_dkim_identities ) {
							?>
							<div class="wpes-notice--info on-enable-dkim">
								<p>
									<?php
									// translators: %s: a list of domains.
									print wp_kses_post( sprintf( __( 'Found DKIM certificates for the following sender-domains: %s', 'email-essentials' ), '<code>' . implode( '</code>, <code>', $acato_email_essentials_dkim_identities ) . '</code>' ) );
									?>
								</p>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>

			<div id="email-styling-filters" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Email styling, and filters for HTML head/body', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info on-smtp-is_html">
						<p>
							<?php print wp_kses_post( __( 'You can use WordPress filters to augment the HEAD and BODY sections of the HTML email.', 'email-essentials' ) ); ?>
						</p>
					</div>

					<div class="wpes-notice--info not-smtp-is_html">
						<p>
							<?php print wp_kses_post( __( 'You can use WordPress filters to change the email.', 'email-essentials' ) ); ?>
						</p>
					</div>

					<table class="wpes-info-table">
						<tr>
							<th><?php esc_html_e( 'Purpose', 'email-essentials' ); ?></th>
							<th><?php esc_html_e( 'WordPress filter', 'email-essentials' ); ?></th>
							<th colspan="2"><?php esc_html_e( 'Parameters', 'email-essentials' ); ?></th class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Plugin defaults', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_defaults</code></td>
							<td colspan="2"><code>array $defaults</code></td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Plugin settings', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_settings</code></td>
							<td colspan="2"><code>array $settings</code></td class=last>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Email subject', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_subject</code></td>
							<td colspan="2"><code>string $subject</code>, <code>PHPMailer $mailer</code></td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Email <head>', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_head</code></td>
							<td colspan="2"><code>string $head_content</code>, <code>PHPMailer $mailer</code>
							</td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Email <body>', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_body</code></td>
							<td colspan="2"><code>string $body_content</code>, <code>PHPMailer $mailer</code>
							</td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Email CSS styles', 'email-essentials' ); ?></td>
							<td><code>acato_email_essentials_css</code></td>
							<td colspan="2"><code>string $css</code>, <code>PHPMailer $mailer</code>
							</td class=last>
						</tr>
						<tr class="not-smtp-is_html">
							<td colspan="4" class="last">
								<?php print wp_kses_post( __( 'Turn on HTML email to enable email styling.', 'email-essentials' ) ); ?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div id="email-preview" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( Plugin::get_config()['is_html'] ? __( 'Example Email (actual HTML) - with your filters applied', 'email-essentials' ) : __( 'Example Email', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<iframe
						class="email-preview"
						src="<?php print esc_attr( add_query_arg( 'iframe', 'content' ) ); ?>"></iframe>
				</div>
			</div>

			<div id="email-test" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Send Test Email', 'email-essentials' ) ); ?>
					</h2>
				</div>
				<?php
				$acato_email_essentials_blog_admin       = get_option( 'admin_email', false );
				$acato_email_essentials_site_admin       = is_multisite() ? get_site_option( 'admin_email', false ) : false;
				$acato_email_essentials_sample_email     = [
					'to'      => $acato_email_essentials_blog_admin,
					'subject' => Plugin::dummy_subject(),
				];
				$acato_email_essentials_sample_email     = Plugin::alternative_to( $acato_email_essentials_sample_email );
				$acato_email_essentials_configured_email = reset( $acato_email_essentials_sample_email['to'] );
				$acato_email_essentials_configured_email = Plugin::rfc_decode( $acato_email_essentials_configured_email )['email'] ?? false;

				$acato_email_essentials_recipients = [
					'The blog administrator: ' . $acato_email_essentials_blog_admin => $acato_email_essentials_blog_admin,
					'The site administrator: ' . $acato_email_essentials_site_admin => $acato_email_essentials_site_admin,
				];
				$acato_email_essentials_recipients = array_filter( $acato_email_essentials_recipients );
				if ( ! in_array( $acato_email_essentials_configured_email, $acato_email_essentials_recipients, true ) ) {
					$acato_email_essentials_recipients[ 'Configured recipient: ' . $acato_email_essentials_configured_email ] = $acato_email_essentials_configured_email;
				}

				$acato_email_essentials_last_test_sent_to = get_option( 'acato_email_essentials_last_test_to', reset( $acato_email_essentials_recipients ) );

				$acato_email_essentials_senders = [];
				// Add 'default' as set in the settings.
				$acato_email_essentials_senders[ 'Default from email: ' . $acato_email_essentials_config['from_email'] ] = $acato_email_essentials_config['from_email'];
				// Add the others, as determined at 'To'.
				$acato_email_essentials_senders = array_merge( $acato_email_essentials_senders, $acato_email_essentials_recipients );
				// Check the last one used.
				$acato_email_essentials_last_test_sent_from = get_option( 'acato_email_essentials_last_test_from', reset( $acato_email_essentials_recipients ) );
				?>
				<div class="inside cols">
					<div class="inside col">
						<strong><?php esc_html_e( 'Send test-email from:', 'email-essentials' ); ?></strong>
						<?php
						foreach ( $acato_email_essentials_senders as $acato_email_essentials_sender_name => $acato_email_essentials_sender_email ) {
							$acato_email_essentials_checked = $acato_email_essentials_last_test_sent_from === $acato_email_essentials_sender_email ? 'checked' : '';
							print '<label><input type="radio" name="send-test-email-from" value="' . esc_attr( $acato_email_essentials_sender_email ) . '" ' . esc_attr( $acato_email_essentials_checked ) . '/>' . esc_html( $acato_email_essentials_sender_name ) . '</label>';
						}
						?>
						<em><?php esc_html_e( 'This setting will allow you to test the sender-replacement setting. Depending on your settings above, the chosen address will either be the actual sender-address, or the reply-to address.', 'email-essentials' ); ?></em>
					</div>
					<div class="inside col">
						<strong><?php esc_html_e( 'Send test-email to:', 'email-essentials' ); ?></strong>
						<?php
						foreach ( $acato_email_essentials_recipients as $acato_email_essentials_recipient_name => $acato_email_essentials_recipient_email ) {
							$acato_email_essentials_checked = $acato_email_essentials_last_test_sent_to === $acato_email_essentials_recipient_email ? 'checked' : '';
							print '<label><input type="radio" name="send-test-email-to" value="' . esc_attr( $acato_email_essentials_recipient_email ) . '" ' . esc_attr( $acato_email_essentials_checked ) . '/>' . esc_html( $acato_email_essentials_recipient_name ) . '</label>';
						}
						?>
						<em><?php esc_html_e( 'This setting will allow you to test the "Alternative Admins" settings.', 'email-essentials' ); ?></em>
					</div>
				</div>
				<div class="inside">
					<input
						type="submit" name="op"
						value="<?php print esc_attr__( 'Send sample mail', 'email-essentials' ); ?>"
						class="button-secondary action"/>
				</div>

				<?php if ( Plugin::$debug ) { ?>
					<div id="wpes-debug-info" class="wpes-notice--info inside">
						<pre><?php print wp_kses_post( Plugin::$debug ); ?></pre>
					</div>
					<script>
						// Scroll to the debug info panel.
						document.getElementById('wpes-debug-info').scrollIntoView({
							behavior: 'smooth'
						});
					</script>
				<?php } ?>
			</div>
	</form>
</div>
