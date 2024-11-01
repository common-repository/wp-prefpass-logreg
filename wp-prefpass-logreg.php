<?php
/*
Plugin Name: PrefPass Universal Login and Registration
Plugin URI: http://about.prefpass.com/site-owners/wp-plugin
Based upon: http://www.jameskelly.org/wordpress-plugins/custom-login-and-registration-forms-plugin/ 
Description: This plugin is based upon a plugin that creates custom login and register pages to replace the wp-login and wp-register pages.  It was modified by PrefPass to let users register and log in using IDs including Facebook, OpenID, and Yahoo via PrefPass. This plugin writes a few options to your database, if you'd like to remove this data, check the appropriate box in the &quot;PrefPass Login Register&quot; menu above before deactivating this plugin.
Author: James Kelly, modified by PrefPass
Version: 1.2
Author URI: http://www.jameskelly.org/
Author URI: http://prefpass.com/
*/ 

/** 
 * Change Log
 *
 * 03/13/2008
 *  Added option to include or exclude username from login
 *
 * 12/11/2007 
 *  Added support for PrefPass
 *
 * 10/22/2006 
 *  Added a checkbox to the login registration options as well as functionality that will email users the custom email (also specified in the login options) whenever an administrator creates an account.  This option defaults to &quot;off&quot;.
 * 
 * 09/18/2006 
 *  Changed the cookie call so that the plugin would work with version WP 2.0.1 and added the ability to alter the default WP registration email, thanks to Eliyahu from http://tsiyon.org/podcast for the idea to change the default email.
 * 
 * 09/10/06
 *  When the reset password form was submitted the error page didn’t load the rest of the theme.  Now it loads the complete themed page including the sidbar.  Thanks to http://www.war59312.com/ for the request.
 * 
 * 09/09/06
 *  First bug found and corrected thanks to http://learninform.com.  The bug revolved around $wp_query->query_vars not existing as an array.
 * 
 * 09/09/06
 *  First version (0.9) went live.
 */

add_action('init', 'jk_do_login_register_init', 99);
// add_action('activate_'.basename(__FILE__), 'jk_custom_logreg_activate');
// add_action('deactivate_'.basename(__FILE__), 'jk_custom_logreg_deactivate');
register_activation_hook('wp-prefpass-logreg/' . basename(__FILE__), 'jk_custom_logreg_activate');
register_deactivation_hook('wp-prefpass-logreg/' . basename(__FILE__), 'jk_custom_logreg_deactivate');
add_action('jk_custom', 'jk_do_login_register_init', 99);
add_action('admin_menu', 'jk_custom_logreg_add_menu');

// For PrefPass, do not let users choose a new password from their profile
add_filter('show_password_fields', 'jk_turn_off_profile_passwords');
function jk_turn_off_profile_passwords() { 
  if (strpos($_SERVER['SCRIPT_NAME'], 'wp-admin/user-edit.php')) return true;   
  else return false; 
}

// For PrefPass, alert users that if they change their email, they must get a new password
add_action('show_user_profile', 'jk_custom_profile_msg');
function jk_custom_profile_msg()
{
  echo "<fieldset><legend>A note on changing your email</legend><p>Do you currently log in without a password? If so, we just wanted to let you know that if you change your email address, you'll also need to start using a password. Just log out and then click on the 'Lost your password?' link after you change your email!</p></fieldset>";
}

function jk_custom_logreg_add_menu()
{
  if ( function_exists('add_submenu_page') )
    add_submenu_page('plugins.php', __('PrefPass Login Register'), __('PrefPass Login Register'), 'manage_options', __FILE__, 'jk_custom_logreg_conf');
}

function jk_custom_logreg_activate()
{
  if (!get_option("jk_prefpass_code"))
    update_option("jk_prefpass_code", " ");

  if (!get_option("jk_prefpass_reg_choose"))
    update_option("jk_prefpass_reg_choose", 0);

  if (!get_option("jk_prefpass_login_show_username"))
    update_option("jk_prefpass_login_show_username", 0);

  if (!get_option("jk_custom_logreg_complete_uninstall"))
    update_option("jk_custom_logreg_complete_uninstall", 0);

  if (!get_option("jk_login_redirect_to"))
    update_option("jk_login_redirect_to", "wp-admin/");

  if (!get_option("jk_logout_redirect_to"))
    update_option("jk_logout_redirect_to", "wp-login.php");

  if (!get_option("jk_login_header_files"))
    update_option("jk_login_header_files", array("header.php"));

  if (!get_option("jk_login_after_head_html"))
    update_option("jk_login_after_head_html", " <div id=\"content\" class=\"narrowcolumn\">\n");

  if (!get_option("jk_login_footer_files"))
    update_option("jk_login_footer_files", array("sidebar.php", "footer.php"));

  if (!get_option("jk_login_before_foot_html"))
    update_option("jk_login_before_foot_html", "  </div>\n");

  if (!get_option("jk_login_form_text"))
    update_option("jk_login_form_text", "Log In");
     
  if (!get_option("jk_login_reg_form_text"))
    update_option("jk_login_reg_form_text", "Register");
     
  if (!get_option("jk_login_forgotpw_form_text"))
    update_option("jk_login_forgotpw_form_text", "Reset Password");

  if (!get_option("jk_login_register_from_email_address"))
    update_option("jk_login_register_from_email_address", preg_replace("/[^@]*@/i", "wordpress@", get_option("admin_email")));

  if (!get_option("jk_login_register_user_email_subject"))
    update_option("jk_login_register_user_email_subject", '##blogname## - Your user information');

  if (!get_option("jk_login_register_user_email"))
    update_option("jk_login_register_user_email", jk_defaults('reg_user_email'));

  if (!get_option("jk_login_register_admin_email_subject"))
    update_option("jk_login_register_admin_email_subject", '##blogname## - New User Registration');

  if (!get_option("jk_login_register_admin_email"))
    update_option("jk_login_register_admin_email", jk_defaults('reg_admin_email'));
}

