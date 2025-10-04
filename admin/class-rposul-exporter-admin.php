<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Rposul_Exporter
 * @subpackage Rposul_Exporter/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rposul_Exporter
 * @subpackage Rposul_Exporter/admin
 * @author     Your Name <email@example.com>
 */
class Rposul_Exporter_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    0.1.0
     * @access   private
     * @var      string    $rposul_exporter    The ID of this plugin.
     */
    private $rposul_exporter;

    /**
     * The version of this plugin.
     *
     * @since    0.1.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.1.0
     * @param      string    $rposul_exporter       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($rposul_exporter, $version) {

        $this->plugin_name = $rposul_exporter;
        $this->version = $version;
    }

    public function init() {
        add_image_size('osul_columnist', 120, 120, false); //mobile
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.1.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Rposul_Exporter_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Rposul_Exporter_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css');
        wp_enqueue_style($this->plugin_name . '-jquery-ui', 'http://code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css', false, "1.12.0", false);
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/rposul-exporter-admin.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name . '-image-picker', plugin_dir_url(__FILE__) . 'css/image-picker.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.1.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Rposul_Exporter_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Rposul_Exporter_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_media();
        wp_enqueue_script('thb_media_selector', plugin_dir_url(__FILE__) . 'js/media_selector.js', array('jquery'));
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-autocomplete');
        wp_enqueue_script('jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_script('jquery-ui-button');
        wp_enqueue_script('jquery-touch-punch');
        wp_enqueue_script($this->plugin_name . '-osul-widgets', plugin_dir_url(__FILE__) . 'js/jquery.osulWidgets.js', array('jquery-ui-autocomplete'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-osul-extensions', plugin_dir_url(__FILE__) . 'js/jquery.osulExtensions.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-block-ui', plugin_dir_url(__FILE__) . 'js/jquery.blockUI.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-are-you-sure', plugin_dir_url(__FILE__) . 'js/jquery.are-you-sure.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-image-picker', plugin_dir_url(__FILE__) . 'js/image-picker.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin.js', array('jquery', $this->plugin_name . '-image-picker', 'thb_media_selector', 'jquery-ui-datepicker', 'jquery-ui-tooltip', 'jquery-ui-sortable', 'jquery-touch-punch', 'jquery-ui-button', 'jquery-ui-progressbar'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-admin-cover-form', plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-cover-form.js', array('jquery', 'jquery-ui-progressbar', 'jquery-ui-datepicker', $this->plugin_name . '-osul-widgets'), $this->version, false);
    }

    public function create_menus() {

        add_menu_page("Exportador PDF", "Exportador PDF", "edit_others_pages", "rposul-exporter");

        $pages = array();
        $pages[] = new Rposul_Exporter_Admin_Page_Main($this->version);
        $pages[] = new Rposul_Exporter_Admin_Page_Manage_Ads($this->version);        
        $pages[] = new Rposul_Exporter_Admin_Page_Manage_Insertions($this->version);
        $pages[] = new Rposul_Exporter_Admin_Page_Edit_Ads($this->version);
        $pages[] = new Rposul_Exporter_Admin_Page_Manage_Headers($this->version);

        foreach ($pages as $page) {
            $page->add_page();
        }
    }

    function set_screen_options($result, $option, $value) {
        $wpcf7_screens = array(
            'rposul_advertisement_per_page');

        if (in_array($option, $wpcf7_screens)) {
            $result = $value;
        }

        return $result;
    }

    /**
     * Print notice on all plugin pages when constants are set to debug environment
     */
    public function global_admin_notices() {
        if (is_rposul_page()) {
            //This combination of variables indicate a debug environment
            if (RPOSUL_IS_DEBUG || USE_LOCAL_SERVER || !USE_PAMPA_SERVER) {
                print_notice_e("warning", false, "Configurações do plugin em modo DEBUG. Entre em contato com o desenvolvedor.");
            }
        }
    }

    public function char_counter() {
        ?>
        <script type="text/javascript">
            (function ($) {
                wpCharCount = function (txt) {
                    $('.char-count').html("" + txt.length);
                };
                $(document).ready(function () {
                    $('#wp-word-count').append('<br />Caracteres estimados: <span class="char-count">0</span>');
                }).bind('wpcountwords', function (e, txt) {
                    wpCharCount(txt);
                });
                $('#content').bind('keyup', function () {
                    wpCharCount($('#content').val());
                });
            }(jQuery));
        </script>
        <?php

    }

    public function save_post($post_id, $post, $update) {
        if ($update) {
            update_post_meta($post_id, RPOSUL_CONTENT_LENGTH_POST_META, rposul_retrieve_post_content_length($post_id, true));
        } else {
            add_post_meta($post_id, RPOSUL_CONTENT_LENGTH_POST_META, rposul_retrieve_post_content_length($post_id, true), true);
        }
    }

}
