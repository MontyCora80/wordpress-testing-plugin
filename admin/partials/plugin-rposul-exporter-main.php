<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Rposul_Exporter
 * @subpackage Rposul_Exporter/admin/partials
 */
if (!current_user_can('edit_others_pages')) {
    wp_die('You do not have sufficient permission to access this page.');
}

/* @var $this Rposul_Exporter_Admin_Page_Main */ 

$is_execution_running = $this->server_state['running'];
?>

<div class="wrap">
    <h2>Criar PDF</h2>   

    <h2 class="nav-tab-wrapper">
        <?php
        foreach ($this->tabs as $tab_key => $tab_values) {
            $link = sprintf('?page=%s&tab=%s', $_REQUEST['page'], $tab_key);
            if (arr_get($this->tabs, 'get')) {
                $link .= "&{$tab_values['get']}";
            }
            echo "<a href='$link' class='nav-tab " . ($this->active_tab == $tab_key ? 'nav-tab-active' : '') . "'>{$tab_values['title']}</a>";
        }
        ?>        
    </h2>
    <?php
    if (false && $is_execution_running && $this->active_tab != 'generate' && $this->active_tab != 'advanced') {
        echo "<div class='rposul-disabled-interactions'>";
        print_notice_e('warning', false, "Uma geração está em andamento. Nenhum dado pode ser alterado nesse momento. "
                . "<a href='" . add_query_arg(array('page' => $_REQUEST['page'], 'tab' => 'generate'), admin_url('admin.php')) . "'>"
                . "Ir para a aba 'Exportar'</a>");
    }
    if ($this->active_tab == 'general'):
        include plugin_dir_path(__FILE__) . 'main-tabs/general.php';
    elseif ($this->active_tab == 'sections'):
        include plugin_dir_path(__FILE__) . 'main-tabs/post-selection.php';
    elseif ($this->active_tab == 'cover'):
        include plugin_dir_path(__FILE__) . 'main-tabs/cover.php';
    elseif ($this->active_tab == 'backcover'):
        $is_backcover = true;
        include plugin_dir_path(__FILE__) . 'main-tabs/cover.php';
    elseif ($this->active_tab == 'reorder'):        
        include plugin_dir_path(__FILE__) . 'main-tabs/reorder-posts.php';
        
    elseif ($this->active_tab == 'reorder-ads'):
        include plugin_dir_path(__FILE__) . 'main-tabs/reorder-ads.php';
    elseif ($this->active_tab == 'generate'):
        include plugin_dir_path(__FILE__) . 'main-tabs/generate.php';
    elseif ($this->active_tab == 'advanced'):
        include plugin_dir_path(__FILE__) . 'main-tabs/advanced.php';
    endif;

    if (false && $is_execution_running && $this->active_tab != 'generate' && $this->active_tab != 'advanced') {
        echo "</div>";
    }
    ?>

</div>