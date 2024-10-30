<?php

/*
Plugin Name: Kontrol
Plugin URI: https://dashboard.kontrol.io/documentation/wordpress.jsp
Description: Capture exceptions, uncaught errors, and custom events into your kontrol.io dashboard.
Version: 1.0.2
Author: kontrol.io
Author URI: https://www.kontrol.io/
*/

include dirname( __FILE__ ) . '/library.php';

kontrol_load();
function kontrol_load()
   {
   if(Kontrol::isInitialized()) return;
   $version = get_bloginfo('version');
   $options = get_option('kontrol_options',Array('API_KEY' => '','LOG_LEVEL' => 2,'Enable_ENV' => 'no','ENABLE_RUNTIME' => 'no'));
   if($options['API_KEY'] == '') return;
   Kontrol::initialize($options['API_KEY'],$version);
   Kontrol::setPHPEnvironmentEnabled(isset($options['ENABLE_ENV']) && $options['ENABLE_ENV'] == 'on');
   Kontrol::setRuntimePropertiesEnabled(isset($options['ENABLE_RUNTIME']) && $options['ENABLE_RUNTIME'] == 'on');
   if(!isset($options['LOG_LEVEL'])) $options['LOG_LEVEL'] = 2;

   // Disabling the logging of PHP Notice & PHP Warning
   $options['LOG_LEVEL'] = 2;

   Kontrol::_errorSeverity($options['LOG_LEVEL']);
   }

add_action('wp_enqueue_scripts','kontrol_js_include');
add_action('admin_enqueue_scripts','kontrol_js_include');
function kontrol_js_include()
   {
   wp_enqueue_script('jquery');
   wp_enqueue_script('kontrol',plugins_url('library.js',__FILE__));
   }

add_action('wp_head','kontrol_js_init');
add_action('admin_head','kontrol_js_init');
function kontrol_js_init()
   {
   $version = get_bloginfo('version');
   $options = get_option('kontrol_options',Array('API_KEY' => '','ENABLE_COOKIE' => ''));
   if($options['API_KEY'] == '') return;
   echo "<script type='text/javascript'>";
   echo "Kontrol.initialize('{$options['API_KEY']}','$version');";
   if(isset($options['ENABLE_COOKIE']) && $options['ENABLE_COOKIE'] == 'on') echo "Kontrol.setCookieEnabled(true);";
   echo "</script>";
   }

add_action('admin_menu','kontrol_admin_add_page');
function kontrol_admin_add_page()
   {
   add_options_page('Kontrol Settings','Kontrol','manage_options','kontrol','kontrol_admin_page');
   }
function kontrol_admin_page()
   {
   if(isset($_REQUEST['test2']))
      {
      try { throw new Exception('PHP Test Exception'); } catch(Exception $e) { Kontrol::exception($e); }
      echo '<script type="text/javascript">window.location = "options-general.php?page=kontrol";</script>';
      }
   ?>
<div>
<form action="options.php" method="post">
<?php settings_fields('kontrol_options'); ?>
<?php do_settings_sections('kontrol'); ?>
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>
<br />

<h3>Kontrol Test Events</h3>
<p>Click any button below to execute test events and they will be sent to your kontrol.io dashboard.</p>
<div><form action="options.php" method="post">
<input type="button" value="Click Me" onclick="javascript:test1()" /> JavaScript: Capture An Exception<br /><br />
<input type="button" value="Click Me" onclick="javascript:test2()" /> PHP: Capture An Exception<br /><br />
</form></div>

<script type='text/javascript'>
  function test1() { try { throw new Error('JavaScript Test Exception'); } catch(e) { Kontrol.exception(e); } alert('Done :)'); }
  function test2() { window.location = 'options-general.php?page=kontrol&test2'; }
</script>
   <?php
   }

add_action('admin_init','kontrol_admin_init');
function kontrol_admin_init()
   {
   register_setting('kontrol_options','kontrol_options','kontrol_options_validate');
   add_settings_section('kontrol_main','Kontrol Settings','kontrol_section_main','kontrol');
   add_settings_field('API_KEY','API Key','kontrol_setting_api_key','kontrol','kontrol_main');

   // Disabling the logging of PHP Notice & PHP Warning
   //add_settings_field('LOG_LEVEL','PHP: Log Level','kontrol_setting_log_level','kontrol','kontrol_main');

   add_settings_field('ENABLE_PHP','PHP: Capture General Details','kontrol_setting_enable_php','kontrol','kontrol_main');
   add_settings_field('ENABLE_ENV','PHP: Capture Environment Details','kontrol_setting_enable_env','kontrol','kontrol_main');
   add_settings_field('ENABLE_RUNTIME','PHP: Capture Runtime Properties','kontrol_setting_enable_runtime','kontrol','kontrol_main');
   add_settings_field('ENABLE_JAVASCRIPT','JavaScript: Capture General Details','kontrol_setting_enable_javascript','kontrol','kontrol_main');
   add_settings_field('ENABLE_COOKIE','JavaScript: Capture Cookie Values','kontrol_setting_enable_cookie','kontrol','kontrol_main');
   }

