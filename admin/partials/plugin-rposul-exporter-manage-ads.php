<div class="wrap">

    <?php
    //Check if we have searched the venues
    ?>

    <h2>        
        Anúncios
        <a href="<?php menu_page_url(Rposul_Exporter_Admin_Page_Edit_Ads::MENU_SLUG, true); ?>" class="add-new-h2"><?php echo __("Add"); ?></a> 
        <?php
        $search_term = ( isset($_GET['s']) ? esc_attr($_GET['s']) : '' );
        //Not sure we want this
//        if (empty($search_term) && !isset($_GET['internal'])){
//            $search_term = Rposul_Newspaper::get_expected_datetime()->format(RPOSUL_FRONT_END_DATE_FORMAT);
//            $_GET['s'] = $search_term;
//            $_REQUEST['s'] = $_GET['s'];
//        }
        if ($search_term) {
            $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $current_url = remove_query_arg(array('hotkeys_highlight_last', 'hotkeys_highlight_first', 'paged', 's'), $current_url);
            printf('<span class="subtitle" style="display:block;padding-left:0;padding-top:10px;;">Resultados para &#8220;%s&#8221;&nbsp;&nbsp;<a href="%s">Mostrar todos</a></span>', $search_term, $current_url);            
        }
        ?>
    </h2>

    <form id="rposul-ads-table" method="get">
        <!--Now we can render the completed list table-->         
        <input type="hidden" name="internal" value="Y" />
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php $ads_table->search_box('Buscar data', 's-date', 'Todos os anúncios'); ?>
        <?php $ads_table->display(); ?>
    </form>


</div><!--End .wrap -->