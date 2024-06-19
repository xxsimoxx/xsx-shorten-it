<?php

namespace XXSimoXX\ShortenIt;

trait Helpers
{

	function before_action_checks($action) {
		if (!isset($_GET['action'])) {
			return false;
		}
		if ($_GET['action'] !== $action) {
			return false;
		}
		if (!check_admin_referer($action, '_xsi')) {
			return false;
		}
		if (!current_user_can('manage_options')) {
			return false;
		}
		return true;
	}


	function add_notice($transient, $message, $failure = false) {
		$other_notices = get_transient($transient);
		$notice = $other_notices === false ? '' : $other_notices;
		$failure_style = $failure ? 'notice-error' : 'notice-success';
		$notice .= '<div class="notice '.$failure_style.' is-dismissible">';
		$notice .= '    <p>'.wp_kses($message, ['br' => [], 'i' => [],]).'</p>';
		$notice .= '</div>';
		set_transient($transient, $notice, \HOUR_IN_SECONDS);
	}

	function display_notices($transient) {
		$notices = get_transient($transient);
		if ($notices === false) {
			return;
		}
		// This contains html formatted from 'add_notice' function that uses 'wp_kses'.
		echo $notices; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		delete_transient($transient);
	}

}
