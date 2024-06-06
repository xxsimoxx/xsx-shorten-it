<?php

/**
 * -----------------------------------------------------------------------------
 * Plugin Name: Shorten It
 * Description: Create short link for your posts, your affiliates or your social content.
 * Version: 1.0.1
 * Requires PHP: 7.4
 * Requires CP: 2.0
 * Author: Simone Fioravanti
 * Author URI: https://software.gieffeedizioni.it
 * Plugin URI: https://software.gieffeedizioni.it
 * Text Domain: xsx-short-it
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\ShortenIt;

require_once 'vendor/autoload.php';
use splitbrain\phpQRCode\QRCode;

class ShortenIt {

	private $options = false;
	private $screen  = '';
	const SLUG       = 'xsx-shorten-it';

	public function __construct() {
		add_action('template_redirect',     [$this, 'maybe_redirect'], 0);
		add_action('admin_menu',            [$this, 'create_settings_menu'], 100);
		add_action('admin_enqueue_scripts', [$this, 'scripts']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
	}

	private function load_options() {
		if ($this->options !== false) {
			return;
		}
		$options = get_option('xsx_short_it', false);
		if ($options !== false) {
			$this->options = $options;
			return;
		}

		$this->options = [
			'ver'		  => '001',
			'paths'       => [],
		];
		$this->save_options();
	}

	private function save_options() {
		update_option('xsx_short_it', $this->options);
	}

	public function create_settings_menu() {
		$this->screen = add_menu_page(
			esc_html__('Shorten It', 'xsx-short-it'),
			esc_html__('Shorten It', 'xsx-short-it'),
			'manage_options',
			self::SLUG,
			[$this, 'render_menu'],
			'dashicons-admin-links'
		);

		add_action('load-'.$this->screen, [$this, 'delete_action']);
		add_action('load-'.$this->screen, [$this, 'new_action']);
		add_action('load-'.$this->screen, [$this, 'zero_action']);
		add_action('load-'.$this->screen, [$this, 'qr_action']);
		add_action('load-'.$this->screen, [$this, 'help']);
	}

	public function help() {
		$general_content = wp_kses(
			__(
				'<b>Path</b> is relative to your ClassicPress installation.<br>
				<b>Destination</b> is the destination URL that path redirects to.<br>
				<b>Redirect code</b> is the HTTP code used to redirect user to destination.<br>
				<b>Hits</b> stores the count of how many users clicked on the shortened URL (some misconfigured URLs can lead to an incorrect count).<br>
				If you have posts or pages with the same URL of a short link, the redirect will anyway take place.',
				'xsx-short-it'
			),
			[
				'b'  => [],
				'br' => [],
			]
		);

		/* Translators: %1$s is the destination, %2$s is the concatenation of site URL and path. */
		$example_content = sprintf (
			__('Path: /fbv<br>
			Destination: %1$s<br>
			means that if you connect to <i>%2$s</i> you will be redirected to <i>%1$s</i>.', 'xsx-short-it'),
			'https://www.facebook.com/cris.vardamak/videos/1327994840668572',
			get_bloginfo('url').'/fbv',
		);
		$example_content = wp_kses($example_content, ['br' => [], 'i' => []]);

		$conflicts_content = wp_kses(
			__(
				'If redirects are not working properly there may be a conflict with plugins using <code>template_redirect</code> hook.<br>
				This hook is used often by SEO plugins to redirect non existing pages.<br>
				Take a look at your SEO plugin\'s settings.',
				'xsx-short-it'
			),
			[
				'code'  => [],
				'br' => [],
			]
		);

		$screen = get_current_screen();
		$screen->add_help_tab(
			[
				'id'	  => 'xsi_help_tab_general',
				'title'	  => esc_html__('Usage', 'xsx-short-it'),
				'content' => '<p>'.$general_content.'</p>',
			]
		);
		$screen->add_help_tab(
			[
				'id'	  => 'xsi_help_tab_example',
				'title'	  => esc_html__('Example', 'xsx-short-it'),
				'content' => '<p>'.$example_content.'</p>',
			]
		);
		$screen->add_help_tab(
			[
				'id'	  => 'xsi_help_tab_seo',
				'title'	  => esc_html__('Conflicts', 'xsx-short-it'),
				'content' => '<p>'.$conflicts_content.'</p>',
			]
		);
	}

	public function render_menu () {

		echo '<div class="wrap">';
		$this->display_notices();
		echo '<div class="xsi xsi-general">';
		echo '<h1>'.esc_html__('Short It', 'xsx-short-it').'</h1>';
		echo '<p>'.esc_html__('Create short link for your posts, your affiliates or your social content.', 'xsx-short-it').'<br>';
		echo esc_html__('Generate QR codes that point to your links.', 'xsx-short-it').'</p>';
		echo esc_html__('Keep track of how many times those links are used.', 'xsx-short-it').'</p>';
		echo '<a href="#" onClick="xsi_help();">'.esc_html__('More instructions', 'xsx-short-it').'</a>.</p></div>';

		echo '<div class="xsi xsi-keys">';
		$this->load_options();

		$ListTable = new ShortItListTable();
		$ListTable->load_items($this->options['paths']);
		$ListTable->prepare_items();
		$ListTable->display();

		echo '<a name="xsi-form">';
		echo '<form action="'.esc_url_raw(add_query_arg(['action' => 'new'], admin_url('admin.php?page='.self::SLUG))).'" method="POST">';
		wp_nonce_field('new', '_xsi');
		echo '<table class="form-table"><tr><td>';
		echo '<label for="new_path">'.esc_html__('Path: ', 'xsx-short-it').'</label></td>';
		echo '<td><input type="text" size="90" name="new_path" id="new_path"></input></td></tr>';
		echo '<tr><td><label for="new_dest">'.esc_html__('Destination: ', 'xsx-short-it').'</label></td>';
		echo '<td><input type="text" size="90" name="new_dest" id="new_dest"></input></td></tr>';
		echo '<tr><td><label for="new_code">'.esc_html__('Redirect type: ', 'xsx-short-it').'</label></td>';
		echo '<td><select name="new_code" id="new_code">';
		echo '	<option value="302">302 - Temporary</option>';
		echo '	<option value="307">307 - Temporary</option>';
		echo '	<option value="301">301 - Permanent</option>';
		echo '	<option value="308">308 - Permanent</option>';
		echo '</select></td></tr></table>';
		echo '<input type="submit" class="button button-primary" id="submit_button" value="'.esc_html__('New short link', 'xsx-short-it').'"></input> ';
		echo '<input type="button" class="button" onclick="xsi_cancel();" id="cancel_button" style="visibility: hidden;" value="'.esc_html__('Cancel', 'xsx-short-it').'"></input>';
		echo '</form>';
		echo '</div>';

		echo '</div>';

	}

	public function scripts($hook) {
		if ($hook !== $this->screen) {
			return;
		}
		wp_enqueue_script(self::SLUG.'-js', plugin_dir_url(__FILE__).'js/shorten-it-settings.js', [], '1.0.0');
		wp_localize_script(
			self::SLUG.'-js',
			'xsiWords',
			[
				'save' => esc_html__('New short link', 'xsx-short-it'),
				'edit' => esc_html__('Edit short link', 'xsx-short-it'),
			]
		);
	}

	private function add_notice($message, $failure = false) {
		$other_notices = get_transient('xsx_short_it_notices');
		$notice = $other_notices === false ? '' : $other_notices;
		$failure_style = $failure ? 'notice-error' : 'notice-success';
		$notice .= '<div class="notice '.$failure_style.' is-dismissible">';
		$notice .= '    <p>'.wp_kses($message, ['br' => [], 'i' => [],]).'</p>';
		$notice .= '</div>';
		set_transient('xsx_short_it_notices', $notice, \HOUR_IN_SECONDS);
	}

	private function display_notices() {
		$notices = get_transient('xsx_short_it_notices');
		if ($notices === false) {
			return;
		}
		// This contains html formatted from 'add_notice' function that uses 'wp_kses'.
		echo $notices; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		delete_transient('xsx_short_it_notices');
	}

	public function new_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'new') {
			return;
		}
		if (!check_admin_referer('new', '_xsi')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		$missing = [];
		if (!isset($_REQUEST['new_path']) || $_REQUEST['new_path'] === '') {
			$missing[] = esc_html__('path', 'xsx-short-it');
		}
		if (!isset($_REQUEST['new_dest']) || $_REQUEST['new_dest'] === '') {
			$missing[] = esc_html__('destination', 'xsx-short-it');
		}
		if (!isset($_REQUEST['new_code']) || !in_array((int) $_REQUEST['new_code'], [301, 302, 307, 308])) {
			$missing[] = esc_html__('valid redirect type', 'xsx-short-it');
		}

		$path = trim(sanitize_text_field(wp_unslash($_REQUEST['new_path'])), '/');

		$dest = esc_url_raw(wp_unslash($_REQUEST['new_dest']));
		$code = (int) sanitize_text_field(wp_unslash($_REQUEST['new_code']));

		if ($dest !== wp_unslash($_REQUEST['new_dest'])) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$missing[] = esc_html__('valid destination', 'xsx-short-it');
		}
		if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
			$missing[] = esc_html__('valid path', 'xsx-short-it');
		}
		$path = preg_replace('#[^A-Za-z0-9/\-\._]#', '', $path);
		if ($missing !== []) {
			$error = sprintf(esc_html__('Missing %s.', 'xsx-short-it'), implode(', ', $missing));
			$this->add_notice($error, true);
			$sendback = remove_query_arg(['action', 'new_note', '_xuc'], wp_get_referer());
			wp_safe_redirect($sendback);
			exit;
		}

		$this->load_options();

		$save_count = array_key_exists($path, $this->options['paths']) ? $this->options['paths'][$path]['hits'] : 0;

		$this->options['paths'][$path] = [
				'dest'  => $dest,
				'code' => $code,
				'hits' => $save_count,
			];

		$this->save_options();
		$this->add_notice(esc_html__('New short URL generated.', 'xsx-short-it'), false);

		$sendback = remove_query_arg(['action', 'new_note', '_xuc'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	public function delete_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'delete') {
			return;
		}
		if (!check_admin_referer('delete', '_xsi')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['path'])) {
			return;
		}

		$this->load_options();

		$path = sanitize_text_field(wp_unslash($_REQUEST['path']));
		unset($this->options['paths'][$path]);

		$this->save_options();
		$this->add_notice(esc_html__('Path deleted.', 'xsx-short-it').'<br><i>'.$path.'</i>', false);

		$sendback = remove_query_arg(['action', 'path', '_xsi'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	public function zero_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'zero') {
			return;
		}
		if (!check_admin_referer('zero', '_xsi')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['path'])) {
			return;
		}

		$this->load_options();

		$path = sanitize_text_field(wp_unslash($_REQUEST['path']));
		$this->options['paths'][$path]['hits'] = 0;

		$this->save_options();
		$this->add_notice(esc_html__('Hits cleared.', 'xsx-short-it').'<br><i>'.$path.'</i>', false);

		$sendback = remove_query_arg(['action', 'path', '_xsi'], wp_get_referer());
		wp_safe_redirect($sendback);
		exit;

	}

	public function qr_action() {

		if (!isset($_GET['action'])) {
			return;
		}
		if ($_GET['action'] !== 'qr') {
			return;
		}
		if (!check_admin_referer('qr', '_xsi')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		if (!isset($_REQUEST['path'])) {
			return;
		}

		$path = sanitize_text_field(wp_unslash($_REQUEST['path']));
		$url = get_bloginfo('url').(str_starts_with($path, '/') ? '' : '/').$path;

		$qr = QRCode::svg($url);
		ob_start();
		header('Content-type: image/svg+xml');
		header('Content-Disposition: attachment; filename='.str_replace('/', '-', $path).'.svg');
		header('Content-Length: '.strlen($qr));
		echo $qr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	public function maybe_redirect() {
		if (defined('WP_CLI') && WP_CLI) {
			return;
		}

		$this->load_options();

		$path = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
		$path = trim($path, '/');

		if (!array_key_exists($path, $this->options['paths'])) {
			return;
		}

		$this->options['paths'][$path]['hits']++;
		$this->save_options();

		wp_redirect($this->options['paths'][$path]['dest'], (int) $this->options['paths'][$path]['code']); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit();
	}

	public static function uninstall() {
		delete_option('xsx_short_it');
	}

}

new ShortenIt;

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class ShortItListTable extends \WP_List_Table {

	// Contains the data to be rendered, as we want this to be passed from another class.
	private $paths = [];

	// Load list items, as we want this to be passed from another class.
	public function load_items($paths) {
		$this->paths = $paths;
	}

	// Output columns definition.
	public function get_columns() {
		return [
			'path' => esc_html__('Path', 'xsx-short-it'),
			'dest' => esc_html__('Destination', 'xsx-short-it'),
			'code' => esc_html__('Redirect code', 'xsx-short-it'),
			'hits' => esc_html__('Hits', 'xsx-short-it'),
		];
	}

	// Just output the column.
	public function column_default($item, $column_name) {
		return $item[$column_name];
	}

	// For "path" column add row actions.
	public function column_path($item) {
		$url = esc_url_raw(get_bloginfo('url').(str_starts_with($item['path'], '/') ? '' : '/').$item['path']);
		$actions = [
			'delete' => '<a href="'.wp_nonce_url(add_query_arg(['action' => 'delete', 'path' => $item['path']]), 'delete', '_xsi').'">'.esc_html__('Delete', 'xsx-short-it').'</a>',
			'edit'   => '<a href="#xsi-form" onclick="xsi_mod(\''.$item['path'].'\', \''.$item['dest'].'\', \''.$item['code'].'\');">'.esc_html__('Edit', 'xsx-short-it').'</a>',
			'reset'  => '<a href="'.wp_nonce_url(add_query_arg(['action' => 'zero', 'path' => $item['path']]), 'zero', '_xsi').'">'.esc_html__('Reset count', 'xsx-short-it').'</a>',
			'copy'   => '<a href="#" onclick="xsi_copy(\''.$url.'\')">'.esc_html__('Copy URL to clipboard', 'xsx-short-it').'</a>',
			'qr'     => '<a href="'.wp_nonce_url(add_query_arg(['action' => 'qr', 'path' => $item['path']]), 'qr', '_xsi').'">'.esc_html__('Download QR', 'xsx-short-it').'</a>',
		];
		$key = '<span class="row-title">'.$item['path'].'</span>';
		return sprintf('%1$s %2$s', $key, $this->row_actions($actions));
	}

	// For "destination" column link it.
	public function column_dest($item) {
		return '<a href="'.$item['dest'].'" target="_blank">'.$item['dest'].'</a>';
	}

	// Prepare our columns and insert data.
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = [];
		$this->_column_headers = [$columns, $hidden, $sortable];
		$data = [];
		foreach ($this->paths as $path => $options) {
			$data[] = [
				'path' => $path,
				'dest' => $options['dest'],
				'code' => $options['code'],
				'hits' => $options['hits'],
			];
		}
		$this->items = $data;
	}

}
