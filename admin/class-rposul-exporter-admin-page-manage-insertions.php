<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_Admin_Page_Manage_Insertions extends Rposul_Exporter_Admin_Page {

    const MENU_SLUG = "rposul-exporter-manage-insertions";

    function __construct($version) {
        parent::__construct($version);
        $this->page_title = "Editorias";
        $this->menu_title = $this->page_title;
        $this->capability = "edit_others_pages";
        $this->menu_slug = Rposul_Exporter_Admin_Page_Manage_Insertions::MENU_SLUG;
    }

    public function page_scripts() {
        parent::page_scripts();
        wp_enqueue_media();
        wp_enqueue_script("{$this->page_hook_suffix}-js", plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-insertions.js', array(
            'jquery', RPOSUL_PLUGIN_NAME . "-osul-widgets"), $this->version, false);

        wp_localize_script("{$this->page_hook_suffix}-js", 'INSERTIONS_JS', array(
            'imageplaceholder' => RPOSUL_PLACEHOLDER_DEFAULT_IMAGE
        ));
    }

    public function page_actions() {
        parent::page_actions();
        switch (arr_get($_GET, 'action', false)) {
            case 'delete':
                $insertion_id = arr_get($_GET, 'insertion');                
                if ($insertion_id != null) {
                    Rposul_Insertion::delete_by_id($insertion_id);
                    $location = remove_query_arg(array('action', 'insertion'), wp_unslash($_SERVER['REQUEST_URI']));
                    wp_redirect($location);
                    exit();
                }
            default:
                break;
        }

        if (arr_get($_POST, 'clear-insertions', false)) {
            Rposul_Insertion::truncate_table();
        }

        if (arr_get($_POST, 'create-insertion', false)) {
            $new_insertion = new Rposul_Insertion();
            $new_insertion->name = arr_get($_POST['insertion'], 'name', '');
            $new_insertion->page = arr_get($_POST['insertion'], 'page', 2);
            $same_page_insertion = Rposul_Insertion::get(array('WHERE' => "page={$new_insertion->page}"));
            if ($same_page_insertion) {
                add_action('admin_notices', function () {
                    print_notice_e('error', false, "Já existe um editorial na página selecionada");
                });
            } else {
                if (isset($_POST['imageselector']) && arr_get($_POST['imageselector'], 'imagepicker')) {
                    $new_insertion->attachment_id = $_POST['imageselector']['imagepicker'];
                    $new_insertion->save();
                } else {
                    add_action('admin_notices', function () {
                        print_notice_e('error', false, 'É necessário selecionar uma imagem para o editorial.');
                    });
                }
            }
        }
    }

    public function display() {
        $insertions_table = new Rposul_Exporter_Admin_Page_Manage_Insertions_Table();
        $insertions_table->prepare_items();
        require (plugin_dir_path(__FILE__) . "partials/plugin-rposul-exporter-manage-insertions.php");
    }

}

if (!class_exists('WPext_List_Table')) {
    require_once(plugin_dir_path(__FILE__) . "../includes/class-wpext-list-table.php");
}

class Rposul_Exporter_Admin_Page_Manage_Insertions_Table extends WPext_List_Table {

    function __construct() {
        //Set parent defaults
        parent::__construct(array(
            'singular' => 'insertion', //singular name of the listed records
            'plural' => 'insertions', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    function get_columns() {
        $columns = array(
            'name' => __("Name"),
            'page' => __('Page'),
            'attachment' => "Imagem"
        );
        return $columns;
    }

    function column_default($item, $column_name) {
        /* @var $item Rposul_Columnist */
        switch ($column_name) {
            case 'name':

                //Build row actions
                $delete_link = add_query_arg(
                        array('action' => 'delete', 'insertion' => $item->get_id()), wp_unslash($_SERVER['REQUEST_URI'])
                );
                $actions = array(
                    'delete' => sprintf('<a href="%s">%s</a>', $delete_link, __("Delete")),
                );

                $title_link = sprintf("<strong>%s</strong>", $item->name);
                //Return the title contents
                return sprintf('%1$s%2$s',
                        /* $1%s */ $title_link,
                        /* $2%s */ $this->row_actions($actions)
                );
            case 'page':
                return $item->page;
            case 'attachment':
                return wp_get_attachment_image($item->attachment_id, 'osul_columnist');
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        //$sortable = $this->get_sortable_columns();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = Rposul_Insertion::get();
    }

}
