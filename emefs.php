<?php
/*
Plugin Name: Events Made Easy Frontend Submit
Plugin URI: http://www.e-dynamics.be/wordpress
Description: Displays a form to allow people to enter events for the Events Made Easy plugin on a regular wordpress page.
Author: Franky Van Liedekerke
Version: 1.0.9
Author URI: http://www.e-dynamics.be/wordpress
License: GNU General Public License
*/

define( 'EMEFS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EMEFS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*
  Default Data used by the plugin
 */
$emefs_event_data = array();

$emefs_event_errors = array(
	"event_name" => false,
	"event_status" => false,
	"event_start_date" => false,
	"event_end_date" => false,
	"event_start_time" => false,
	"event_end_time" => false,
	"event_time" => false,
	"event_rsvp" => false,
	"rsvp_number_days" => false,
	"registration_requires_approval" => false,
	"registration_wp_users_only" => false,
	"event_seats" => false,
	"event_author" => false,
	"event_notes" => false,
	'event_page_title_format' => false,
	'event_single_event_format' => false,
	'event_contactperson_email_body' => false,
	'event_respondent_email_body' => false,
	'event_url' => false,
	'event_category_ids' => false,
	'event_attributes' => false,
	'location_id' => false,
	'location_name' => false,
	'location_address' => false,
	'location_town' => false,
	'location_latitude' => false,
	'location_longitude' => false,
);

$emefs_has_errors = false;

// don't load directly
if (!function_exists('is_admin')) {
   header('Status: 403 Forbidden');
   header('HTTP/1.1 403 Forbidden');
   exit();
}

if (!class_exists("EMEFS")) :
class EMEFS {

	/*
	 Load the options, set up hooks, and all, on the condition that EME is activated as well.
	 */
   var $settings;
	
   function __construct() {
      if ((function_exists('is_multisite') && is_multisite() && array_key_exists('events-made-easy/events-manager.php',get_site_option('active_sitewide_plugins'))) || in_array('events-made-easy/events-manager.php', apply_filters('active_plugins', get_option( 'active_plugins' )))) {
         add_action('init', array($this,'init') );
         register_activation_hook( __FILE__, array($this,'activate') );
         register_deactivation_hook( __FILE__, array($this,'deactivate') );
      } else {
         add_action('admin_notices', array(__CLASS__, 'do_dependencies_notice'));
      }
   }

   function network_propagate($pfunction, $networkwide) {
      global $wpdb;

      if (function_exists('is_multisite') && is_multisite()) {
         // check if it is a network activation - if so, run the activation function 
         // for each blog id
         if ($networkwide) {
            $old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            foreach ($blogids as $blog_id) {
               switch_to_blog($blog_id);
               call_user_func($pfunction, $networkwide);
            }
            switch_to_blog($old_blog);
            return;
         }
      }
      call_user_func($pfunction, $networkwide);
   }

   function activate($networkwide) {
      $this->network_propagate(array($this, '_activate'), $networkwide);
   }

   function deactivate($networkwide) {
      $this->network_propagate(array($this, '_deactivate'), $networkwide);
   }

   /*
      Enter our plugin activation code here.
    */
   function _activate() {}

   /*
      Enter our plugin deactivation code here.
    */
   function _deactivate() {}

   function init() {
      load_plugin_textdomain( 'emefs', EMEFS_PLUGIN_DIR . 'lang',
            basename( dirname( __FILE__ ) ) . '/lang' );
      // Load settings page
      if (!class_exists("emefs_settings"))
         require(EMEFS_PLUGIN_DIR . 'emefs-settings.php');
      $this->settings=new EMEFS_Settings();
      if (!is_admin()) {
         add_action('template_redirect', array($this, 'pageHasForm'));
         $this->processForm();
         self::registerAssets();
      }
   }

   function pageHasForm() { 
      global $wp_query;
      if ( is_page() || is_single() ) {
         $post = $wp_query->get_queried_object();
         if ( false !== strpos($post->post_content, '[emefs_submit_event_form]') ) {
            if(!$this->settings->options['guest_submit'] && !current_user_can('edit_posts')){
               wp_redirect(get_permalink($this->settings->options['guest_not_allowed_page']));
            }

            // Display Form Shortcode & Wrapper
            add_shortcode( 'emefs_submit_event_form', array($this, 'deployForm'));

            // Scripts and Styles 
            add_action( 'wp_print_scripts', array(__CLASS__, 'printScripts') );
            add_action( 'wp_print_styles', array(__CLASS__, 'printStyles') );
         }
      }
   }

   /*
      Tell the user to activate EME before using EMEFS
    */
   public static function do_dependencies_notice() {
      $message = __( "The Events Made Easy Frontend Submit plugin is an extension to the Events Made Easy plugin, which has to be installed and activated first. The plugin has been deactivated.", 'emefs' );
      echo sprintf('<div class="error"><p>%s</p></div>', $message);
   }

   /*
      Processes the form submitted data
    */
   function processForm() {

      global $emefs_event_errors, $emefs_event_data, $emefs_has_errors;
      $eme_timezone=get_option('timezone_string');
      $eme_date_obj=new DateTime(null,$eme_timezone);

      if (!$this->settings->options['success_page']) {
         return false;
      }

      if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['event']['action'] ) && wp_verify_nonce( $_POST['new-event'], 'action_new_event' ) ) {

         $hasErrors = false;

         $event_data = $_POST['event'];

         if ( isset($event_data['event_name']) && !empty($event_data['event_name']) ) { 
            $event_data['event_name'] = esc_attr( $event_data['event_name'] );
         } else {
            $emefs_event_errors['event_name'] = __('Please enter a name for the event', 'emefs');
         }

         if ( isset($event_data['event_start_date']) && !empty($event_data['event_start_date']) ) { 
            $event_data['event_start_date'] = esc_attr( $event_data['event_start_date'] );
         } else {
            $emefs_event_errors['event_start_date'] = __('Enter the event\'s start date', 'emefs');
         }

         if ( isset($event_data['event_start_time']) && !empty($event_data['event_start_time']) ) { 
            $event_data['event_start_time'] = $eme_date_obj->setTimestamp(strtotime($event_data['event_start_time']))->format('H:i:00');
         } else {
            $event_data['event_start_time'] = '00:00';
         }

         if ( isset($event_data['event_end_time']) && !empty($event_data['event_end_time']) ) { 
            $event_data['event_end_time'] = $eme_date_obj->setTimestamp(strtotime($event_data['event_end_time']))->format('H:i:00');
         } else {
            $event_data['event_end_time'] = $event_data['event_start_time'];
         }

         if ( isset($event_data['event_end_date']) && !empty($event_data['event_end_date']) ) { 
            $event_data['event_end_date'] = esc_attr( $event_data['event_end_date'] );
         } else {
            $event_data['event_end_date'] = $event_data['event_start_date'];
         }

         $time_start = $eme_date_obj->setTimestamp(strtotime($event_data['event_start_date'].' '.$event_data['event_start_time']))->getTimestamp();
         $time_end = $eme_date_obj->setTimestamp(strtotime($event_data['event_end_date'].' '.$event_data['event_end_time']))->getTimestamp();

         if(!$time_start){
            $emefs_event_errors['event_start_time'] = __('Check the start\'s date and time', 'emefs');
         }

         if(!$time_end){
            $emefs_event_errors['event_end_time'] =  __('Check the end\'s date and time', 'emefs');
         }

         if($time_start && $time_end && $time_start > $time_end){
            $emefs_event_errors['event_time'] =  __('The event\'s end must be <strong>after</strong> the event\'s start', 'emefs');
         }

         if ( isset($event_data['event_notes']) && !empty($event_data['event_notes']) ) { 
            $event_data['event_notes'] = esc_attr( $event_data['event_notes'] ); 
         } else { 
            $emefs_event_errors['event_notes'] = __('Please enter a description for the event', 'emefs'); 
         }

         if (get_option('eme_categories_enabled')) {
            if ( isset($event_data['event_category_ids']) && !empty($event_data['event_category_ids']) && $event_data['event_category_ids'] != 0 ) { 
               $event_data['event_category_ids'] = (int) esc_attr( $event_data['event_category_ids'] ); 
            } else { 
               $emefs_event_errors['event_category_ids'] = __('Please select an Event Category', 'emefs');
            }
         }

         foreach ($emefs_event_errors as $error) {
            if($error){
               $emefs_has_errors = true;
               break;
            }	
         }

         // after submit, not all event fields are present, so we will merge the submitted data with a new event
         $new_event = eme_new_event();

         if ( !$emefs_has_errors ) {

            $force=0;
            if ($this->settings->options['force_location_creation'])
               $force=1;

            $event_data = self::processLocation($event_data, $force);
            $event_data['event_contactperson_email_body'] = esc_attr( $event_data['event_contactperson_email_body'] );
            $event_data['event_url'] = esc_url( $event_data['event_url'] );

            $emefs_event_data_compiled = array_merge($emefs_event_data, $event_data);
            $emefs_event_data_compiled = array_merge($new_event,$emefs_event_data_compiled);
            unset($emefs_event_data_compiled['action']);

            foreach ($emefs_event_data_compiled as $key => $value) {
               // location info is not part of the event
               if (strpos($key,'location') !== false && $key != 'location_id') {
                  unset($emefs_event_data_compiled[$key]);
                  $location_data[$key] = $value;
               }
               // localised info is not part of the event
               if (strpos($key,'localised-') !== false) {
                  unset($emefs_event_data_compiled[$key]);
               }
            }

            if ($this->settings->options['auto_publish']) {
               $emefs_event_data_compiled['event_status'] = $this->settings->options['auto_publish'];
            }

            if (is_user_logged_in()) {
               $current_userid=get_current_user_id();
               $emefs_event_data_compiled['event_author'] = $current_userid;
            }

            if ($event_id = eme_db_insert_event($emefs_event_data_compiled)) {
               if (is_user_logged_in() && $this->settings->options['auto_publish']!=STATUS_DRAFT) {
                  wp_redirect(html_entity_decode(eme_event_url(eme_get_event($event_id))));
               } elseif (!is_user_logged_in() && $this->settings->options['auto_publish']==STATUS_PUBLIC) {
                  wp_redirect(html_entity_decode(eme_event_url(eme_get_event($event_id))));
               } else {
                  wp_redirect(get_permalink($this->settings->options['success_page']));
               }
               exit;
            } else {
               $emefs_has_errors = true;
            }

         } else {
            $emefs_event_data = array_merge($emefs_event_data, $event_data);	
            $emefs_event_data = array_merge($new_event,$emefs_event_data);
         }
      }
   }

   /* 
      Process the data for a new location
    */
   public static function processLocation($event_data, $force=0) {

      if ( isset($event_data['location_name']) && '' != $event_data['location_name'] ) {
         $event_data['location_name'] = esc_attr( $event_data['location_name'] );
      }

      if ( isset($event_data['location_address']) && '' != $event_data['location_address'] ) {
         $event_data['location_address'] = esc_attr( $event_data['location_address'] );
      }

      if ( isset($event_data['location_town']) && '' != $event_data['location_town'] ) {
         $event_data['location_town'] = esc_attr( $event_data['location_town'] );
      }

      if ( !empty($event_data['location_name']) && !empty($event_data['location_address']) && !empty($event_data['location_town'])) {

         $location = eme_get_identical_location($event_data);
         if ( !$location['location_id'] ) {
            $location = array (
                  'location_name' => $event_data['location_name'],
                  'location_address' => $event_data['location_address'],
                  'location_town' => $event_data['location_town'],
                  'location_latitude' => $event_data['location_latitude'],
                  'location_longitude' => $event_data['location_longitude'],
                  );
            $location = eme_insert_location($location, $force);
         }

         $event_data['location_id'] = $location['location_id'];
      }
      return $event_data;
   }

   /*
      Print out the Submitting Form
    */
   function deployForm($atts, $content) {
      global $emefs_event_errors, $emefs_event_data;

      if (empty($emefs_event_data)) {
            if (function_exists('eme_new_event')) {
               $emefs_event_data=eme_new_event()+eme_new_location();
               $emefs_event_data["event_status"] = 5;
               // the following 2 fields are not a part of an event, but are needed to prevent php notice errors when showing the form for the first time
               $emefs_event_data["localised-start-date"] = '';
               $emefs_event_data["localised-end-date"] = '';
            } else {
            ?>
               <div class="emefs_error">
               <h2><?php _e('Events Made Easy plugin missing', 'emefs'); ?></h2>
               <p><?php _e("This plugin requires the plugin 'Events Made Easy' to be installed.", 'emefs'); ?></p>
               </div>
            <?php
            }
      }
      if (!$this->settings->options['success_page']) {
      ?>
            <div class="emefs_error">
            <h2><?php _e('Basic Configuration is Missing', 'emefs'); ?></h2>
            <p><?php _e('You have to configure the page where successful submissions will be redirected to.', 'emefs'); ?></p>
            </div>
      <?php
            return false;
      }

      if (!$this->settings->options['guest_submit'] && !$this->settings->options['guest_not_allowed_page']) {
      ?>
            <div class="emefs_error">
            <h2><?php _e('Basic Configuration is Missing', 'emefs'); ?></h2>
            <p><?php _e('Since you have chosen not to accept guest submissions, you have to configure the page where to redirect unauthorized users.', 'emefs'); ?></p>
            </div>
      <?php
            return false;
      }

      $filename = locate_template(array(
               'eme-frontend-submit/form.php',
               'events-made-easy-frontend-submit/form.php',
               'events-made-easy/form.php',
               'emefs/form.php'
               ));
      if (empty($filename)) {
         $filename = 'templates/form.php';
      }
      // check if the user wants AM/PM or 24 hour notation
      // make sure that escaped characters are filtered out first
      $time_format = preg_replace('/\\\\./','',get_option('time_format'));
      $show24Hours = 'true';
      if (preg_match ( "/g|h/", $time_format ))
         $show24Hours = 'false';
      $gmap_enabled=1;
      if (!$this->settings->options['gmap_enabled'])
         $gmap_enabled=0;

      ob_start();
      require($filename);
      ?>
      <script type="text/javascript">
         jQuery(document).ready( function(){
               emefs_autocomplete_url = "<?php echo EMEFS_PLUGIN_URL; ?>emefs-locations-search.php";
               emefs_gmap_enabled = <?php echo $gmap_enabled; ?>;
               show24Hours = <?php echo $show24Hours; ?>;
               emefs_gmap_hasSelectedLocation = <?php echo ($emefs_event_data['location_id'])?'1':'0'; ?>;
               emefs_deploy(emefs_autocomplete_url, show24Hours, emefs_gmap_enabled, emefs_gmap_hasSelectedLocation);
               });
      </script>
      <?php
      $form = ob_get_clean();
      return $form;
   }

   /*
      Print fields which act as security and blocking methods
      preventing unwanted submitions.
    */
   public static function end_form($submit = 'Submit Event') {
      echo sprintf('<input type="submit" value="%s" id="submit" />', __($submit));
      echo '<input type="hidden" name="event[action]" value="new_event" />';
      wp_nonce_field( 'action_new_event', 'new-event' );
   }

   /*
      Print event data fields (not location data)
    */
   public static function field($field = false, $type = 'text', $field_id = false, $more = null) {
      global $emefs_event_data;

      //if (!$field || !isset($emefs_event_data[$field]))
      if (!$field)
         return false;

      if (is_array($field)) {
         $field = $field[0];
         $context = $field[1]; 
      } else {
         $context = 'event';
      }

      $localised_field_id='';
      switch($field) {
         case 'event_notes':
            $type = 'textarea';
            $more = "required='required'";
            break;
         case 'event_category_ids':
            $type = ($type != 'radio')?'select':'radio';
            break;
         case 'location_latitude':
         case 'location_longitude':
            $type = 'hidden';
            break;
         case 'event_start_date':
            $localised_field_id='localised-start-date';
            $more = "required='required'";
            $type = 'localised_text';
            break;
         case 'event_end_date':
            $localised_field_id='localised-end-date';
            $type = 'localised_text';
            break;
         case 'event_name':
            $more = "required='required'";
            $type = 'text';
            break;
         default:
            $type = 'text';
      }

      $html_by_type = array(
            'text' => '<input type="text" id="%s" name="event[%s]" value="%s" %s/>',
            'localised_text' => '<input type="text" id="%s" name="%s" value="%s" %s/>',
            'textarea' => '<textarea id="%s" name="event[%s]" %s>%s</textarea>',
            'hidden' => '<input type="hidden" id="%s" name="event[%s]" value="%s" %s />',
            );

      $field_id = ($field_id)?$field_id:$field;

      switch($type) {
         case 'textarea':
            echo sprintf($html_by_type[$type], $field_id, $field, $more, $emefs_event_data[$field]);
            break;
         case 'text':
         case 'hidden':
            echo sprintf($html_by_type[$type], $field_id, $field, $emefs_event_data[$field], $more);
            //echo sprintf($html_by_type[$type], $field_id, $field, '', $more);
            break;
         case 'localised_text':
            //echo sprintf($html_by_type['hidden'], $field_id, $field, '', $more);
            echo sprintf($html_by_type['hidden'], $field_id, $field, $emefs_event_data[$field], $more);
            echo sprintf($html_by_type[$type], $localised_field_id, "event[$localised_field_id]", $emefs_event_data[$localised_field_id], $more);
            break;
         case 'select':
            echo self::getCategoriesSelect($more);
            break;
         case 'radio':
            echo self::getCategoriesRadio($more);
            break;
      }
   }

   /*
      Print event data fields error messages (not location data)
    */
   public static function error($field = false, $html = '<span class="error">%s</span>') {
      global $emefs_event_errors;
      if (!$field || !$emefs_event_errors[$field])
         return false;
      echo sprintf($html, $emefs_event_errors[$field]);
   }

   /*
      Wrapper function to get categories form eme
    */
   public static function getCategories() {
      $categories = eme_get_categories();
      if (has_filter('emefs_categories_filter')) $categories=apply_filters('emefs_categories_filter',$categories);
      return($categories);
   }

   /*
      Function that creates and returns a radio input set from the existing categories
    */
   public static function getCategoriesRadio($more) {
      global $emefs_event_data;

      $categories = self::getCategories();
      $category_radios = array();
      if ( $categories ) {
         $category_radios[] = '<input type="hidden" name="event[event_category_ids]" value="0" '.$more.' />';
         foreach ($categories as $category){
            $checked = ($emefs_event_data['event_category_ids'] == $category['category_id'])?'checked="checked"':'';
            $category_radios[] = sprintf('<input type="radio" id="event_category_ids_%s" value="%s" name="event[event_category_ids]" %s />', $category['category_id'], $category['category_id'], $checked);
            $category_radios[] = sprintf('<label for="event_category_ids_%s">%s</label><br/>', $category['category_id'], $category['category_name']);
         }
      }

      return implode("\n", $category_radios);	
   }

   /*
      Print what self::getCategoriesRadio returns
    */
   public static function categoriesRadio() {
      echo self::getCategoriesRadio();
   }

   /*
      Function that creates and returns a select input set from the existing categories
    */
   public static function getCategoriesSelect($more) {
      global $emefs_event_data;

      $category_select = array();
      $category_select[] = '<select id="event_category_ids" name="event[event_category_ids]" '.$more.' >';
      $categories = self::getCategories();
      if ( $categories ) {
         $category_select[] = sprintf('<option value="%s">%s</option>', 0, '--');
         foreach ($categories as $category){
            $selected = ($emefs_event_data['event_category_ids'] == $category['category_id'])?'selected="selected"':'';
            $category_select[] = sprintf('<option value="%s" %s>%s</option>', $category['category_id'], $selected, $category['category_name']);
         }
      }
      $category_select[] = '</select>';
      return implode("\n", $category_select);
   }

   /*
      Sets up style and scripts assets the plugin uses
    */
   public static function registerAssets() {

      wp_register_script( 'jquery-plugin', EME_PLUGIN_URL.'js/jquery-datepick/jquery.plugin.min.js');
      wp_register_script( 'jquery-datepick',EME_PLUGIN_URL.'js/jquery-datepick/jquery.datepick.js',array( 'jquery','jquery-plugin' ));
      wp_register_script( 'jquery-mousewheel', EME_PLUGIN_URL.'js/jquery-mousewheel/jquery.mousewheel.min.js', array('jquery'));
      wp_register_script( 'jquery-timeentry', EME_PLUGIN_URL.'js/timeentry/jquery.timeentry.js', array('jquery','jquery-plugin','jquery-mousewheel'));

      // we don't have '$this' here yet, so get the option values the other way
      $settings = new EMEFS_Settings();
      if ($settings->options['gmap_enabled']) {
         wp_register_script( 'google-maps', 'http://maps.google.com/maps/api/js?v=3.1&sensor=false');
         wp_register_script( 'emefs', EMEFS_PLUGIN_URL.'emefs.js', array('jquery-datepick', 'jquery-timeentry', 'jquery-ui-autocomplete', 'google-maps'));
      } else {
         wp_register_script( 'emefs', EMEFS_PLUGIN_URL.'emefs.js', array('jquery-datepick', 'jquery-timeentry', 'jquery-ui-autocomplete'));
      }
      $style_filename = locate_template(array(
               'eme-frontend-submit/style.css',
               'events-made-easy-frontend-submit/style.css',
               'emefs/style.css',
               'events-made-easy/style.css'
               ));

      if(empty($style_filename)){
         $style_filename = EMEFS_PLUGIN_URL.'templates/style.css';
      }else{
         $style_filename = get_bloginfo('url').'/'.str_replace(ABSPATH, '', $style_filename);
      }

      wp_register_style( 'emefs', $style_filename );
      wp_register_style( 'emefs-internal', EMEFS_PLUGIN_URL.'templates/style.internal.css');
      wp_register_style('jquery-datepick', EME_PLUGIN_URL.'js/jquery-datepick/jquery.datepick.css');
   }

   /*
      Deliver scripts for output on the theme 
    */
   public static function printScripts() {
      if (!is_admin()) {
         // jquery ui locales are with dashes, not underscores
         $locale_code = get_locale();
         $locale_code = preg_replace( "/_/","-", $locale_code );
         $firstDayOfWeek = get_option('start_of_week');

         // Now we can localize the script with our data.
         // in our case: replace in the registered script "emefs" the string emefs.locale by $locale_code, and emefs.firstDayOfWeek by $firstDayOfWeek
         $translation_array = array( 'locale' => $locale_code, 'firstDayOfWeek' => $firstDayOfWeek );
         wp_localize_script( 'emefs', 'emefs', $translation_array );
         wp_enqueue_script( 'emefs' );

         $locale_file = EME_PLUGIN_DIR. "js/jquery-datepick/jquery.datepick-$locale_code.js";
         $locale_file_url = EME_PLUGIN_URL. "js/jquery-datepick/jquery.datepick-$locale_code.js";
         // for english, no translation code is needed)
         if ($locale_code != "en-US") {
            if (!file_exists($locale_file)) {
               $locale_code = substr ( $locale_code, 0, 2 );
               $locale_file = EME_PLUGIN_DIR. "js/jquery-datepick/jquery.datepick-$locale_code.js";
               $locale_file_url = EME_PLUGIN_URL. "js/jquery-datepick/jquery.datepick-$locale_code.js";
            }
            if (file_exists($locale_file))
               wp_enqueue_script('jquery-datepick-locale',$locale_file_url);
         }
      }
   }

   /*
      Deliver styles for output on the theme 
    */
   public static function printStyles() {
      if (!is_admin()) {
         wp_enqueue_style('emefs');
         wp_enqueue_style('emefs-internal');
         wp_enqueue_style('jquery-datepick');
      }
   }

}
endif;

// Initialize our plugin object.
global $emefs;
if (class_exists("EMEFS") && !$emefs) {
   $emefs = new EMEFS();
}

