<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Exporter_Admin_Page_Manage_Headers extends Rposul_Exporter_Admin_Page {

    const MENU_SLUG = "rposul-exporter-manage-headers";

    function __construct($version) {
        parent::__construct($version);
        $this->page_title = "Cabeçalhos";
        $this->menu_title = $this->page_title;
        $this->capability = "edit_others_pages";
        $this->menu_slug = self::MENU_SLUG;
        $this->current_headers = null;
    }

    public function page_scripts() {
        parent::page_scripts();
        wp_enqueue_media();
        wp_enqueue_script("{$this->page_hook_suffix}-js", plugin_dir_url(__FILE__) . 'js/rposul-exporter-admin-headers.js', array(
            'jquery', RPOSUL_PLUGIN_NAME . "-osul-widgets"), $this->version, true);

        wp_localize_script("{$this->page_hook_suffix}-js", 'HEADERS_JS', array(
            'imageplaceholder' => RPOSUL_PLACEHOLDER_DEFAULT_IMAGE
        ));
    }

    public function page_actions() {
        parent::page_actions();
        switch (arr_get($_GET, 'action', false)) {
            case 'delete':
                $header_id = arr_get($_GET, 'header');
                if ($header_id != null) {
                    Rposul_Header::delete_by_id($header_id);
                    $location = remove_query_arg(array('action', 'header'), wp_unslash($_SERVER['REQUEST_URI']));
                    wp_redirect($location);
                    exit();
                }
            default:
                break;
        }

        if (arr_get($_POST, 'create-header', false)) {
            $hpost = $_POST['header'];
            $new_header = new Rposul_Header();
            $new_header->name = sanitize_text_field(arr_get($hpost, 'name', ''));
            $new_header->type = sanitize_key(arr_get($hpost, 'type', 'single'));
            $new_header->headrule_enabled = isset($hpost['headrule']);
            $option = sanitize_key(arr_get($hpost, 'option_A_type'));
            $new_header->content[] = array(
                'type' => $option,
                'value' => $option == 'text' ? sanitize_text_field($hpost['text'][0]) : sanitize_text_field($hpost['image'][0])
            );

            if ($new_header->type != 'single') {
                $option = sanitize_key(arr_get($hpost, 'option_B_type'));
                $new_header->content[] = array(
                    'type' => $option,
                    'value' => $option == 'text' ? sanitize_text_field($hpost['text'][1]) : sanitize_text_field($hpost['image'][1])
                );
            }

            if (is_wp_error($new_header->save())) {
                add_action('admin_notices', function () {
                    print_notice_e('error', false, 'Não foi possível criar o cabeçalho.');
                });
            } else {
                add_action('admin_notices', function () {
                    print_notice_e('success', true, 'Cabeçalho salvo com sucesso!');
                });
            }
        }

        if (arr_get($_POST, 'set-default-header', false)) {
            $default_header = intval(sanitize_text_field((arr_get($_POST, 'default-header', ''))));
            $updated_value = false;
            if (!empty($default_header)) {
                $updated_value = RPOSUL_Options::update_option(RPOSUL_OPTION_DEFAULT_HEADER, $default_header);
            }

            if ($updated_value) {
                add_action('admin_notices', function () {
                    print_notice_e('success', true, 'Cabeçalho padrão alterado!');
                });
            } else {
                $old_value = RPOSUL_Options::get_option(RPOSUL_OPTION_DEFAULT_HEADER);                
                if ($old_value != $default_header) {
                    // We only fail if values are different
                    // update returns false when value isnt changed
                    add_action('admin_notices', function () {
                        print_notice_e('error', false, 'Não foi possível alterar o cabeçalho padrão.');
                    });
                }
            }
        }

        $screen = get_current_screen();
        /* @var $screen WP_Screen */

        $screen->add_help_tab(array(
            'id' => 'list_overview',
            'title' => 'Visão Geral',
            'content' => '<p>Nessa tela é possível gerenciar os cabeçalhos para as páginas no PDF.</p>'
            . '<p>Para criar um novo cabeçalho clique no botão adicionar localizado no topo da página ou role '
            . 'até o final da página e preencha o formulário de criação de cabeçalhos. '
            . 'Quando criado um cabeçalho estará sempre habilitado e pode ser desabilitado clicando no botão disponível '
            . 'na coluna "Habilitado"</p> '
            . '<p><b>Escolha automática de cabeçalho:</b> Caso o cabeçalho possua o mesmo nome de um layout de página '
            . 'este será utilizado como padrão para este layout. Caso o layout não possua um cabeçalho com o nome '
            . 'correspondente, então o padrão será utilizado.</p>'));

        $screen->add_help_tab(array(
            'id' => 'list_types',
            'title' => 'Tipos de cabeçalho',
            'content' => '<p>Tipos de cabeçalhos</p>'
            . '<ul>'
            . '<li><strong>Simples:</strong> Permite a inserção de apenas 1 conteúdo '
            . 'no cabeçalho. No caso do conteúdo ser uma imagem essa imagem será inserida com 100% da '
            . 'largura de texto disponível.</li>'
            . '<li><strong>Duplos:</strong> Permite a inserção de dois conteúdos diferentes no cabeçalho.</li>'
            . '</ul>'));

        $screen->add_help_tab(array(
            'id' => 'list_image',
            'title' => 'Conteúdo: Imagem',
            'content' => '<p>O conteúdo do tipo imagem permite inserir uma imagem no cabeçalho.</p>'
            . '<p>A altura do cabeçalho será alterada para se adaptar a altura da imagem utilizada, mantendo as '
            . 'proporções originais da mesma. Já a largura da imagem irá variar dependendo do tipo de cabeçalho '
            . 'selecionado:</p>'
            . '<ul>'
            . '<li><strong>Simples:</strong> A imagem irá possuir 100% da largura da página.'
            . '<li><strong>Duplo:</strong> A imagem irá possuir 33% da largura da página.'
            . '</ul>'));

        $screen->add_help_tab(array(
            'id' => 'list_text',
            'title' => 'Conteúdo: Texto',
            'content' => '<p>O conteúdo do tipo texto permite inserir texto no cabeçalho. Algumas tags especiais '
            . 'são permitidas, possibilitando inserir conteúdo variável. Essas tasg estão listados abaixo.</p>'
            . '<ul><li><strong>{{date}}:</strong> Insere a data atual no formato "quarta-feira, 12 de julho de 2017"</li>'
            . '<li><strong>{{page}}:</strong> Insere o número da página que está sendo gerada</li>'
            . '<li><strong>{{bold} ... }:</strong> Modifica o texto (...) em negrito.</li>'
            . '<li><strong>{{tiny} ... }:</strong> Modifica o texto (...) para um tamanho de fonte menor que o padrão.</li>'
            . '<li><strong>{{thin} ... }:</strong> Modifica o texto (...) para estilo mais fino e itálico.</li></ul>'
            . '<p>OBS: Tags de substituição podem ser usadas dentro de tags de modificação, porém tags de modificação '
            . 'não podem ser utilizadas dentro de outras tags de modificação. Ex: {{bold} {{page}}} é <b>válido</b>. {{bold} {{tiny} texto}} é <b>inválido</b></p>'
        ));

        $screen->add_help_tab(array(
            'id' => 'list_moreoptions',
            'title' => 'Outras opções',
            'content' => '<p>Outras opções disponíveis:</p>'
            . '<ul>'
            . '<li><strong>Linha divisória:</strong> Adiciona uma linha divisória entre o cabeçalho e o conteúdo da página."</li>'
            . '</ul>'));
    }

    public function display() {
        $headers_table = new Rposul_Exporter_Admin_Page_Manage_Headers_Table();
        $headers_table->prepare_items();
        require (plugin_dir_path(__FILE__) . "partials/plugin-rposul-exporter-manage-headers.php");
    }

}