function jk_custom_logreg_deactivate()
{
  if (1 == (int)get_option("jk_custom_logreg_complete_uninstall"))
  {
    delete_option("jk_prefpass_code");
    delete_option("jk_prefpass_reg_choose");
    delete_option("jk_prefpass_login_show_username");
    delete_option("jk_login_form_text");
    delete_option("jk_login_reg_form_text");
    delete_option("jk_login_forgotpw_form_text");
    delete_option("jk_logout_redirect_to");
    delete_option("jk_login_redirect_to");
    delete_option("jk_login_header_files");
    delete_option("jk_login_after_head_html");
    delete_option("jk_login_before_foot_html");
    delete_option("jk_login_footer_files");
    delete_option("jk_login_register_from_email_address");
    delete_option("jk_login_register_user_email_subject");
    delete_option("jk_login_register_user_email");
    delete_option("jk_login_register_admin_email_subject");
    delete_option("jk_login_register_admin_email");
    delete_option("jk_custom_logreg_complete_uninstall");
  }
}

if ( !function_exists('wp_new_user_notification') ) :
function wp_new_user_notification($user_id, $plaintext_pass = '') {
  $user = new WP_User($user_id);
    
  if (get_option('jk_login_register_admin_registration_send_email'))
  {
    $adminurl = '/wp-admin';
    $referer = strtolower(wp_get_referer());
      
    if (strlen($_REQUEST["pass1"]) > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'add-user') && strstr($referer, $adminurl))
        $plaintext_pass = $_REQUEST["pass1"];
  }
    
  $user_login = stripslashes($user->user_login);
  $user_email = stripslashes($user->user_email);
    
  $find = array('/##username##/i', '/##password##/i', '/##blogname##/i', '/##siteurl##/i', '/##loginurl##/i', '/##useremail##/i');
  $replace = array($user_login, $plaintext_pass, get_settings('blogname'), get_settings('siteurl'), get_settings('siteurl').'/wp-login.php', $user_email);
  
  $headers = "MIME-Version: 1.0\n" .
    "From: ". $user_email . "\n" . 
    "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\n";
      
  $subject = get_settings("jk_login_register_admin_email_subject");
  $subject = preg_replace($find, $replace, $subject);
  $subject = preg_replace("/##.*##/", "", $subject);  //get rid of any remaining variables
  
  $message = get_settings('jk_login_register_admin_email');
  $message = preg_replace($find, $replace, $message);
  $message = preg_replace("/##.*##/", "", $message);  //get rid of any remaining variables

  @wp_mail(get_settings('admin_email'), $subject, $message, $headers);

  if ( empty($plaintext_pass) )
    return;

  $headers = "MIME-Version: 1.0\n" .
    "From: ". get_settings("jk_login_register_from_email_address") . "\n" . 
    "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\n";
      
  $subject = get_settings("jk_login_register_user_email_subject");
  $subject = preg_replace($find, $replace, $subject);
  $subject = preg_replace("/##.*##/", "", $subject);  //get rid of any remaining variables
  
  $message = get_settings('jk_login_register_user_email');
  $message = preg_replace($find, $replace, $message);
  $message = preg_replace("/##.*##/", "", $message);  //get rid of any remaining variables
    
  wp_mail($user_email, $subject, $message, $headers);
}
endif;


if ( ! function_exists('wp_nonce_field') ) {
  function jk_custom_logreg_nonce_field($action = -1) {
    return; 
  }
  $jk_custom_logreg_nonce = -1;
} else {
  function jk_custom_logreg_nonce_field($action = -1) {
    return wp_nonce_field($action);
  }
  $jk_custom_logreg_nonce = 'jk-custom-logreg-update-key';
}


