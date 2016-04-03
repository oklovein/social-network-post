<?php
/**
 * @package Snetpost
 */
/*
Plugin Name: Snetpost
Plugin URI: http://ifyouknowit.com/
Description: Used to various social networks like facebook,twitter from wordpress. Put your authentication token in wordpress settings and go on posting to your timeline. 
Version: 1.0.0
Author: ifyouknowit
Author URI: http://www.ifyouknowit.com
License: GPLv2 or later
Text Domain: snetpost
*/


class Snetpost{

 private $my_plugin_screen_name;
 private static $instance;

 static function getInstance(){
    if(!isset(self::$instance)){
      self::$instance =  new self();
    }
    
    return self::$instance;
 
 }
 
    public function PluginMenu()
    {

       $this->my_plugin_screen_name = add_menu_page(
                                        'Social Network Post Settings', 
                                        'SNP Settings', 
                                        'manage_options',
                                        'snetpost_options',
                                        array($this,'snetpost_options_page')                                           
                                        );
                                        
                       
                                        
                                        
add_submenu_page('snetpost_options','Get Tokens', 'Get Tokens','manage_options', 'snpost_gettoken',array($this,'addscreen_snetpost'));
 
    }
    
    
     function register_snetpost_settings() {
    register_setting('snetpost_plugin_options','snetpost_plugin_options');

    add_settings_section('plugin_main', 'SNP settings', array($this,'plugin_section_text'), 'snetpost_plugin');

    add_settings_field('facebook_apikey', 'Facebook API Key', array($this,'facebook_apikey_fn'), 'snetpost_plugin', 'plugin_main');

   add_settings_field('facebook_sec', 'Facebook Secret', array($this,'facebook_sec_fn'), 'snetpost_plugin', 'plugin_main');
   add_settings_field('facebook_token', 'Facebook Token', array($this,'facebook_token_fn'), 'snetpost_plugin', 'plugin_main');
  
   
    }
   
      public function InitPlugin()
      {
          add_action('admin_init', array($this,'register_snetpost_settings'));         
          add_action('admin_menu',array($this,'PluginMenu'));
          add_action('init', array($this,'sess_fn'), 1);
          add_action('wp_logout', array($this,'end_sess_fn'));
          add_action('wp_login', array($this,'end_sess_fn'));
          add_action('publish_post', array($this,'post_published_snp'), 10, 2 );

      }


function sess_fn(){
    if(!session_id()) {
        session_start();
    }

}

function end_sess_fn() {
    session_destroy();
}
    
    
 /* function snetpost_admin_options() {  
  add_options_page('Social Network Post Settings', 'Social Network Post Settings', 'manage_options', 'snetpost_plugin', array($this,'snetpost_options_page'));
} */
   
   
   function addscreen_snetpost(){ 
   

   
    $site_target = get_site_url().'/wp-admin/admin.php?page=snetpost_options';
   
   	require_once __DIR__ . '/vendor/autoload.php';


$snetpost_plugin_options = get_option('snetpost_plugin_options');

$fb = new Facebook\Facebook([
  'app_id' => $snetpost_plugin_options['facebook_apikey'],
  'app_secret' => $snetpost_plugin_options['facebook_sec'],
  'default_graph_version' => 'v2.5',
]);




echo '<p>Get Tokens from Social Net Work Sites ?</p>';
// echo '<br><a href="' . $loginUrl . '">Log in with Facebook!</a>';


$helper = $fb->getRedirectLoginHelper();
try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if($accessToken){

$oAuth2Client = $fb->getOAuth2Client();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
   // exit;
  }


}

    $snetpost_plugin_options = get_option('snetpost_plugin_options');
 
    $snetpost_plugin_options['facebook_token']=(string)$accessToken;
    update_option('snetpost_plugin_options',$snetpost_plugin_options);
    
    echo 'Facebook Token Set,Check in SNP settings';
   
   
   }
   
	
}


function post_published_snp( $ID, $post ) {
    $author = $post->post_author; /* Post author ID. */
    $name = get_the_author_meta( 'display_name', $author );
   // $email = get_the_author_meta('user_email',$author );
    $title = $post->post_title;
    $permalink = get_permalink( $ID );
    //$edit = get_edit_post_link( $ID, '' );
    //$to[] = sprintf( '%s <%s>', $name, $email );
   
   // $message = $title ."\r\n".$post->post_content;
    $message = $title;
    $message .= "\r\n".$permalink;
    
    
require_once __DIR__ . '/vendor/autoload.php';


$snetpost_plugin_options = get_option('snetpost_plugin_options');

$fb = new Facebook\Facebook([
  'app_id' => $snetpost_plugin_options['facebook_apikey'],
  'app_secret' => $snetpost_plugin_options['facebook_sec'],
  'default_graph_version' => 'v2.5',
]);
    
 $linkData = [
  'access_token' => $snetpost_plugin_options['facebook_token'],
  'message' => $message, 
  ];

try {
  // Returns a `Facebook\FacebookResponse` object
  $response = $fb->post('/me/feed', $linkData);
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}
  
}



  function plugin_section_text() {

	echo '<p>Social Network Post Settings ?</p>';
	
	$site_target = get_site_url().'/wp-admin/admin.php?page=snpost_gettoken';
   
   	require_once __DIR__ . '/vendor/autoload.php';




$snetpost_plugin_options = get_option('snetpost_plugin_options');

$fb = new Facebook\Facebook([
  'app_id' => $snetpost_plugin_options['facebook_apikey'],
  'app_secret' => $snetpost_plugin_options['facebook_sec'],
  'default_graph_version' => 'v2.5',
]);




$helper = $fb->getRedirectLoginHelper();
$permissions = ['email', 'user_likes','user_posts','publish_actions']; // optional
// echo 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
// $loginUrl = $helper->getLoginUrl('http://'.$site_target, $permissions);

$loginUrl = $helper->getLoginUrl($site_target , $permissions);
// echo $_SESSION['FBRLH_' . 'state'];
// $_SESSION['FBRLH_' . 'state1']=$_SESSION['FBRLH_' . 'state'];

echo '<br><a href="' . $loginUrl . '">Log in with Facebook!</a>';
	
  }


function facebook_apikey_fn() {
$snetpost_plugin_options = get_option('snetpost_plugin_options');



echo "<input id='facebook_apikey' value='".$snetpost_plugin_options['facebook_apikey']."' name='snetpost_plugin_options[facebook_apikey]' size='75' type='text' />";
} 


function facebook_sec_fn() {
$snetpost_plugin_options = get_option('snetpost_plugin_options');



echo "<input id='facebook_sec' value='".$snetpost_plugin_options['facebook_sec']."' name='snetpost_plugin_options[facebook_sec]' size='75' type='text' />";
} 


function facebook_token_fn() {
$snetpost_plugin_options = get_option('snetpost_plugin_options');



echo "<input id='facebook_token' value='".$snetpost_plugin_options['facebook_token']."' name='snetpost_plugin_options[facebook_token]' size='75' type='text' />";
} 
    
    
    
    
function snetpost_options_page() {

?>
<div>
<!--<h2>Mapworks settings</h2>
Options relating mapworks. -->
<form action="options.php" method="post">
<?php settings_fields('snetpost_plugin_options'); ?>
<?php do_settings_sections('snetpost_plugin'); ?>
 
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>

 
<?php
}
      
    
      


}

$MyPlugin = Snetpost::getInstance();
$MyPlugin->InitPlugin();
