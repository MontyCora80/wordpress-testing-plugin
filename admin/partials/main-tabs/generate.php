<?php
$should_reset_server_state = false;
$pages_to_generate = RPOSUL_Options::get_option(RPOSUL_OPTION_PAGE_TO_GENERATE, 0);

if (isset($_REQUEST['rposul_exporter_form_generate'])) {
    if (!$is_execution_running) {

        if (isset($_POST["rposul_exporter_main_export_page_check"])) {
            $pages_to_generate = $_POST["rposul_exporter_main_export_page"];
        } else {
            $pages_to_generate = 0;
        }
        RPOSUL_Options::update_option(RPOSUL_OPTION_PAGE_TO_GENERATE, $pages_to_generate);
        Rposul_Newspaper::generate(
                isset($_POST["rposul_exporter_main_export_pdf_quick"]), $pages_to_generate
        );
        $should_reset_server_state = true;
    } else {
        print_notice_e('error', true, 'Já existe uma geração em andamento.');
    }
}

if (isset($_POST['rposul_exporter_main_cancel_execution'])) {
    Rposul_Exporter_PyExporterComm::cancel_execution();
    print_notice_e('success', false, "Geração cancelada.");
    $should_reset_server_state = true;
}

if ($should_reset_server_state) {
    $this->server_state = Rposul_Exporter_PyExporterComm::get_state();
}

$current_state = $this->server_state; //server_state commes from the parent
$is_done = arr_get($current_state, 'done', false);
$is_running = arr_get($current_state, 'running', false);
$current_page = arr_get($current_state, 'current_page', "");
$total_pages = arr_get($current_state, 'total_pages', "");
$output_filename = arr_get($current_state, 'output_filename', "merged.pdf");
$displayNone = 'style="display: none;"';
$download_link = RPOSUL_SERVICE_URL . "tmp/$output_filename";

//var_dump($_POST);
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row"><label>Data</label></th>
        <td><?php echo arr_get($current_state, 'news_date', '--'); ?></td>
    </tr>
    <form id="generate" method="post">  
        <tr valign="top">
            <th scope="row"><label>Gerar</label></th>
            <td>
                <!-- Removed Issue #739 -->
                <?php if (RPOSUL_IS_DEBUG): ?>
                    <input class="button-primary" type="submit" id="rposul_exporter_main_export_pdf_quick" name="rposul_exporter_main_export_pdf_quick" value="Rascunho" />
                <?php endif; ?>
                <input class="button-primary" type="submit" id="rposul_exporter_main_export_pdf_close" name="rposul_exporter_main_export_pdf_close" value="Versão final" />
                <input type="hidden" name="rposul_exporter_form_generate" value="Y" />
            </td>
            <td>
                <input type="checkbox" name="rposul_exporter_main_export_page_check" <?php checked($pages_to_generate != 0, true, true); ?> id="rposul_exporter_main_export_page_check" value="Y">Página específica
                <input type="number" id="rposul_exporter_main_export_page" class="ui-widget-content ui-corner-all" name="rposul_exporter_main_export_page"  
                       min="0" max="<?php echo Rposul_Page::count(); ?>" maxlength="4" size="4" value="<?php echo $pages_to_generate ? $pages_to_generate : 1; ?>" <?php disabled($pages_to_generate, 0, true); ?>/>

            </td>
    </form>
</tr>      
<tr valign="top">
    <th scope="row"><label>Status</label></th>
    <td><?php
        echo '<div id="osul-pdf-show-spinner" ' . (!$is_running ? $displayNone : '') . ' class="spinner is-active" style="float:left;width:auto;height:auto;margin:0px;padding:10px 0 10px 20px;background-position:0px 0;"></div>'
        . '<i id="osul-generate-checkmark" ' . (!$is_done ? $displayNone : '') . ' class="genericon genericon-checkmark"></i>'
        . '<i id="osul-generate-close" ' . ($is_done || $is_running ? $displayNone : '') . ' class="genericon genericon-close"></i>';
        ?></td>
</tr>

<tr valign="top">
    <th scope="row"><label>Progresso</label></th>
    <td><div id="progressbar" data-progress-value="<?php echo $current_page; ?>" data-progress-max="<?php echo $total_pages; ?>" ><div class="progress-label"><?php echo ($is_running ? "$current_page/$total_pages" : ""); ?></div></div>
        <form id="progresscancel" method="post" <?php echo (!$is_running ? $displayNone : ''); ?>>
            <?php submit_button('Cancelar', 'primary', 'rposul_exporter_main_cancel_execution'); ?>
        </form>
    </td>
</tr>

<tr valign="top">        
    <td><?php
        $download_or_try_to_view = false ? 'download="' . $get_newsdate . '"' : "";
        echo '<form method="post">
                    <input type="hidden" name="rposul_exporter_form_submitted" value="Y"/>
                    <a id="osul-pdf-show" class="button-primary" href="' . $download_link . '" ' . $download_or_try_to_view .
        ($is_done ? ' ' : ' disabled onclick="return false;"') . '>Visualizar PDF</a>
                    </form>';
        ?></td>
</tr>
</table>

<p>    
    <?php submit_button('Mostrar log', 'secondary remote-log-show', 'remote-log-show', false); ?>
    <?php //submit_button('Resumo para impressão', 'secondary', 'print-summary', false, array('onclick' => 'PrintDiv();')); ?>
</p>
<!--<input type="button" value="Resumo para impressão" onclick="PrintDiv();" />-->

<div id="remote-log-data" style="display:none; cursor: default">
    <?php
    submit_button('Fechar', 'secondary remote-log-close', 'remote-log-close', false);
    submit_button('Atualizar', 'secondary remote-log-show', 'remote-log-update', false);
    ?>
    <div  id='remote-log-data-text'></div>
</div> 

<script type="text/javascript">
    function PrintDiv() {

        var headstr = "<html><head><title></title></head><body>";
        var footstr = "</body>";
        var newstr = document.getElementById('divToPrint').innerHTML;
        var oldstr = document.body.innerHTML;
        document.body.innerHTML = headstr + newstr + footstr;
        window.print();
        document.body.innerHTML = oldstr;
        return false;
    }
</script>
<!--<div id="divToPrint" style="display: none;">

    <h1>Resumo Jornal O Sul</h1>
    <h1>Nº: <?php // echo rposul_get_news_number_from_date(RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE));         ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Data: <?php echo RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE); ?></h1>
    <br/>
    <h2>Conteúdo:</h2>
    <ul>
<?php
//        //Nao repetir instanciacao do newspaper
//        foreach ($newspaper->newspaper_pages as $page_number => $page) {
//            echo "<li>";
//            echo "<span>";
//            echo "Página " . ($page_number + 1) . ": ";
//            if ($page instanceof TexBaseCoverTemplate) {
//                echo "Capa";
//            } else {
//                echo Rposul_Exporter_Constants::$template_ids[$page->getTemplateId()];
//            }
//            echo "</span>";
//            echo "<ul>";
//            foreach ($page->post_ids as $post) {
//                echo "<li>" . get_post($post)->post_title . "</li>";
//            }
//
//            foreach ($page->transient_ads as $adv) {
//                echo "<li>" . $adv->title . " (Anúncio) </li>";
//            }
//            echo "</ul>";
//            echo "</li>";
//        }
?>
    </ul>
</div> -->
