<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_Admin_Page_Main extends Rposul_Exporter_Admin_Page {

    const MENU_SLUG = "rposul-exporter";

    var $active_tab, $tabs, $server_state, $session_values;

    function __construct($version) {
        parent::__construct($version);

        $this->page_title = "Exportador PDF";
        $this->menu_title = "Criar PDF";
        $this->capability = "edit_others_pages";
        $this->menu_slug = self::MENU_SLUG;
        $this->active_tab = "";
        $this->session_values = array();
        $this->tabs = array(
            "general" => array('title' => "Geral", 'get' => 'newskeepdate=1'),
            "sections" => array('title' => "Seleção de Posts"),
            "cover" => array('title' => "Capa"),
            "generate" => array('title' => "Visualizar"),
            "advanced" => array('title' => "Avançado"));
    }

    public function page_scripts() {
        parent::page_scripts();

        switch ($this->active_tab) {
            case "sections":
                $handle = "{$this->page_hook_suffix}-post-selection-js";
                wp_register_script($handle, plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-post-selection.js', array(
                    'jquery', RPOSUL_PLUGIN_NAME . "-osul-widgets",
                    RPOSUL_PLUGIN_NAME . '-block-ui'
                        ), $this->version, false);

                $headers = Rposul_Header::get();
                $template_settings = array(
                    RPOSUL_TEMPLATEID_SMALL_NEWS => array('has_title' => true)
                );

                foreach ($headers as $key => $val) {
                    $search = array_search($val->name, Rposul_Exporter_Constants::$template_ids);
                    if ($search !== FALSE) {
                        if (array_key_exists($search, $template_settings)) {
                            $template_settings[$search]['auto_header'] = $val->get_id();
                        } else {
                            $template_settings[$search] = array('auto_header' => $val->get_id());
                        }
                    }
                }

                wp_localize_script($handle, 'post_selection_object', array(
                    'template_settings' => $template_settings,
                    'date_format' => 'yy-mm-dd'
                ));

                wp_enqueue_script($handle);
                break;
            case "generate":
                wp_enqueue_script("{$this->page_hook_suffix}-generate-js", plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-generate.js', array(
                    'jquery'), $this->version, true);
                break;
            default:
        }
    }

    public function page_styles() {
        parent::page_styles();
        switch ($this->active_tab) {
            case "sections":
                wp_enqueue_style($this->page_hook_suffix . '-post-selection-style', plugin_dir_url(__FILE__) . 'css/rposul-exporter-admin-post-selection.css', array(), $this->version, false);
                break;
            default:
        }
    }

    public function page_actions() {
        parent::page_actions();

        $input_tab = filter_input(INPUT_GET, 'tab');
        if (in_array($input_tab, array_keys($this->tabs))) {
            $this->active_tab = $input_tab;
        } else {
            $this->active_tab = 'general';
        }
        $this->server_state = Rposul_Exporter_PyExporterComm::get_state();

        if (arr_get($this->server_state, 'running', false)) {
            add_action('admin_notices', function () {
                print_notice_e('error', true, "Uma criação de PDF está em andamento");
            });
        }
        /**
         * TABS ACTIONS
         */
        switch ($this->active_tab) {
            case "general":
                $this->do_tab_general_actions();
                break;
            case "sections":
                $this->do_tab_sections_actions();
                break;
            case "cover":
                $this->do_tab_cover_actions();
                break;
            case "generate":
                $this->do_tab_generate_actions();
                break;
            case "advanced":
                $this->do_tab_advanced_actions();
                break;
            default:
        }
        /**
         * END TABS ACTIONS
         */
        // DONE HERE because we want to ensure that the date was already changed if we are saving

        $retval = Rposul_Newspaper::check_ads();
        if (is_wp_error($retval) && in_array('missing', $retval->get_error_codes())) {
            add_action('admin_notices', function () {
                print_notice_e('warning', false, 'Os anúncios desta edição estão desatualizados.<br>Por favor recoloque todos os anúncios na edição atual indo em "Seleção de Posts -> Opções -> Recolocar anúncios"');
            });
        }


        if (!Rposul_Newspaper::is_configuring_expected_date()) {
            add_action('admin_notices', function () {
                $tomorrow = Rposul_Newspaper::get_expected_datetime();
                print_notice_e('info', false, 'A data configurada é diferente da padrão ' . $tomorrow->format(RPOSUL_DATE_FORMAT) . '.');
            });
        }
    }

    public function display() {
        require (plugin_dir_path(__FILE__) . "partials/plugin-rposul-exporter-main.php");
    }

    private function do_tab_general_actions() {
        if (!arr_get($this->server_state, 'running', false)) {
            if (isset($_REQUEST['rposul_exporter_main_submit'])) {

                if (isset($_POST['rposul_exporter_main_date'])) {
                    $news_date = $_POST['rposul_exporter_main_date'];
                } else {
                    $dtObject = new DateTime();
                    $news_date = $dtObject->format(RPOSUL_DATE_FORMAT);
                }
                Rposul_Newspaper::change_date($news_date);

                if (isset($_POST['rposul_exporter_double_date'])) {
                    RPOSUL_Options::update_option(RPOSUL_OPTION_DOUBLE_NEWS_DATE, true);
                } else {
                    RPOSUL_Options::update_option(RPOSUL_OPTION_DOUBLE_NEWS_DATE, false);
                }

                print_notice_e("success", true, 'Novas opções gerais salvas');
            } else if (!DISABLE_AUTOMATIC_NEWSPAPER_DATE_SETTER && !arr_get($_GET, 'newskeepdate', false)) {
                //Rposul_Newspaper::change_date();
            }
        } else {
            print_notice_e("error", true, 'Não é possível alterar configurações gerais durante a geração de uma edição.');
        }
    }

    private function do_tab_sections_actions() {
        if (arr_get($_POST, 'reset-ads', False)) {
            Rposul_Newspaper::reset_ads(null, true);
        }
        if (arr_get($_POST, 'reset-insertions', False)) {
            Rposul_Newspaper::reset_insertions(null, true);
        }
        if (arr_get($_POST, 'clear-selection', False)) {
            Rposul_Newspaper::reset();
        }
    }

    private function do_tab_cover_actions() {
        
    }

    private function do_tab_generate_actions() {
        
    }

    private function do_tab_advanced_actions() {
        
    }

}