function jk_custom_logreg_conf()
{
  global $jk_custom_logreg_nonce;
  
  if (isset($_REQUEST["task"]))
  {
    if ($_REQUEST["task"] == 'reset_user_email')
    {
    update_option("jk_login_register_user_email_subject", '##blogname## - Your username and password');
    update_option("jk_login_register_user_email", jk_defaults('reg_user_email'));
    }
    elseif ($_REQUEST["task"] == 'reset_admin_email')
    {
    update_option("jk_login_register_admin_email_subject", '##blogname## - New User Registration');
    update_option("jk_login_register_admin_email", jk_defaults('reg_admin_email'));
    }
  }

  if ( $_POST ) {
    if ( !current_user_can('manage_options') )
      die(__('Cheatin&#8217; huh?'));
  
    check_admin_referer($jk_custom_logreg_nonce);
  
    if (isset($_POST['jk_custom_logreg_complete_uninstall']))
      update_option('jk_custom_logreg_complete_uninstall', 1);

    update_option('jk_prefpass_code', stripslashes($_POST['jk_prefpass_code']));
    if (isset($_POST['jk_prefpass_reg_choose']))
      update_option('jk_prefpass_reg_choose', 1);
    else update_option('jk_prefpass_reg_choose', 0);

    if (isset($_POST['jk_prefpass_login_show_username']))
      update_option('jk_prefpass_login_show_username', 1);
    else update_option('jk_prefpass_login_show_username', 0);
  
    update_option('jk_login_form_text', stripslashes($_POST['jk_login_form_text']));
    update_option('jk_login_reg_form_text', stripslashes($_POST['jk_login_reg_form_text']));
    update_option('jk_login_forgotpw_form_text', stripslashes($_POST['jk_login_forgotpw_form_text']));
    update_option('jk_login_redirect_to', stripslashes($_POST['jk_login_redirect_to']));
    update_option('jk_logout_redirect_to', stripslashes($_POST['jk_logout_redirect_to']));
    update_option('jk_login_after_head_html', stripslashes($_POST['jk_login_after_head_html']));
    update_option('jk_login_before_foot_html', stripslashes($_POST['jk_login_before_foot_html']));
    update_option('jk_login_register_from_email_address', stripslashes($_POST['jk_login_register_from_email_address']));
    update_option('jk_login_register_user_email_subject', stripslashes($_POST['jk_login_register_user_email_subject']));
    update_option('jk_login_register_user_email', stripslashes($_POST['jk_login_register_user_email']));
    update_option('jk_login_register_admin_email_subject', stripslashes($_POST['jk_login_register_admin_email_subject']));
    update_option('jk_login_register_admin_email', stripslashes($_POST['jk_login_register_admin_email']));
      
    if (isset($_POST['jk_login_register_admin_registration_send_email']))
      update_option('jk_login_register_admin_registration_send_email', 1);

    $error = "";
    $header_files = trim(str_replace("\r\n", "\n", stripslashes($_POST['jk_login_header_files'])));
    $header_files = explode("\n", $header_files);
    foreach((array)$header_files as $header_file) {
      if ( !file_exists(TEMPLATEPATH . '/' . $header_file) ) {
          $error .= "<li>The header file {$header_file} doesn't exist in your theme (template) directory, please verify the name and try again.</li>";
      } 
    }
    if ( empty($error) )
        update_option('jk_login_header_files', $header_files);
    
    $footer_files = trim(str_replace("\r\n", "\n", stripslashes($_POST['jk_login_footer_files'])));
    $footer_files = explode("\n", $footer_files);
    foreach((array)$footer_files as $footer_file) {
      if ( !file_exists(TEMPLATEPATH . '/' . $footer_file) ) {
        $error .= "<li>The footer file {$footer_file} doesn't exist in your theme (template) directory, please verify the name and try again.</li>";
      } 
    }
    if ( empty($error) ) {
      update_option('jk_login_footer_files', $footer_files);
      $success = "<li>Custom login and registration form options updated successfully!</li>";
    }
  } //end if ( $_POST )

?>
  <div class="wrap">
  <?php if ( strlen($error) > 0 ) { ?>
    <div id="message" class="updated fade">
    <p><strong><?php _e("<div><strong>Errors Exist</strong></div><ul>{$error}</ul>"); ?></strong></p>
    </div>
  <?php } ?>
  
  <?php if ( strlen($success) > 0 ) { ?>
    <div id="message" class="updated fade">
    <p><strong><?php _e("<div><strong>Success!</strong></div><ul>{$success}</ul>"); ?></strong></p>
    </div>
  <?php } ?>

  <h2><?php _e('PrefPass-enabled Custom Login and Registration'); ?></h2>
  <p><?php _e('<p>Use the options below to customize your login, registration, and related forms.  You should know a little something about the theme you are using, how to figure out which files are used by your theme, and have basic HTML skills.  Chances are if you are using a theme based on the default theme that this will just work.  But if it doesn\'t you need to be able to tweak it so that it does.</p>'); ?></p>
  <p><?php _e('<p>The forms all are encapsulated by a div with the id of <em>login</em>, so you should be able to modify your styles should you wish to customize the forms appearance.</p>'); ?></p>

  <h3>PrefPass account entries</h3>
  <p>If you haven't already done so, go to <a  target="_blank" href="http://prefpass.com">http://prefpass.com</a> and sign up, then activate your publisher account. Follow the instructions there, copying the following entries from here. Don't worry about the parts that tell you to paste things into pages on your site, this plugin takes care of all that!</p>
  <p>Just to make sure it's clear, the idea is to copy the below entries <em>from</em> here <em>to</em> your prefpass.com account.</p>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row">Registration form URL:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="{your URL here}/wp-register.php?action=register" size="45" />
      <br />
        This <em>must</em> be a complete URL, for example "http://www.mywordpressinstall.com/wp-register.php?action=register".
      </td> 
    </tr>   
    <tr valign="top"> 
      <th scope="row">Registration form email field:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="user_email" size="45" /></td> 
    </tr>
    <tr valign="top"> 
      <th scope="row">Registration form password field:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="user_pass" size="45" />
      <br />
        Leave the registration page password confirmation field blank.
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row">Login form URL:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="{your URL here}/wp-login.php" size="45" />
      <br />
        This also must be a complete URL, for example "http://www.mywordpressinstall.com/wp-login.php".
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row">Login page email field:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="eml" size="45" /></td> 
    </tr>
    <tr valign="top"> 
      <th scope="row">Login page password field:</th> 
      <td><input onclick="this.select();" type="text" style="border-color:#FFFFFF;" value="pwd" size="45" /></td> 
    </tr>
  </table>
    
  <form action="<?php _e('plugins.php?page=' . basename(dirname(__FILE__)) . '/' . basename(__FILE__)); ?>" method="post">
  <?php jk_custom_logreg_nonce_field($jk_custom_logreg_nonce) ?>
  <h3>PrefPass code snippet</h3>
  <p>This is the one item you have to copy <em>from</em> your prefpass.com account <em>to</em> here.</p>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_prefpass_code">PrefPass code snippet:</label></th> 
      <td>
        <textarea name="jk_prefpass_code" id="jk_prefpass_code" style="width: 95%; height: 75px;"><?php echo get_option("jk_prefpass_code") ? htmlspecialchars(get_option("jk_prefpass_code")) : ""; ?></textarea>
        <br />
        In the last step of setting up your PrefPass account, you will be provided with a code snippet. Copy it and paste it in above.
      </td> 
    </tr>
  </table>

  <h3>PrefPass options</h3>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_prefpass_reg_choose">Registration style:</label></th> 
      <td>
        <input name="jk_prefpass_reg_choose" type="checkbox" id="jk_prefpass_reg_choose" value="1"<?php echo (get_option('jk_prefpass_reg_choose') == 1 ? ' checked="checked"' :'') ?>"size="45" />
        <label for="jk_prefpass_reg_choose">Check this box if you want users to explicitly choose whether or not to use an external ID or a password before registering.</label>
        <p>If the box is not checked, users can still choose to use a password from the ID selection pop-up.</p> 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_prefpass_login_show_username">Login style:</label></th> 
      <td>
        <input name="jk_prefpass_login_show_username" type="checkbox" id="jk_prefpass_login_show_username" value="1"<?php echo (get_option('jk_prefpass_login_show_username') == 1 ? ' checked="checked"' :'') ?>"size="45" />
        <label for="jk_prefpass_login_show_username">Check this box if you want users to enter their username as well as their email address when logging in.</label>
        <p>NOTE: If you leave this box unchecked, make sure your existing users (including you as admin) all have email addresses defined. Users without valid email addresses will not be able to log in.</p> 
      </td> 
    </tr>
  </table>

  <h3>Redirection Options</h3>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_complete_uninstall">Redirect After Login:</label></th> 
      <td>
        <input name="jk_login_redirect_to" type="text" id="jk_login_redirect_to" value="<?php _e(htmlspecialchars(get_option('jk_login_redirect_to'))); ?>" size="45" />
        <br />
        This option allows you to designate where you want the user to be directed to once they log in successfully.  This defaults to wp-admin/ 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_logout_redirect_to">Redirect After Logout:</label></th> 
      <td>
        <input name="jk_logout_redirect_to" type="text" id="jk_logout_redirect_to" value="<?php _e(htmlspecialchars(get_option('jk_logout_redirect_to'))); ?>" size="45" />
        <br />
        This option allows you to designate where you want the user to be directed to once they log out (Sign Out).  This defaults to wp-login.php which should trigger the login form. 
      </td> 
    </tr>  
  </table>
   
  <h3>Template Options for Forms</h3>
  <table class="optiontable">
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_form_text">Login Form Title:</label></th> 
      <td>
        <input name="jk_login_form_text" type="text" id="jk_login_form_text" value="<?php _e(htmlspecialchars(get_option('jk_login_form_text'))); ?>" size="45" />
        <br />
        This text will appear above the Login Form.
      </td> 
    </tr> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_reg_form_text">Registration Form title:</label></th> 
      <td>
        <input name="jk_login_reg_form_text" type="text" id="jk_login_reg_form_text" value="<?php _e(htmlspecialchars(get_option('jk_login_reg_form_text'))); ?>" size="45" />
        <br />
        This text will appear above the Registration Form.
      </td> 
    </tr> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_forgotpw_form_text">Forgot Password Text:</label></th> 
      <td>
        <input name="jk_login_forgotpw_form_text" type="text" id="jk_login_forgotpw_form_text" value="<?php _e(htmlspecialchars(get_option('jk_login_forgotpw_form_text'))); ?>" size="45" />
        <br />
        This text will appear above the Forgot Password Form.
      </td> 
    </tr> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_header_files">Template Header Files:</label></th> 
      <td>
        <textarea name="jk_login_header_files" id="jk_login_header_files" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_header_files") ? htmlspecialchars(implode("\n", get_option("jk_login_header_files"))) : ""; ?></textarea>
        <br />
        Enter each header file used in your template one per line.  Typically this is only header.php.  You can figure this out by clicking Presentation=>Theme Editor=>Main Index Template.  If the only function call you see is get_header() before the HTML then it's likely this is the only file you need to enter.
      </td> 
    </tr> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_after_head_html">Template After Header HTML:</label></th> 
      <td>
        <textarea name="jk_login_after_head_html" id="jk_login_after_head_html" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_after_head_html") ? htmlspecialchars(get_option("jk_login_after_head_html")) : ""; ?></textarea>
        <br />
        Enter header HTML that appears between the get_header() function and the page code.  You can probably figure this out by clicking Presentation=>Theme Editor=>Main Index Template.  The HTML you need to copy is everything between the last ?&gt; in the top of the file and the line that looks something like this: &lt;?php if (have_posts()) : ?&gt;.
        <br />
        Keep in mind that if you are using a template that doesn't fit the typical scheme that you will need to experiment a bit to get this right.
      </td> 
    </tr>
    <tr><th colspan="2" style="text-align:center;"><h4>&lt;-- The Form Will Be Here --&gt;</h4></th></tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_before_foot_html">Template Before Footer HTML:</label></th> 
      <td>
        <textarea name="jk_login_before_foot_html" id="jk_login_before_foot_html" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_before_foot_html") ? htmlspecialchars(get_option("jk_login_before_foot_html")) : ""; ?></textarea>
        <br />
        Enter footer HTML that appears between the page code and the get_sidebar()/get_footer() functions.  You can probably figure this out by clicking Presentation=>Theme Editor=>Main Index Template and scrolling all the way to the bottom.  The HTML you need to copy is everything between the line that looks like this: &lt;?php endif; ?&gt; and the line that may look like this &lt;?php get_sidebar(); ?&gt;.
        <br />
        Keep in mind that if you are using a template that doesn't fit the typical scheme that you will need to experiment a bit to get this right.
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_footer_files">Template Footer Files:</label></th> 
      <td>
        <textarea name="jk_login_footer_files" id="jk_login_footer_files" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_footer_files") ? htmlspecialchars(implode("\n", get_option("jk_login_footer_files"))) : ""; ?></textarea>
        <br />
        Enter each footer file used in your template one per line.  Typically this is sidebar.php and footer.php.  You can figure this out by clicking Presentation=>Theme Editor=>Main Index Template.  If you see the function calls get_sidebar() and get_footer then you should be able to leave the defaults alone.
      </td> 
    </tr> 
  </table>

  <h3>Email Template Options</h3>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_admin_registration_send_email">Send When Admin Adds:</label></th> 
      <td>
        <input name="jk_login_register_admin_registration_send_email" type="checkbox" id="jk_login_register_admin_registration" value="1"<?php echo (get_option('jk_login_register_admin_registration_send_email') == 1 ? ' checked="checked"' :'') ?>"size="45" />
        <label for="jk_login_register_admin_registration_send_email">Check this box if you want an email to be sent when you (the admin) registers a new email.</label> 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_from_email_address">Admin From Address:</label></th> 
      <td>
        <input name="jk_login_register_from_email_address" type="text" id="jk_login_register_from_email_address" value="<?php _e(htmlspecialchars(get_option('jk_login_register_from_email_address'))); ?>" size="45" />
        <br />
        You can customize the address that your registration confirmation comes from.  By default this is wordpress@<em>yourdomain.com</em>. 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_user_email_subject">User Subject Line:</label></th> 
      <td>
        <input name="jk_login_register_user_email_subject" type="text" id="jk_login_register_user_email_subject" value="<?php _e(htmlspecialchars(get_option('jk_login_register_user_email_subject'))); ?>" size="45" maxlengt="150" />
        <br />
        Here you can customize the subject of the email that goes out to new registrations.  The same variables exist here using the same syntax as outlined below. 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_user_email">User Email Template:</label></th> 
      <td>
        <div class="alignright"><a href="<?php _e('plugins.php?page=' .  basename(dirname(__FILE__)) . '/' . basename(__FILE__)); ?>&task=reset_user_email">Reset User Email to Default</a></div>
        <textarea name="jk_login_register_user_email" id="jk_login_register_user_email" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_register_user_email") ? htmlspecialchars(get_option("jk_login_register_user_email")) : ""; ?></textarea>
        <br />
        In this area you can override the default email that is sent out <strong>to your users</strong> when they register.  You can write anything you want here, one <strong>IMPORTANT</strong> step that you musn't forget is to add the variables.  The variables you can use are ##username##, ##password##, ##siteurl##, ##blogname##, and ##loginurl##.  Note the double pound signs (##), each variable must have double pound signs around them with <strong>no spaces</strong>.  See the current default as an example.
        <br />
        <strong>IMPORTANT:</strong> Make certain you test this by registering using a test email address so you know what your users are getting.
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_admin_email_subject">Admin Subject Line:</label></th> 
      <td>
        <input name="jk_login_register_admin_email_subject" type="text" id="jk_login_register_admin_email_subject" value="<?php _e(htmlspecialchars(get_option('jk_login_register_admin_email_subject'))); ?>" size="45" maxlengt="150" />
          <br />
          Here you can customize the subject of the email that goes to you, the administrator when a new user registers.  The same variables exist here using the same syntax as outlined below. 
      </td> 
    </tr>
    <tr valign="top"> 
      <th scope="row"><label for="jk_login_register_admin_email">Admin Email Template:</label></th> 
      <td>
        <div class="alignright"><a href="<?php _e('plugins.php?page=' .  basename(dirname(__FILE__)) . '/' . basename(__FILE__)); ?>&task=reset_admin_email">Reset Admin Email to Default</a></div> 
        <br />
        <textarea name="jk_login_register_admin_email" id="jk_login_register_admin_email" style="width: 95%; height: 75px;"><?php echo get_option("jk_login_register_admin_email") ? htmlspecialchars(get_option("jk_login_register_admin_email")) : ""; ?></textarea>
        In this area you can override the default email that is sent to you when a new user registers.  This email probably isn't as important as the email that goes to your users, but the same rules apply which I will repeat.  The variables you can use are ##username##, ##useremail##, ##blogname##, ##siteurl##, and ##loginurl##.  Note the double pound signs (##), each variable must have double pound signs around them with <strong>no spaces</strong>.  See the current default as an example.
      </td> 
    </tr>
  </table>

  <h3>Uninstallation Options</h3>
  <table class="optiontable"> 
    <tr valign="top"> 
      <th scope="row"><label for="jk_custom_logreg_complete_uninstall">Toggle Complete Uninstall:</label></th> 
      <td>
        <input name="jk_custom_logreg_complete_uninstall" type="checkbox" id="jk_custom_logreg_complete_uninstall" value="1"<?php (1 == (int)get_option("jk_custom_logreg_complete_uninstall")) ? _e(' checked="checked"') : _e(''); ?> />
          <br />
          Often plugins write options to your database upon activation and rarely ever remove them which can clutter up your database.  This plugin will remove all
          data upon deactivation if you check this box.  If this box is unchecked then you can safely deactivate this plugin and all your options you've set will remain
          once you re-activate the plugin.
      </td> 
    </tr>
  </table>

  <p class="submit"><input type="submit" name="Submit" value="Update Options &raquo;" /> 
  </form>
  </div>

<?  
}