if (!class_exists('WPext_List_Table')) {
    require_once(plugin_dir_path(__FILE__) . "../includes/class-wpext-list-table.php");
}

class Rposul_Exporter_Admin_Page_Manage_Headers_Table extends WPext_List_Table {

    private $i18n = array(
        'single' => 'Simples',
        'double' => 'Duplo'
    );

    function __construct() {
        //Set parent defaults
        parent::__construct(array(
            'singular' => 'header', //singular name of the listed records
            'plural' => 'headers', //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));
    }

    function get_columns() {
        $columns = array(
            'name' => __("Name"),
            'type' => "Tipo",
            'sideA' => "Conteúdo",
            'sideB' => "Conteúdo",
            'headrule' => "Linha divisória",
            'enabled' => "Habilitado"
        );
        return $columns;
    }

    function column_default($item, $column_name) {
        /* @var $item Rposul_Columnist */
        switch ($column_name) {
            case 'name':

                //Build row actions
                $delete_link = add_query_arg(
                        array('action' => 'delete', 'header' => $item->get_id()), wp_unslash($_SERVER['REQUEST_URI'])
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

            case 'type':
                return $this->i18n[$item->type];
            case 'sideA':
                if (isset($item->content[0])) {
                    if ($item->content[0]['type'] == 'text') {
                        return $item->content[0]['value'];
                    } else {
                        return wp_get_attachment_image($item->content[0]['value'], 'osul_columnist');
                    }
                }
                return '';
            case 'sideB':
                if (isset($item->content[1])) {
                    if ($item->content[1]['type'] == 'text') {
                        return $item->content[1]['value'];
                    } else {
                        return wp_get_attachment_image($item->content[1]['value'], 'osul_columnist');
                    }
                }
                return '';
            case 'headrule':
                if ($item->headrule_enabled) {
                    return '<span class="checkmark">'
                            . '<div class="checkmark_stem"></div>'
                            . '<div class="checkmark_kick"></div>'
                            . '</span>';
                } else {
                    return "";
                }
                return '<input type="checkbox"' . checked('1', $item->headrule_enabled, false) . ' disabled />';
            case 'enabled':
                return '<label class="rposul-input-toggle">'
                        . '<input type="checkbox"' . checked('1', $item->enabled, false) . ' data-id="' . $item->get_id() . '"/>'
                        . '<span class="slider round"></span>'
                        . '</label>';
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = Rposul_Header::get();
    }

}
