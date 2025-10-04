<?php

abstract class Rposul_Exporter_Admin_Page {

    abstract function display();

    var $page_title;
    var $menu_title;
    var $capability;
    var $menu_slug;
    var $page_hook_suffix;
    var $version;
    var $is_menu_accessible;

    /* TODO we arent using this in our model
     * function hooks_init() {
      add_action('admin_menu', array($this, 'add_page'));
      } */

    public function __construct($version, $is_menu_accessible = true) {
        $this->version = $version;
        $this->is_menu_accessible = $is_menu_accessible;
    }

    function add_page() {
        $this->page_hook_suffix = add_submenu_page($this->is_menu_accessible ? RPOSUL_MENU_SLUG : null, $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, array($this, 'render_page'), 10);
        add_action('load-' . $this->page_hook_suffix, array($this, 'page_actions'), 9);
        add_action('admin_print_scripts-' . $this->page_hook_suffix, array($this, 'page_styles'), 10);
        add_action('admin_print_styles-' . $this->page_hook_suffix, array($this, 'page_scripts'), 10);
        add_action("admin_footer-" . $this->page_hook_suffix, array($this, 'footer_scripts'));
    }

    function footer_scripts() {
        
    }

    function page_scripts() {
        
    }

    /*
     * Actions to be taken prior to page loading. This is after headers have been set.
     * @uses load-$hook
     */

    function page_actions() {
        
    }

    function page_styles() {
        
    }

    function render_page() {
        $this->display();
    }

}