function kontrol_section_main()
   {
   echo '<p>Register at <a target="_blank" href="https://dashboard.kontrol.io">dashboard.kontrol.io</a> for an account and copy your API key here.</p>';
   }

function kontrol_setting_api_key()
   {
   $options = get_option('kontrol_options');
   if(!isset($options['ENABLE_ENV'])) $options['ENABLE_ENV'] = 'off';
   echo "<input id='API_KEY' name='kontrol_options[API_KEY]' size='64' type='text' value='{$options['API_KEY']}' />";
   }

function kontrol_setting_log_level()
   {
   $options = get_option('kontrol_options');
   if(!isset($options['LOG_LEVEL'])) $options['LOG_LEVEL'] = 2;
   echo "<select name='kontrol_options[LOG_LEVEL]' id='LOG_LEVEL'>",
        "<option " , ($options['LOG_LEVEL']==2?'selected':'') , " value='2'>Log Everything : Except PHP Notice & PHP Warning</option>",
        "<option " , ($options['LOG_LEVEL']==1?'selected':'') , " value='1'>Log Everything : Except Notice</option>",
        "<option " , ($options['LOG_LEVEL']==0?'selected':'') , " value='0'>Log Everything</option>",
        "</select>";
   }

function kontrol_setting_enable_php()
   {
   echo "<input name='kontrol_options[ENABLE_PHP]' type='checkbox' id='ENABLE_PHP' disabled checked />";
   }

function kontrol_setting_enable_env()
   {
   $options = get_option('kontrol_options');
   if(!isset($options['ENABLE_ENV'])) $options['ENABLE_ENV'] = 'off';
   echo "<input name='kontrol_options[ENABLE_ENV]' type='checkbox' id='ENABLE_ENV'" . ($options['ENABLE_ENV'] == 'on' ? ' checked' : '') . "/>";
   }

function kontrol_setting_enable_runtime()
   {
   $options = get_option('kontrol_options');
   if(!isset($options['ENABLE_RUNTIME'])) $options['ENABLE_RUNTIME'] = 'off';
   echo "<input name='kontrol_options[ENABLE_RUNTIME]' type='checkbox' id='ENABLE_RUNTIME'" . ($options['ENABLE_RUNTIME'] == 'on' ? ' checked' : '') . "/>";
   }

function kontrol_setting_enable_javascript()
   {
   echo "<input name='kontrol_options[ENABLE_JAVASCRIPT]' type='checkbox' id='ENABLE_JAVASCRIPT' disabled checked />";
   }

function kontrol_setting_enable_cookie()
   {
   $options = get_option('kontrol_options');
   if(!isset($options['ENABLE_COOKIE'])) $options['ENABLE_COOKIE'] = 'off';
   echo "<input name='kontrol_options[ENABLE_COOKIE]' type='checkbox' id='ENABLE_COOKIE'" . ($options['ENABLE_COOKIE'] == 'on' ? ' checked' : '') . "/><br /><br />";
   }

function _L($Content,$Length) { $Content = trim($Content); return (strlen($Content)>$Length?substr($Content,0,$Length):$Content); }
function kontrol_options_validate($input)
   {
   if(isset($input['API_KEY'])) $input['API_KEY'] = _L($input['API_KEY'],128);
   if(isset($input['LOG_LEVEL'])) $input['LOG_LEVEL'] = $input['LOG_LEVEL'] * 1;
   if(isset($input['ENABLE_ENV'])) $input['ENABLE_ENV'] = _L($input['ENABLE_ENV'],8);
   if(isset($input['ENABLE_RUNTIME'])) $input['ENABLE_RUNTIME'] = _L($input['ENABLE_RUNTIME'],8);
   if(isset($input['ENABLE_COOKIE'])) $input['ENABLE_COOKIE'] = _L($input['ENABLE_COOKIE'],8);
   return $input;
   }

function kontrol_settings_link($links)
   {
   $settings_link = '<a href="options-general.php?page=kontrol">Settings</a>';
   array_unshift($links,$settings_link);
   return $links;
   }
add_filter("plugin_action_links_kontrol/kontrol.php",'kontrol_settings_link');

?>