function jk_do_login_register_init()
{
  global $pagenow;

  switch ($pagenow)
  {
    case "wp-login.php":
    if ($_REQUEST["action"] == "register") wp_redirect("wp-register.php");
      else jk_do_login();
    break;
    case "wp-register.php":
      jk_do_register();
    break;
  }
}

function jk_do_login()
{
  global $wpdb, $error, $wp_query;

  if (!is_array($wp_query->query_vars))
    $wp_query->query_vars = array();
  
  $action = $_REQUEST['action'];
  $error = '';
  
  nocache_headers();
  
  header('Content-Type: '.get_bloginfo('html_type').'; charset='.get_bloginfo('charset'));
  
  if ( defined('RELOCATE') ) 
  { // Move flag is set
    if ( isset( $_SERVER['PATH_INFO'] ) && ($_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF']) )
      $_SERVER['PHP_SELF'] = str_replace( $_SERVER['PATH_INFO'], '', $_SERVER['PHP_SELF'] );
  
    $schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
    if ( dirname($schema . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) != get_settings('siteurl') )
      update_option('siteurl', dirname($schema . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']) );
  }

  switch($_REQUEST["action"])
  {
    //logout
    case "logout":
      wp_clearcookie();
      if(get_option("jk_logout_redirect_to"))
        $redirect_to = get_option("jk_logout_redirect_to");
      else
        $redirect_to = "wp-login.php";
      do_action('wp_logout');
      nocache_headers();
    
      if ( isset($_REQUEST['redirect_to']) )
        $redirect_to = $_REQUEST['redirect_to'];
      
      wp_redirect($redirect_to);
      exit();
      break;

    //lost lost password
    case 'lostpassword':
      do_action('lost_password');

      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);

      // PrefPass init code added here (IDs loaded from options entry fields)
      echo get_option("jk_prefpass_code");
          
      echo get_option("jk_login_after_head_html");
?>
      <div id="login">
        <h2><?php _e(get_option("jk_login_forgotpw_form_text")) ?></h2>
        <p><?php _e('Please enter your information here. We will send you a new password.') ?></p>
        <?php if ($error) {echo "<div id='login_error'>$error</div>";} ?>
        
        <form id="prefpassFormSubmissionPasswordReset" name="lostpass" action="wp-login.php" method="post">
        <p>
  
          <input type="hidden" name="action" value="retrievepassword" />
  
          <label><?php _e('Username:') ?><br />
          <input type="text" name="user_login" id="user_login" value="" size="20" tabindex="1" /></label>
        </p>
        <p><label><?php _e('E-mail:') ?><br />
          <input type="text" name="email" id="email" value="" size="20" tabindex="2" /></label><br />
        </p>
        
        <!-- Gotcha: name / id of "submit" interferes with submit() method; changed to "submitbutton" -->
        <p class="submit"><input onClick="prefpassSSORouterPasswordReset(this.form.email.value);return false;" type="submit" name="submitbutton" id="submitbutton" value="<?php _e('Retrieve Password'); ?> &raquo;" tabindex="3" /></p>
        </form>
        <ul>
        <li><a href="<?php bloginfo('home'); ?>/" title="<?php _e('Are you lost?') ?>">&laquo; <?php _e('Home') ?></a></li>
        <?php if (get_settings('users_can_register')) : ?>
        <li><a href="<?php bloginfo('wpurl'); ?>/wp-register.php"><?php _e('Register') ?></a></li>
        <?php endif; ?>
        <li><a href="<?php bloginfo('wpurl'); ?>/wp-login.php"><?php _e('Login') ?></a></li>
        </ul>
      </div>
<?php
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;

    //lost retrieve password
    case 'retrievepassword':
      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);
      
      $user_data = get_userdatabylogin($_POST['user_login']);
      // redefining user_login ensures we return the right case in the email
      $user_login = $user_data->user_login;
      $user_email = $user_data->user_email;
    
      if (!$user_email || $user_email != $_POST['email'])
      {
        echo get_option("jk_login_after_head_html");
        echo sprintf(__('Sorry, that user does not seem to exist in our database. Perhaps you have the wrong username or e-mail address? <a href="%s">Try again</a>.'), 'wp-login.php?action=lostpassword');
        echo get_option("jk_login_before_foot_html");
    
        $footer_files = get_option("jk_login_footer_files");
        foreach((array)$footer_files as $footer_file)     
          include(TEMPLATEPATH . '/' . $footer_file);
        die();
      }
    
      do_action('retreive_password', $user_login);  // Misspelled and deprecated.
      do_action('retrieve_password', $user_login);
    
      // Generate something random for a password... md5'ing current time with a rand salt
      $key = substr( md5( uniqid( microtime() ) ), 0, 50);
      // now insert the new pass md5'd into the db
      $wpdb->query("UPDATE $wpdb->users SET user_activation_key = '$key' WHERE user_login = '$user_login'");
      $message = __('Someone has asked to reset the password for the following site and username.') . "\r\n\r\n";
      $message .= get_option('siteurl') . "\r\n\r\n";
      $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
      $message .= __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.') . "\r\n\r\n";
      $message .= get_settings('siteurl') . "/wp-login.php?action=resetpass&key=$key\r\n";
    
      $m = wp_mail($user_email, sprintf(__('[%s] Password Reset'), get_settings('blogname')), $message);
    
      echo get_option("jk_login_after_head_html");
      echo "          <div id=\"login\">\n";
      if ($m == false) 
      {
        echo "<h1>There Was a Problem</h1>";
        echo '<p>' . __('The e-mail could not be sent.') . "<br />\n";
        echo  __('Possible reason: your host may have disabled the mail() function...') . "</p>";
      } 
      else 
      {
        echo "<h1>Success!</h1>";
        echo '<p>' .  sprintf(__("The e-mail was sent successfully to %s's e-mail address."), $user_login) . '<br />';
        echo  "<a href='wp-login.php' title='" . __('Check your e-mail first, of course') . "'>" . __('Click here to login!') . '</a></p>';
      }
      echo "          </div>\n";
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;
    
    //reset password
    case 'resetpass' :
      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);
      
      echo get_option("jk_login_after_head_html");

      echo "          <div id=\"login\">\n";
      // Generate something random for a password... md5'ing current time with a rand salt
      $key = preg_replace('/a-z0-9/i', '', $_GET['key']);
      if ( empty($key) )
      {
        _e('<h1>Problem</h1>');
        _e('Sorry, that key does not appear to be valid.');
        echo "          </div>\n";
        echo get_option("jk_login_before_foot_html");

        $footer_files = get_option("jk_login_footer_files");
        foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

        die();
      }
      $user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE user_activation_key = '$key'");
      if ( !$user )
      {
        _e('<h1>Problem</h1>');
        _e('Sorry, that key does not appear to be valid.');
        echo "          </div>\n";
        echo get_option("jk_login_before_foot_html");

        $footer_files = get_option("jk_login_footer_files");
        foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

        die();
      }
    
      do_action('password_reset');
    
      $new_pass = substr( md5( uniqid( microtime() ) ), 0, 7);
      $wpdb->query("UPDATE $wpdb->users SET user_pass = MD5('$new_pass'), user_activation_key = '' WHERE user_login = '$user->user_login'");
      wp_cache_delete($user->ID, 'users');
      wp_cache_delete($user->user_login, 'userlogins'); 
      $message  = sprintf(__('Username: %s'), $user->user_login) . "\r\n";
      $message .= sprintf(__('Password: %s'), $new_pass) . "\r\n";
      $message .= get_settings('siteurl') . "/wp-login.php\r\n";
    
      $m = wp_mail($user->user_email, sprintf(__('[%s] Your new password'), get_settings('blogname')), $message);
    
      if ($m == false) 
      {
        echo ('<h1>Problem</h1>');
        echo '<p>' . __('The e-mail could not be sent.') . "<br />\n";
        echo  __('Possible reason: your host may have disabled the mail() function...') . '</p>';
      } 
      else 
      {
        echo ('<h1>Success!</h1>');
        echo '<p>' .  sprintf(__('Your new password is in the mail.'), $user_login) . '<br />';
        echo  "<a href='wp-login.php' title='" . __('Check your e-mail first, of course') . "'>" . __('Click here to login!') . '</a></p>';
        // send a copy of password change notification to the admin
        $message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
        wp_mail(get_settings('admin_email'), sprintf(__('[%s] Password Lost/Change'), get_settings('blogname')), $message);
      }
      echo "          </div>\n";
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
      include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;
    
    //login and default action
    case 'login' : 
    default:
      //check credentials - 99% of this is identical to the normal wordpress login sequence as of 2.0.4
      //Any differences will be noted with end of line comments. 
      $user_login = '';
      $user_pass = '';
      $using_cookie = false;
      /**
      * this is what the code was
      * if ( !isset( $_REQUEST['redirect_to'] ) )
      *  $redirect_to = 'wp-admin/';
      * else
      *  $redirect_to = $_REQUEST['redirect_to'];
      **/
      if ( !isset( $_REQUEST['redirect_to'] ) ) {
        if ( get_option("jk_login_redirect_to") )
          $redirect_to = get_option("jk_login_redirect_to");
        else
          $redirect_to = 'wp-admin/';
      } else {
        $redirect_to = $_REQUEST['redirect_to'];
      }

      if( $_POST ) {
        $user_login = $_POST['log'];
        $user_login = sanitize_user( $user_login );
        $user_pass  = $_POST['pwd'];
        $rememberme = $_POST['rememberme'];
      } else {
        if (function_exists('wp_get_cookie_login'))   //This check was added in version 1.0 to make the plugin compatible with WP2.0.1
        {
          $cookie_login = wp_get_cookie_login();
          if ( ! empty($cookie_login) ) {
            $using_cookie = true;
            $user_login = $cookie_login['login'];
            $user_pass = $cookie_login['password'];
          }
        }
        elseif ( !empty($_COOKIE) ) //This was added in version 1.0 to make the plugin compatible with WP2.0.1
        {
          if ( !empty($_COOKIE[USER_COOKIE]) )
            $user_login = $_COOKIE[USER_COOKIE];
          if ( !empty($_COOKIE[PASS_COOKIE]) ) {
            $user_pass = $_COOKIE[PASS_COOKIE];
            $using_cookie = true;
          }
        }
      }
    
      // get user_login from email
      if ($user_login == "") {
        global $wpdb;
        $user_email = $_POST['eml'];
        if (is_email($user_email)) {
          $user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE user_email = '$user_email'");
          if ($user) $user_login = $user->user_login;
        }
      }

      do_action('wp_authenticate', array(&$user_login, &$user_pass));
      if ( $user_login && $user_pass ) {
        $user = new WP_User(0, $user_login);
      
        // If the user can't edit posts, send them to their profile.
        if ( !$user->has_cap('edit_posts') && ( empty( $redirect_to ) || $redirect_to == 'wp-admin/' ) )
          $redirect_to = get_settings('siteurl') . '/wp-admin/profile.php';
      
        if ( wp_login($user_login, $user_pass, $using_cookie) ) {
          if ( !$using_cookie )
            wp_setcookie($user_login, $user_pass, false, '', '', $rememberme);
          do_action('wp_login', $user_login);
          wp_redirect($redirect_to);
          exit;
        } else {
          if ( $using_cookie )      
            $error = __('Your session has expired.');
        }
      } else if ( $user_login || $user_pass ) {
        $error = __('<strong>Error</strong>: The password field is empty.');
      }

      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);

      // PrefPass init code added here (IDs loaded from options entry fields)
      echo get_option("jk_prefpass_code");
      
      echo get_option("jk_login_after_head_html");
?>
      <div id="login">                                             
      <h2><?php _e(get_option("jk_login_form_text")) ?></h2>
      <?php if ( $error ) {echo "<div id='login_error'>$error</div>"; } ?>
      <!-- form id changed from "loginform" for PrefPass -->
      <form name="loginform" id="prefpassFormSubmissionLogin"  action="wp-login.php" method="post">
 
        <!-- For PrefPass, only show username if admin wants to -->
        <?php if (get_option("jk_prefpass_login_show_username") == 1) { ?>       
          <p><label><?php _e('Username:') ?><br /><input type="text" name="log" id="log" value="<?php echo wp_specialchars(stripslashes($user_login), 1); ?>" size="20"  /></label></p>
        <?php } ?>

        <!-- Email field added for PrefPass -->
        <p><label><?php _e('Email:') ?><br /><input type="text" name="eml" id="user_email" value="<?php echo attribute_escape(stripslashes($user_email)); ?>" size="20" /></label></p>

        <p><label><?php _e('Password:') ?><br /> <input type="password" name="pwd" id="login_password" value="" size="20"  /></label></p>
        
        <!-- Added PrefPass graphic here -->
        <p><img src="<?php echo get_settings('siteurl').'/wp-content/plugins/wp-prefpass-logreg/prefpass-icons-login.gif'; ?>" border="0" /></p>
        
        <p>
          <label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="3" /> 
          <?php _e('Remember me'); ?></label></p>

        <!-- Submit onclick action added for PrefPass -->
        <!-- Gotcha: name / id cannot be "submit", changed to "submitbutton" -->
        <p class="submitbutton">
          <input type="submit" onClick="prefpassSSORouterLogin(this.form.eml.value);return false;" name="submitbutton" id="submitbutton" value="<?php _e('Login'); ?> &raquo;" tabindex="4" />
          <input type="hidden" name="redirect_to" value="<?php echo wp_specialchars($redirect_to); ?>" />
        </p>
      </form>
      <ul>
        <li><a href="<?php bloginfo('home'); ?>/" title="<?php _e('Are you lost?') ?>">&laquo; <?php _e('Home') ?></a></li>
        <?php if (get_settings('users_can_register')) : ?>
          <li><a href="<?php bloginfo('wpurl'); ?>/wp-register.php"><?php _e('Register') ?></a></li>
        <?php endif; ?>
        <li><a href="<?php bloginfo('wpurl'); ?>/wp-login.php?action=lostpassword" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a></li>
      </ul>
      </div>
<?php
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;
  } // end switch
}

