<?php
$newspaperdate = RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE);
if ($newspaperdate) {
    $search_datetime = DateTime::createFromFormat(RPOSUL_DATE_FORMAT, $newspaperdate);
    $search_datetime->setTime(0, 0, 0);
    $search_datetime->modify("-1 day");
    $search_datetime_string = $search_datetime->format(RPOSUL_DATE_FORMAT);
} else {
    $search_datetime = new DateTime();
    $search_datetime_string = '';
}
?>
<div id='post-selection-main'>
    <div id="post-selection-source">
        <div id="post-selection-source-options">
            <div>
                <input type="text" name="" id="date-source-posts" value="<?php echo $search_datetime_string; ?>" readonly="true"/>
                <?php submit_button('Buscar', 'secondary', 'search-posts-by-date', false, array('title' => 'Carrega os posts da data escolhida para serem selecionados')); ?>
            </div>
            <div>
                <?php
                submit_button(
                        'Data', 'secondary', 'order-posts-by-date', false, array('title' => "Ordena valores pela data de postagem (decrescente)")
                );
                submit_button('Tamanho', 'secondary', 'order-posts-by-size', false, array('title' => "Ordena valores pelo tamanho (crescente)"));
                ?>
            </div>
            <input type="text" value="" id="filter-source-posts" placeholder="Filtrar"/>
        </div>
        <ul class="sortable-source-posts">
            <?php echo rposul_get_posts_li(array('date' => $search_datetime, 'orderby' => 'post_date')); ?>
        </ul>
    </div>
    <div id="post-selection-target">
        <div id="post-selection-target-options">
            <?php submit_button('Adicionar página', 'secondary add-blank-page', 'add-blank-page-top', false, array('title' => "Adiciona uma página em branco ao final do jornal")); ?>
            <input type="text" value="" id="filter-target-pages" placeholder="Filtrar págs. (ex: 2, 5, ...)"/>
            <?php submit_button('Opções', 'secondary newspaper-options', 'newspaper-options', false, array('title' => "Opções para toda a edição")); ?>
        </div>
        <ul id='post-selection-target-sortable-wrap'>
            <?php
            $newspaper_pages = Rposul_Page::get();
            for ($index = 0; $index < count($newspaper_pages); $index ++) {
                $page = $newspaper_pages[$index];
                /* @var $page Rposul_Page */
                ?>                

                <li class='<?php echo $page->is_cover() ? "sortable-target-cover'" : "sortable-target-page"; ?>'>
                    <div class="sortable-target-item-header" 
                         data-pageid="<?php echo $page->get_id(); ?>"
                         data-templateid="<?php echo $page->template_id; ?>"
                         data-headerid="<?php echo arr_get($page->extra_values, 'header_type', get_option(RPOSUL_OPTION_DEFAULT_HEADER, 0)); ?>"
                         data-extravalues='<?php echo json_encode($page->extra_values); ?>'
                         >
                        <span class="dashicons dashicons-admin-generic sortable-target-item-header-settings"></span>
                        <span class="sortable-target-item-header-notice"></span>
                        <span class="sortable-target-item-header-page"><?php echo $index + 1; ?></span>
                    </div>
                    <ul class='sortable-target-posts' data-pageid="<?php echo $page->get_id(); ?>">
                        <?php if ($page->is_cover()): ?>
                            <li class="ui-state-default ui-state-disabled">CAPA</li>                            
                        <?php endif; ?>
                        <?php
                        if ($page->post_ids) {
                            $args = array(
                                'post__in' => $page->post_ids,
                                'disable_pageposts' => false,
                                'trash' => true
                            );
                            echo rposul_get_posts_li($args);
                        }

                        if ($page->advertisement_ids) {
                            echo rposul_get_advertisements_li(array('page_id' => $page->get_id()));
                        }
                        echo rposul_get_extras_li(array('page' => $page));
                        ?>                        
                    </ul>
                </li>
                <?php
            }
            ?>            
        </ul>
        <div id="post-selection-target-options">
            <?php submit_button('Adicionar página', 'secondary add-blank-page', 'add-blank-page-bottom', false); ?>
            <?php submit_button('Opções', 'secondary newspaper-options', 'newspaper-options', false, array('title' => "Opções para toda a edição")); ?>
        </div>
    </div>
