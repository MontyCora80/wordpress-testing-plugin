<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_Admin_Page_Manage_Ads extends Rposul_Exporter_Admin_Page {

    const MENU_SLUG = "rposul-exporter-manage-ads";

    function __construct($version) {
        parent::__construct($version);
        $this->page_title = "Anúncios";
        $this->menu_title = $this->page_title;
        $this->capability = "edit_others_pages";
        $this->menu_slug = Rposul_Exporter_Admin_Page_Manage_Ads::MENU_SLUG;
        $this->ad_obj = null;
    }

    public function page_styles() {
        parent::page_styles();
        wp_enqueue_style($this->page_hook_suffix, plugin_dir_url(__FILE__) . 'css/rposul-exporter-admin-ads.css', array(), $this->version, false);
    }
    
    public function page_scripts() {
        parent::page_scripts();
        wp_enqueue_script("{$this->page_hook_suffix}-js", plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-ads.js', array(
            'jquery'), $this->version, true);

        wp_localize_script("{$this->page_hook_suffix}-js", 'ADV_JS', array(
            'display_date_format' => RPOSUL_FRONT_END_DATE_FORMAT
        ));
    }

    public function page_actions() {
        parent::page_actions();
        switch (arr_get($_GET, 'action', false)) {
            case 'delete':
                $ad_id = arr_get($_GET, 'ad');
                if ($ad_id != null) {
                    $ad_obj = new Rposul_Advertisement(array('id' => $ad_id));
                    $ad_obj->delete();
                    $location = remove_query_arg(array('action', 'ad'), wp_unslash($_SERVER['REQUEST_URI']));
                    wp_redirect($location);
                    exit();
                }
            default:
                break;
        }
        $screen = get_current_screen();
        /* @var $screen WP_Screen */

        $screen->add_help_tab(array(
            'id' => 'list_overview',
            'title' => 'Visão Geral',
            'content' => '<p>Nessa tela é possível gerenciar os anúncios que serão inseridos no momento da criação do PDF. '
            . 'Os anúncios são inseridos na edição durante no momento de inicialização da construção da escolha de posts '
            . 'para uma determinada edição. Caso um anúncio seja adicionado após o início da seleção de posts de um determinado dia '
            . 'é necessário requisitar a recolocação manual dos anúncios na tela de seleção de posts.</p>'));

        $screen->add_help_tab(array(
            'id' => 'list_filter',
            'title' => 'Busca',
            'content' => '<p>Inicialmente todos os anúncios criados são visualizados na lista. Caso o usuário queira filtar '
            . 'os anúncios criados por data é possível fazê-lo digitando uma data no modelo "dd-mm-yy" (Ex: 30-12-16) no campo "Buscar Data". '
            . 'Caso tal campo esteja vazio ou o valor inserido seja inválido todos os anúncios serão mostrados novamente.</p>'
            . '<p>Se javascript estiver habilitado no browser ao clicar na caixa de texto irá aparecer um calendário, facilitando '
            . 'a seleção da data a ser buscada.</p>'));

        add_screen_option('per_page', array(
            'default' => 20,
            'option' => 'rposul_advertisement_per_page'));
    }

    public function display() {
        $ads_table = new Rposul_Exporter_Admin_Page_Manage_Ads_Table();
        $ads_table->prepare_items();
        require (plugin_dir_path(__FILE__) . "partials/plugin-rposul-exporter-manage-ads.php");
    }

}

if (!class_exists('WPext_List_Table')) {
    require_once(plugin_dir_path(__FILE__) . "../includes/class-wpext-list-table.php");
}

class Rposul_Exporter_Admin_Page_Manage_Ads_Table extends WPext_List_Table {

    function __construct() {
        //Set parent defaults
        parent::__construct(array(
            'singular' => 'ad', //singular name of the listed records
            'plural' => 'ads', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    function get_columns() {
        $columns = array(
            'id' => __("ID"),
            'title' => __("Title"),
            'startdate' => "Data inicial",
            'enddate' => "Data final",
            'page' => __("Page"),
            'type' => __("Type"),
            'reocurrence' => "Recorrência",
            'has_custom_dates' => "Datas customizadas",
        );
        return $columns;
    }

    function column_default($item, $column_name) {
        /* @var $item Rposul_Advertisement */
        switch ($column_name) {
            case 'title':

                //Build row actions
                $delete_link = add_query_arg(
                        array('action' => 'delete', 'ad' => $item->get_id()), wp_unslash($_SERVER['REQUEST_URI'])
                );
                $edit_link = sprintf("?page=%s&amp;ad=%s", Rposul_Exporter_Admin_Page_Edit_Ads::MENU_SLUG, $item->get_id());
                $actions = array(
                    'edit' => sprintf('<a href="%s">%s</a>', $edit_link, __("Edit")),
                    'delete' => sprintf('<a href="%s">%s</a>', $delete_link, __("Delete")),
                );

                $title_link = sprintf("<strong><a class='' href='%s'</a>%s</strong>", $edit_link, $item->title);
                //Return the title contents
                return sprintf('%1$s%2$s',
                        /* $1%s */ $title_link,
                        /* $2%s */ $this->row_actions($actions)
                );

            case 'startdate':
                return $item->startdate->format(Rposul_Advertisement::DATE_FORMAT);
            case 'id':
                return $item->get_id();
            case 'type':
                return Rposul_Advertisement::$TYPE_OPTIONS[$item->type];
            case 'page':
                return $item->page;
            case 'enddate':
                if ($item->schedule != 'once' && $item->schedule != 'custom') {
                    return $item->enddate->format(Rposul_Advertisement::DATE_FORMAT);
                } else {
                    return '';
                }
            case 'has_custom_dates':
                if (!empty($item->include) || !empty($item->exclude)) {
                    return '<span class="dashicons dashicons-yes"></span>';
                } else {
                    return "";
                }
            case 'reocurrence':
                return $item->get_summary();
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title' => array('title', false), //true means it's already sorted
            'startdate' => array('startdate', false),
            'enddate' => array('enddate', false),
            'id' => array('id', true),
            'page' => array('page', false),
        );
        return $sortable_columns;
    }

    function prepare_items() {
        $per_page = $this->get_items_per_page('rposul_advertisement_per_page');
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $args = array();
        if (!empty($_REQUEST['orderby'])) {
            $order = !empty($_REQUEST['order']) ? esc_sql($_REQUEST['order']) : 'ACS';
            $args["ORDER BY"] = $_REQUEST['orderby'] . ' ' . $order;
        }

        if (empty($args["ORDER BY"])) {
            $args["ORDER BY"] = 'id DESC';
        }

        $args["LIMIT"] = $per_page;

        $current_page = $this->get_pagenum();
        $args["OFFSET"] = ( $current_page - 1 ) * $per_page;

        $date = null;
        if (!empty($_REQUEST['s'])) {
            $date_text_field = sanitize_text_field($_REQUEST['s']);
            $date = DateTime::createFromFormat(RPOSUL_FRONT_END_DATE_FORMAT, $date_text_field);
        }

        if (!empty($date)) {
            $data = Rposul_Advertisement::get_from_today($args, $date);
            $total_items = Rposul_Advertisement::get_count_from_today(array(), $date);
        } else {
            $data = Rposul_Advertisement::get($args);
            $total_items = Rposul_Advertisement::count();
        }


        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

}
