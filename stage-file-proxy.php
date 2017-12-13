<?php
/**
 * Plugin Name:     Stage File Proxy
 * Plugin URI:      https://github.com/drubage/stage-file-proxy
 * Description:     The easiest way to handle the uploads folder for Wordpress during development. This plugin will automatically download files from the production site uploads folder on demand. This plugin IS NOT meant for use on production websites.
 * Author:          Drew Michael
 * Author URI:      https://fruition.net/
 * Text Domain:     stage-file-proxy
 * Version:         0.1.0
 *
 * @package         Stage_File_Proxy
 */

add_action( 'admin_menu', 'stage_file_proxy_menu' );
function stage_file_proxy_menu() {
  add_options_page( __('Stage File Proxy Options', 'textdomain' ), __('Stage File Proxy Options', 'textdomain' ), 'manage_options', 'stage-file-proxy', 'stage_file_proxy_options_page' );
}
add_action( 'admin_init', 'stage_file_proxy_init' );

function stage_file_proxy_init() {

  /*
	 * http://codex.wordpress.org/Function_Reference/register_setting
	 * register_setting( $option_group, $option_name, $sanitize_callback );
	 * The second argument ($option_name) is the option name. It’s the one we use with functions like get_option() and update_option()
	 * */
  # With input validation:
  register_setting( 'stage-file-proxy-group', 'stage-file-proxy-settings', 'stage_file_proxy_settings_validate_and_sanitize' );

  /*
* http://codex.wordpress.org/Function_Reference/add_settings_section
* add_settings_section( $id, $title, $callback, $page );
* */
  add_settings_section( 'section-1', __( 'Source Domain', 'textdomain' ), 'section_1_callback', 'stage-file-proxy' );

  /*
 * http://codex.wordpress.org/Function_Reference/add_settings_field
 * add_settings_field( $id, $title, $callback, $page, $section, $args );
 * */
  add_settings_field( 'field-1-1', __( 'Source Domain', 'textdomain' ), 'source_domain_callback', 'stage-file-proxy', 'section-1' );
  add_settings_field( 'field-1-2', __( 'Method', 'select' ), 'method_callback', 'stage-file-proxy', 'section-1' );

}
/*
 * THE ACTUAL PAGE
 * */
function stage_file_proxy_options_page() {
  ?>
    <div class="wrap">
        <h2><?php _e('Stage File Proxy Options', 'textdomain'); ?></h2>
        <form action="options.php" method="POST">
          <?php settings_fields('stage-file-proxy-group'); ?>
          <?php do_settings_sections('stage-file-proxy'); ?>
          <?php submit_button(); ?>
        </form>
    </div>
<?php }
/*
* THE SECTIONS
* Hint: You can omit using add_settings_field() and instead
* directly put the input fields into the sections.
* */
function section_1_callback() {
  _e( 'Please enter the domain where the source images are location (for example, http://www.fruition.net).', 'textdomain' );
}
/*
* THE FIELDS
* */
function source_domain_callback() {

  $settings = (array) get_option( 'stage-file-proxy-settings' );
  $field = "source_domain";
  $value = esc_attr( $settings[$field] );

  echo "<input type='text' name='stage-file-proxy-settings[$field]' value='$value' />";
}

function method_callback() {

  $settings = (array) get_option( 'stage-file-proxy-settings' );
  $field = "method";
  $value = esc_attr( $settings[$field] );

  echo '<select name="stage-file-proxy-settings[method]">';
  echo '<option value="download"' . ($value == 'download' ? ' selected="selected"' : '') . '>Download</option>';
  echo '<option value="redirect"' . ($value == 'redirect' ? ' selected="selected"' : '') . '>Redirect</option>';
  echo '</select>';

}
/*
* INPUT VALIDATION:
* */
function stage_file_proxy_settings_validate_and_sanitize( $input ) {

  $settings = (array) get_option( 'stage-file-proxy-settings' );

  if ( filter_var($input['source_domain'], FILTER_VALIDATE_URL) !== FALSE ) {
    $output['source_domain'] = trim($input['source_domain']);
  } else {
    add_settings_error( 'stage-file-proxy-settings', 'invalid-source_domain', 'Please enter a valid domain.' );
  }
  $output['method'] = trim($input['method']);

  return $output;
}

function stage_file_proxy_404(){
  if( is_404() && !isset($_REQUEST['stage_file_proxy'])) {
    $settings = (array) get_option( 'stage-file-proxy-settings' );
    if (isset($settings['source_domain']) && $settings['source_domain'] <> '') {
      if (substr($settings['source_domain'], -1) == '/') {
        $settings['source_domain'] = substr($settings['source_domain'], 0, -1);
      }
      $source = $settings['source_domain'] . $_SERVER['REQUEST_URI'];

      if ($settings['method'] == 'redirect') {
        header("Location: " . $source);
        exit;
      }

      $parts = explode('/', $_SERVER['REQUEST_URI']);
      $post_date = "{$parts[3]}-{$parts[4]}-01";

      if ($file = file_get_contents($source)) {
        $upload = wp_upload_bits(basename($_SERVER['REQUEST_URI']), NULL, $file, $post_date);
        if ( wp_redirect( $upload['url'] . '?stage_file_proxy=true' ) ) {
          exit;
        }
      }
    }
  }
}
add_action( 'template_redirect', 'stage_file_proxy_404' );