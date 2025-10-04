<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_Admin_Page_Edit_Ads extends Rposul_Exporter_Admin_Page {

    const MENU_SLUG = "rposul-exporter-edit-ads";

    function __construct($version) {
        parent::__construct($version, false);

        $this->page_title = "Anúncios";
        $this->menu_title = $this->page_title;
        $this->capability = "edit_others_pages";
        $this->menu_slug = Rposul_Exporter_Admin_Page_Edit_Ads::MENU_SLUG;
        $this->ad_obj = null;
    }

    public function page_scripts() {
        parent::page_scripts();
        wp_enqueue_script($this->page_hook_suffix . '-event', plugin_dir_url(__FILE__) . 'js/event.js', array('jquery'), $this->version, false);

        global $wp_locale;
        wp_localize_script($this->page_hook_suffix . '-event', 'EO_Ajax_Event', array(
            'advcompatibility' => Rposul_Advertisement::$TYPE_COMPATIBILITY,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'wpversion' => get_bloginfo('version'),
            'startday' => intval(get_option('start_of_week')),
            'format' => Rposul_Advertisement::DATE_FORMAT,
            'is24hour' => true,
            'location' => get_option('timezone_string'),
            'locale' => array(
                'isrtl' => $wp_locale->is_rtl(),
                'monthNames' => array_values($wp_locale->month),
                'monthAbbrev' => array_values($wp_locale->month_abbrev),
                'dayAbbrev' => array_values($wp_locale->weekday_abbrev),
                'showDates' => __('Mostrar datas'),
                'hideDates' => __('Esconder datas'),
                'weekDay' => $wp_locale->weekday,
                'meridian' => array($wp_locale->get_meridiem('am'), $wp_locale->get_meridiem('pm')),
                'hour' => __('Hora'),
                'minute' => __('Minuto'),
                'day' => __('dia'),
                'days' => __('dias'),
                'week' => __('semana'),
                'weeks' => __('semanas'),
                'month' => __('mês'),
                'months' => __('meses'),
                'year' => __('ano'),
                'years' => __('anos'),
                'daySingle' => __('todos os dias'),
                'dayPlural' => __('a cada %d dias'),
                'weekSingle' => __('toda semana na'),
                'weekPlural' => __('a cada %d semanas na'),
                'monthSingle' => __('todo mês no dia'),
                'monthPlural' => __('a cada %d mêses no dia'),
                'yearSingle' => __('todos os anos'),
                'yearPlural' => __('a cada %d anos'),
                'summary' => __('Este evento se repetirá'),
                'until' => __('até'),
                'occurrence' => array(
                    __('primeira'),
                    __('segunda'),
                    __('terceira'),
                    __('quarta'),
                    __('última'),
                ),
            )
        ));

        wp_enqueue_script($this->page_hook_suffix . '-event-controler', plugin_dir_url(__FILE__) . 'js/event-controler.js', array('jquery', $this->page_hook_suffix . '-event'), $this->version, false);
    }

    public function page_actions() {
        parent::page_actions();

        if (isset($_GET['ad'])) {
            $advertisements = Rposul_Advertisement::get(array('WHERE' => "id={$_GET['ad']}"));
            $this->ad_obj = $advertisements[0];
        } else {
            $this->ad_obj = new Rposul_Advertisement();
        }

        $should_delete = isset($_POST['rposul_exporter_ads_submitted']) && isset($_POST['delete-ad']);
        $should_save = isset($_POST['rposul_exporter_ads_submitted']) && isset($_POST['publish-ad']);
        //$should_reload = !empty($_REQUEST['rposul-reload']);

        if ($this->ad_obj->is_new() || $should_save) {
            $this->ad_obj->import_from_post_data();
        }

        if ($should_delete) {
            $this->ad_obj->delete();
            $location = remove_query_arg(array('ad'), wp_unslash($_SERVER['REQUEST_URI']));
            wp_redirect($location);
            exit();
        }

        if ($should_save) {
            //Needs to come before should reload
            $this->ad_obj->save();
            add_action('admin_notices', function () {
                print_notice_e('success', true, 'Anúncio salvo com sucesso!');
            });
            wp_redirect(menu_page_url(Rposul_Exporter_Admin_Page_Manage_Ads::MENU_SLUG, false));
            exit();
        }

        //Keeping this code here only for reference. In the future we may delete this
        /* if ($should_reload) {
          //This means we are creating the add and should allow refresh by the user
          $location = remove_query_arg(array('rposul-reload'), wp_unslash($_SERVER['REQUEST_URI']));
          if ($get_ad_id == null) {
          //now we prefer to go to the manage table
          //$location = add_query_arg('ad', $this->ad_obj->get_id(), $location);
          $location = remove_query_arg('page', $location);
          $location = add_query_arg('page', Rposul_Exporter_Admin_Page_Manage_Ads::MENU_SLUG, $location);
          }
          wp_redirect($location);
          exit();
          } */
    }

    public function page_styles() {
        parent::page_styles();
        wp_enqueue_style($this->page_hook_suffix . '-event-style', plugin_dir_url(__FILE__) . 'css/event-style.css', array(), $this->version, false);
    }

    public function display() {
        global $wp_locale;
        //Get the starting day of the week
        $start_day = 1; //intval( get_option( 'start_of_week' ) );
        $ical_days = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');


        $php_format = Rposul_Advertisement::DATE_FORMAT;

        $id = $this->ad_obj->get_id();
        $title = $this->ad_obj->title;
        $schedule = $this->ad_obj->schedule;
        $start = $this->ad_obj->startdate;
        $frequency = $this->ad_obj->frequency;
        $schedule_meta = $this->ad_obj->meta;
        $until = $this->ad_obj->enddate;
        $include = $this->ad_obj->include;
        $exclude = $this->ad_obj->exclude;
        $image_id = $this->ad_obj->image_id;
        $section_id = $this->ad_obj->section_id;
        $type = $this->ad_obj->type;
        $ad_page = $this->ad_obj->page;
        $relative_placement = $this->ad_obj->relative_placement;
        $is_new = $this->ad_obj->is_new();
        $date_created = $this->ad_obj->date_created;
        $date_modified = $this->ad_obj->date_modified;

        if ('monthly' == $schedule) {
            $bymonthday = preg_match('/BYMONTHDAY=/', $this->ad_obj->meta);
            $occurs_by = ( $bymonthday ? 'BYMONTHDAY' : 'BYDAY' );
        } else {
            $occurs_by = '';
        }
        require (plugin_dir_path(__FILE__) . "partials/plugin-rposul-exporter-edit-ads.php");
    }

}