function jk_do_register()
{
  global $wpdb, $wp_query;

  if (!is_array($wp_query->query_vars))
    $wp_query->query_vars = array();


  switch( $_REQUEST["action"] ) 
  {
    case 'register':
      require_once( ABSPATH . WPINC . '/registration-functions.php');
      
      $user_login = sanitize_user( $_POST['user_login'] );
      $user_email = $_POST['user_email'];

      // added for PrefPass: get password
      $user_pass = '';
      if ( isset( $_POST['user_pass'] ))
        $user_pass = $_POST['user_pass'];

      $errors = array();
        
      if ( $user_login == '' )
        $errors['user_login'] = __('<strong>ERROR</strong>: Please enter a username.');
    
      /* checking e-mail address */
      if ($user_email == '') {
        $errors['user_email'] = __('<strong>ERROR</strong>: Please type your e-mail address.');
      } else if (!is_email($user_email)) {
        $errors['user_email'] = __('<strong>ERROR</strong>: The email address isn&#8217;t correct.');
        $user_email = '';
      }
    
      if ( ! validate_username($user_login) ) {
        $errors['user_login'] = __('<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.');
        $user_login = '';
      }
    
      if ( username_exists( $user_login ) )
        $errors['user_login'] = __('<strong>ERROR</strong>: This username is already registered, please choose another one.');
    
      /* checking the email isn't already used by another user */
      $email_exists = $wpdb->get_row("SELECT user_email FROM $wpdb->users WHERE user_email = '$user_email'");
      if ( $email_exists)
        die (__('<strong>ERROR</strong>: This email address is already registered, please supply another.'));
        
      // added for PrefPass: Check the password (error checking from admin-functions.php line 513)
      if ($user_pass == '') {
        $errors['user_pass'] = __('<strong>ERROR</strong>: Please enter a password.');
      } elseif ( strpos( " ".$user_pass, "\\" ) )
        $errors['user_pass'] = __('<strong>ERROR</strong>: Passwords may not contain the character "\\".');

      if ( 0 == count($errors) ) {
        // removed for PrefPass
        // $password = substr( md5( uniqid( microtime() ) ), 0, 7);
      
        $user_id = wp_create_user( $user_login, $user_pass, $user_email );
        if ( !$user_id )
          $errors['user_id'] = sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !'), get_settings('admin_email'));
        else
          wp_new_user_notification($user_id, $user_pass);
      }
      
      if ( 0 == count($errors) ) 
      {//continues after the break; 

        // For PrefPass, we already have the user password, so instead 
        //   of asking the user to log in, we just log them in automatically
        $_REQUEST["action"] = "login";
        $_POST['log'] = $user_login;
        $_POST['pwd'] = $user_pass;
        $_POST['eml'] = $user_email;
        $_POST['rememberme'] = true;
        jk_do_login();
      
        // the below will never happen since logging the user in does a redirect
        $header_files = get_option("jk_login_header_files");
        foreach((array)$header_files as $header_file)     
          include(TEMPLATEPATH . '/' . $header_file);
          
        echo get_option("jk_login_after_head_html");
?>
        <div id="login">                                             
          <h2><?php _e('Registration Complete') ?></h2>
          <p><?php printf(__('Username: %s'), "<strong>" . wp_specialchars($user_login) . "</strong>") ?><br />
  
          <!-- Removed for PrefPass -->
          <!-- <?php printf(__('Password: %s'), '<strong>' . __('emailed to you') . '</strong>') ?> <br /> -->
  
          <?php printf(__('E-mail: %s'), "<strong>" . wp_specialchars($user_email) . "</strong>") ?></p>
          <p class="submit"><a href="wp-login.php"><?php _e('Login'); ?> &raquo;</a></p>
        </div>
<?php
        echo get_option("jk_login_before_foot_html");

        $footer_files = get_option("jk_login_footer_files");
        foreach((array)$footer_files as $footer_file)     
          include(TEMPLATEPATH . '/' . $footer_file);

        die();
        break;
      }//continued from the error check above

    default:
      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);

      // PrefPass init code added here (IDs loaded from options entry fields)
      echo get_option("jk_prefpass_code");
        
      echo get_option("jk_login_after_head_html");
?>
      <div id="login">                                             
        <h2><?php _e(get_option("jk_login_reg_form_text")) ?></h2>
        <?php if ( isset($errors) ) : ?>
          <div class="error">
            <ul>
            <?php
            foreach($errors as $error) echo "<li>$error</li>";
            ?>
            </ul>
          </div>
        <?php endif; ?>
                                                                  
        <!-- Add PrefPass graphic here for normal use -->
        <?php if (get_option("jk_prefpass_reg_choose") != 1) : ?>
          <p><img src="<?php echo get_settings('siteurl').'/wp-content/plugins/wp-prefpass-logreg/prefpass-icons-reg.gif'; ?>" border="0" /></p>
        <?php endif; ?>
        
        <!-- form id changed from "registerform" for PrefPass -->
        <form id="prefpassFormSubmissionReg" method="post" action="wp-register.php">
          
          <!-- PrefPass gotcha: field of name "action" interferes with action attribute;
          <input type="hidden" name="action" value="register" />
          -->
          <p><label for="user_login"><?php _e('Username:') ?></label><br /> <input type="text" name="user_login" id="user_login" size="20" maxlength="20" value="<?php echo wp_specialchars($user_login); ?>" /><br /></p>
          <p><label for="user_email"><?php _e('E-mail:') ?></label><br /> <input type="text" name="user_email" id="user_email" size="20" maxlength="100" value="<?php echo wp_specialchars($user_email); ?>" /></p>
          
          <!-- if selected, add explicit choice to use PrefPass -->
          <?php if (get_option("jk_prefpass_reg_choose") == 1) : ?>
            <p><input type=radio name="prefpass_reg_choose" value="no" onClick="prefpassHidePasswordOptions();" checked>Use one of these IDs:</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="<?php echo get_settings('siteurl').'/wp-content/plugins/wp-prefpass-logreg/prefpass-icons.gif'; ?>" align="top" border="0" /></p>
            <p><input type=radio name="prefpass_reg_choose" value="yes" onClick="prefpassDisplayPasswordOptions();">Choose a password</p>
          <?php endif; ?>

          <!-- added for PrefPass -->
          <span id="prefpass-enabled-password-field-1"><p><label for="user_pass"><?php _e('Password:') ?></label><br /> <input type="password" name="user_pass" id="user_pass" size="20" maxlength="100" value="" /></p></span>
          <script>prefpassHidePasswordOptions();</script>  

          <!-- removed for prefPass -->
          <!-- <p><?php _e('A password will be emailed to you.') ?></p> -->

          <!-- Submit onclick action added for PrefPass -->
          <!-- PrefPass gotcha: name / id of "submit" interferes with submit() method; changed to "submitbutton" -->
          <input type="submit" value="<?php _e('Register') ?> &raquo;" onClick="prefpassSSORouterReg(this.form.user_email.value);return false;" id="submitbutton" name="submitbutton" /><p class="submitbutton"></p>   

        </form>
        <ul>
          <li><a href="<?php bloginfo('home'); ?>/" title="<?php _e('Are you lost?') ?>">&laquo; <?php _e('Home') ?></a></li>
          <li><a href="<?php bloginfo('wpurl'); ?>/wp-login.php"><?php _e('Login') ?></a></li>
          <li><a href="<?php bloginfo('wpurl'); ?>/wp-login.php?action=lostpassword" title="<?php _e('Password Lost and Found') ?>"><?php _e('Lost your password?') ?></a></li>
        </ul>
      </div>
<?php
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;
    case 'disabled':
      $header_files = get_option("jk_login_header_files");
      foreach((array)$header_files as $header_file)     
        include(TEMPLATEPATH . '/' . $header_file);
        
      echo get_option("jk_login_after_head_html");
?>
      <div id="login">                                             
        <h2><?php _e('Registration Disabled') ?></h2>
        <p><?php _e('User registration is currently not allowed.') ?><br />
        <a href="<?php echo get_settings('home'); ?>/" title="<?php _e('Go back to the blog') ?>"><?php _e('Home') ?></a>
        </p>
      </div>
<?php
      echo get_option("jk_login_before_foot_html");

      $footer_files = get_option("jk_login_footer_files");
      foreach((array)$footer_files as $footer_file)     
        include(TEMPLATEPATH . '/' . $footer_file);

      die();
      break;
  } // end switch
}

function jk_defaults($type)
{
  $str = "";

  switch ($type)
  {
    case "reg_user_email":
        $str = "Username: ##username##\n";

    // removed for PrefPass
        // $str .= "Password: ##password##\n";
          $str .= "##loginurl##";
        break;
    case "reg_admin_email":
        $str = "New user registration on your blog ##blogname##:\n";
        $str .= "Username: ##username##\n";
        $str .= 'E-mail: ##useremail##';
        break;
  }
  
  return $str;
}
?>
