<div id='<?php echo "template_$current_template_id"; ?>' class="cover_form_option">
    <form class="<?php get_form_classes_e($current_template_id); ?>" method="post">
        <table class="form-table">
            <tr valign="top">
                <th>Imagem Esquerda</th>
                <td><label for="upload_image">
                        <div class="thumbnail">
                            <div class="selected_image_container">
                                <?php
                                $attachment_id = arr_get($t_options, "thumb_primary", "");
                                if (!empty($attachment_id)) {
                                    $attachment_attr = rposul_get_attachment($attachment_id);
                                    if (!$attachment_attr) {
                                        $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                    } else {
                                        $attachment_url = $attachment_attr['url'];
                                    }
                                } else {
                                    $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                }
                                ?>
                                <img class="upload_image_thumbnail" data-picker-id="primary" data-attachment-id="<?php echo $attachment_id ?>" width="120" height="120" src="<?php echo $attachment_url ?>" class="img-responsive">
                            </div>
                        </div>
                        <input class="upload_image_button" data-picker-id="primary" type="button" value="Selecionar imagem" />                            
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th>Cor borda imagem esquerda</th>
                <td><select name='cover_options[color_primary]'><?php generate_color_options_e(arr_get($t_options, 'color_primary')); ?></select></td>
            <input name='cover_options[thumb_primary]' data-picker-id="primary" type="hidden" value="<?php echo arr_get($t_options, 'thumb_primary'); ?>" />
            </tr>
            <tr valign="top">
                <th>Imagem Direita</th>
                <td><label for="upload_image">
                        <div class="thumbnail">
                            <div class="selected_image_container">
                                <?php
                                $attachment_id = arr_get($t_options, "thumb_secondary", "");
                                if (!empty($attachment_id)) {
                                    $attachment_attr = rposul_get_attachment($attachment_id);
                                    if (!$attachment_attr) {
                                        $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                    } else {
                                        $attachment_url = $attachment_attr['url'];
                                    }
                                } else {
                                    $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                }
                                ?>
                                <img class="upload_image_thumbnail" data-picker-id="secondary" data-attachment-id="<?php echo $attachment_id ?>" width="120" height="120" src="<?php echo $attachment_url ?>" class="img-responsive">
                            </div>
                        </div>
                        <input class="upload_image_button" data-picker-id="secondary" type="button" value="Selecionar imagem" />                            
                        <input name='cover_options[thumb_secondary]' data-picker-id="secondary" type="hidden" value="<?php echo arr_get($t_options, 'thumb_secondary'); ?>" />
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th>Cor borda imagem direita</th>
                <td><select name='cover_options[color_secondary]'><?php generate_color_options_e(arr_get($t_options, 'color_secondary')); ?></select></td>
            </tr>
            <tr valign="top">
                <th>Noticia Selo</th>
                <td><select class="combobox_titles" name='cover_options[news_second]'><?php generate_combobox_options_e(arr_get($t_options, 'news_second')); ?>                    </select></td>
            </tr>
            <tr valign="top">
                <th>Noticia Manchete</th>
                <td><select class="combobox_titles" name='cover_options[news_third]'><?php generate_combobox_options_e(arr_get($t_options, 'news_third')); ?></select></td>
            </tr>
            <tr valign="top">
                <th>Noticia Esquerda</th>
                <td><select class="combobox_titles" name='cover_options[news_first]'><?php generate_combobox_options_e(arr_get($t_options, 'news_first')); ?></select></td>
            </tr>
            <tr valign="top">
                <th>Noticia Direita</th>
                <td><select class="combobox_titles" name='cover_options[news_fifth]'><?php generate_combobox_options_e(arr_get($t_options, 'news_fifth')); ?></select></td>
            </tr>
            <tr valign="top">
                <th>Noticia Pé</th>
                <td><select class="combobox_titles" name="cover_options[news_fourth]"><?php generate_combobox_options_e(arr_get($t_options, 'news_fourth')); ?>                    </select></td>
            </tr>
        </table>
        <input disabled="disabled" class="button-primary" type="submit" name="rposul_exporter_main_cover_options_save" value="<?php esc_attr_e("Save"); ?>" />
    </form>
</div>