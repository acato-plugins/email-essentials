<?php
/**
 * View: email log.
 *
 * @package WP_Email_Essentials
 */

namespace Acato\Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'email-essentials' ) ) );
}

?>
<div class="wrap wpes-wrap wpes-queue">
	<?php
	Plugin::template_header( __( 'Email Queue', 'email-essentials' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	require_once __DIR__ . '/../lib/class-wpes-queue-list-table.php';
	$wpes_queue_list_table = new WPES_Queue_List_Table();

	?>
	<div class="wpes-notice--warning">
		<?php print wp_kses_post( __( 'Enabling the throttling feature will prevent sending large amounts of emails in quick succession, for example a spam-run.', 'email-essentials' ) ); ?>
		<br/>
		<?php
		// translators: 1: max emails, 2: time window in seconds.
		print wp_kses_post( sprintf( __( 'Once activated, when more than %1$d emails are sent within %2$d seconds from the same IP-address, all other emails will be held until released.', 'email-essentials' ), Queue::get_max_count_per_time_window(), Queue::get_time_window() ) );
		?>
		<br/>
		<?php
		// translators: %d: batch size.
		print wp_kses_post( sprintf( __( 'Emails will be sent in batches of %d per minute, the trigger is a hit on the website, the admin panel or the cron (wp-cron.php).', 'email-essentials' ), Queue::get_batch_size() ) );
		?>
		<br/>
		<?php print wp_kses_post( __( 'Emails with high priority will be sent as usual, if you have mission-critical emails, set priority to high using the following header;', 'email-essentials' ) ); ?>
		<code class="inline">X-Priority: 1</code>
	</div>
	<form
		action="<?php print esc_attr( add_query_arg( 'wpes-action', 'form-post' ) ); ?>"
		method="post">
		<?php
		wp_nonce_field( 'acato-email-essentials--queue', 'wpes-nonce' );
		$wpes_queue_list_table->process_bulk_action();
		$wpes_queue_list_table->prepare_items();
		$wpes_queue_list_table->display();
		?>
	</form>
</div>
