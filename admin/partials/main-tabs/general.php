<form class="dirty-check" method="post" action="<?php echo esc_url(add_query_arg(array('newskeepdate' => true))); ?>">
    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <label for="rposul_exporter_main_date"><?php esc_attr_e(__('Date'), 'wp_admin_style'); ?></label>
            </th>
            <td>                        
                <input type="text" name="rposul_exporter_main_date" id="news_date" value="<?php echo RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE, ""); ?>" readonly="true"/>
            </td>
        </tr>
        <tr>
            <th>
                <label for="rposul_exporter_double_date">Edição dupla</label>
            </th>
            <td>
                <input type="checkbox" name="rposul_exporter_double_date" id="news_double" <?php checked(RPOSUL_Options::get_option(RPOSUL_OPTION_DOUBLE_NEWS_DATE, false)); ?>/>
            </td>
        </tr>
    </table>
    <input type="hidden" name='rposul_exporter_form_submitted' value="Y"/>
    <input class="button-primary" type="submit" name="rposul_exporter_main_submit" disabled="disabled" value="<?php esc_attr_e("Save"); ?>" />
</form>