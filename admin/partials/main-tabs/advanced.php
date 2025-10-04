<?php
if (isset($_POST['rposul_exporter_advanced_form_submitted'])) {
    
    if (isset($_POST['rposul_exporter_main_advanced_publish'])) {
        
        Rposul_Exporter_PyExporterComm::publish();
    }
    
    if (isset($_POST['rposul_exporter_main_dismantle_news'])) {
        if (delete_metadata('post', null, RPOSUL_PUBLISHED_DATE_POST_META, $_POST['rposul_exporter_main_advanced_date'], true)) {
            print_notice_e('success', false, "Publicação desmontada com sucesso");
        } else {
            print_notice_e('error', false, "Nenhuma publicação encontrada em " . $_POST['rposul_exporter_main_advanced_date']);
        }
    }

    if (isset($_POST['rposul_exporter_main_clear_cache'])) {
        RPOSUL_Options::clear();
        Rposul_Page::delete_all();
        print_notice_e('success', false, "Dados da cache apagados com sucesso.");
    }


    if (isset($_POST['rposul_exporter_main_cancel_execution'])) {
        Rposul_Exporter_PyExporterComm::cancel_execution();
        Rposul_Exporter_PyExporterComm::clear_cache();
        print_notice_e('success', false, "Geração cancelada.");
    }

    if (isset($_POST['rposul_exporter_main_make_content_length'])) {
        global $wpdb;
        $lowerbounddate = new DateTime(RPOSUL_Options::get_option(RPOSUL_OPTION_ARTICLE_START_DATE));
        $upperbounddate = new DateTime(RPOSUL_Options::get_option(RPOSUL_OPTION_ARTICLE_END_DATE));
        $options = array(
            'posts_per_page' => -1,
            'offset' => 0,
            'date_query' => array(
                'after' => $lowerbounddate->format('Y-m-d'),
                'before' => $upperbounddate->format('Y-m-d'),
                'inclusive' => true
            ),
            'fields' => 'ids', // only get post IDs.
        );


        $options['meta_query'] = array(
            array(
                'key' => RPOSUL_CONTENT_LENGTH_POST_META,
                'compare' => 'NOT EXISTS')
        );
        $posts_results = get_posts($options);

        foreach ($posts_results as $pid) {
            update_post_meta($pid, RPOSUL_CONTENT_LENGTH_POST_META, rposul_retrieve_post_content_length($pid, true));
        }
    }
}
?>
<form method="post">
    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="rposul_exporter_main_advanced_publish">Publicar último gerado</label>
            </th>
            <td>
            </td>
            <td>                        
                <input class="button-primary" type="submit" name="rposul_exporter_main_advanced_publish" value="Publicar" onclick="return confirm('Tem certeza que deseja realizar a publicação do último pdf gerado?')" />
            </td>
        </tr>        
        <tr valign="top">
            <th scope="row">
                <label for="rposul_exporter_main_advanced_date">Data</label>
            </th>
            <td>                        
                <input type="text" name="rposul_exporter_main_advanced_date" id="news_dismantle_date" value="<?php echo RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE, ""); ?>" readonly="true"/>
            </td>
            <td>                        
                <input class="button-primary" type="submit" name="rposul_exporter_main_dismantle_news" value="Desmontar publicação" onclick="return confirm('Essa ação irá marcar todos os posts do dia selecionado como não publicados. Continuar?')" />
            </td>
        </tr>        
        <tr valign="top">
            <th scope="row">
                <label>Banco de dados</label>
            </th>
            <td></td>
            <td>                        
                <input class="button-primary" type="submit" name="rposul_exporter_main_clear_cache" value="Limpar cache"  onclick="return confirm('Essa ação irá apagar todas as configurações selecionadas. Continuar?')" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label>Tamanho dos conteúdos para o periodo</label>
            </th>
            <td></td>
            <td>                        
                <input class="button-primary" type="submit" name="rposul_exporter_main_make_content_length" value="Recalcular" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <label>Cancelar geração atual</label>
            </th>
            <td></td>
            <td>                        
                <input class="button-primary" type="submit" name="rposul_exporter_main_cancel_execution" value="Cancelar"  onclick="return confirm('Essa ação parar qualquer execução atual e limpar todos os dados de cache do gerador de PDF. Continuar?')" />
            </td>
        </tr>
    </table>
    <input type="hidden" name='rposul_exporter_advanced_form_submitted' value="Y"/>            
</form>