</div>


<div id="page-options-modal">
    <span class="page-options-modal-close">&times;</span>
    <table class="form-table">
        <caption></caption>
        <tbody>
            <tr valign="top">
                <th scope="row">Layout</th>
                <td>
                    <select id="page-options-modal-templateid">
                        <option value="">Automático</option>
                        <?php
                        foreach (Rposul_Exporter_Constants::$template_ids as $id => $name) {
                            echo "<option value='$id'>$name</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Tipo de cabeçalho</th>
                <td class="forminp">
                    <select id="page-options-modal-headerid">
                        <?php
                        $headers = Rposul_Header::get(array('WHERE' => 'enabled=1'));
                        foreach ($headers as $h) {
                            echo "<option value='{$h->get_id()}'>$h->name</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr id='page-options-modal-extra-title-row' valign="top">
                <th scope="row">Título</th>
                <td class="forminp">
                    <input id='page-options-modal-extra-title' class='modal-extra-value' type='text' placeholder='Título' name='title'/>                    
                </td>
            </tr>
            <tr>
                <td><?php submit_button(__('Save Changes'), 'primary', 'page-options-modal-save'); ?></td>
                <td><?php submit_button(__('Move'), 'secondary', 'page-options-modal-move'); ?></td>
                <td><?php submit_button(__('Delete'), 'secondary', 'page-options-modal-delete'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div id="page-move-modal">
    <span class="page-options-modal-close">&times;</span>
    <table class="form-table">
        <caption></caption>
        <tbody>
            <tr valign="top">
                <th scope="row">Página destino</th>
                <td>
                    <input type="number" id="page-move-modal-number" class="ui-widget-content ui-corner-all" min="2" max="365" maxlength="4" size="4"/>                     
                </td>
            </tr>            
            <tr>
                <td><?php submit_button(__('Apply'), 'primary', 'page-move-modal-apply'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div id="page-add-modal">
    <span class="page-options-modal-close">&times;</span>
    <table class="form-table">
        <caption>Adicionar páginas</caption>
        <tbody>
            <tr valign="top">
                <th scope="row">Quantidade</th>
                <td>
                    <input type="number" id="page-add-modal-quantity" class="ui-widget-content ui-corner-all" min="1" max="365" maxlength="4" size="4"/>                     
                </td>
            </tr> 
            <tr valign="top">
                <th scope="row">Página</th>
                <td>
                    <input type="number" id="page-add-modal-position" class="ui-widget-content ui-corner-all" min="2" max="365" maxlength="4" size="4"/>                     
                </td>
            </tr>            
            <tr>
                <td><?php submit_button(__('Apply'), 'primary', 'page-add-modal-apply'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div id="newspaper-options-modal">
    <span class="page-options-modal-close">&times;</span>
    <table class="form-table">
        <caption>Opções da edição</caption>        
        <tbody>
            <tr valign="top">
                <th scope="row">Recolocar editorias</th>
                <td>
                    <form method="post" action="">
                        <?php submit_button('Refazer editorias', 'secondary', 'reset-insertions', false, array('title' => "Retira todas as editorias do jornal e adiciona as configuradas atualmente na aba editorias", 'onclick' => "return confirm('Isto irá limpar todas as editorias selecionados para esta edição. Continuar?')")); ?>
                    </form>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Recolocar anúncios</th>
                <td>
                    <form method="post" action="">
                        <?php submit_button('Refazer anúncios', 'secondary', 'reset-ads', false, array('title' => "Retira todas os anúncios do jornal e adiciona as configuradas atualmente na aba de anúncios", 'onclick' => "return confirm('Isto irá limpar todos os anúncios selecionados para esta edição. Continuar?')")); ?>
                    </form>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Recomeçar</th>
                <td>
                    <form method="post" action="">
                        <?php submit_button('Limpar tudo', 'secondary', 'clear-selection', false, array('title' => "Limpa a seleção e recomeça o jornal", 'onclick' => "return confirm('Isto irá limpar todos os posts, editorias e anúncios selecionados para esta edição. Continuar?')")); ?>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>
</div>