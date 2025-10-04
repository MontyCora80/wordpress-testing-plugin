<div class="wrap">
    <h2>        
        Cabeçalhos
        <a href="#add" class="add-new-h2"><?php echo __("Add"); ?></a> 
    </h2>

    <form method="get">        
        <?php $headers_table->display(); ?>
    </form>

    <h2>Padrão</h2>

    <form method="post">
        <input type="hidden" name="set-default-header" value="Y">
        <table class="widefat">
            <tbody>
                <tr>
                    <th>Cabeçalho padrão</th>
                    <td>
                        <select name='default-header'>
                            <?php
                            $selected_default_header = RPOSUL_Options::get_option(RPOSUL_OPTION_DEFAULT_HEADER, 0);
                            $none_selected = true;
                            $header_options = '';
                            foreach ($headers_table->items as $item) {
                                $item_id = $item->get_id();
                                $selected = selected($selected_default_header, $item_id, false);
                                $none_selected &= empty($selected);
                                $header_options .= "<option value='$item_id' $selected>$item->name</option>";
                            }
                            ?>                        
                            <option disabled value="0" <?php selected($none_selected); ?>> -- Selecione uma opção -- </option>
                            <?php echo $header_options; ?>        
                        </select>
                    </td>
                    <td>
                        <?php submit_button(__('Save'), 'secondary', 'save-default-header', false) ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

    <h2 id="add">Adicionar</h2>

    <form method="post">
        <input type="hidden" name="create-header" value="Y">
        <table class="widefat">
            <tbody>
                <tr>
                    <th>Nome de exibição</th>
                    <td><input type="text" name="header[name]" placeholder="Nome do cabeçalho" required></td>
                </tr>
                <tr>
                    <th>Linha divisória</th>
                    <td>
                        <input type="checkbox" checked="checked" name="header[headrule]"/>
                    </td>
                </tr>
                <tr>
                    <th>Tipo do cabeçalho</th>
                    <td>
                        <select name='header[type]' id="header_type">
                            <option value="double">Duplo</option>
                            <option value="single">Simples</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Conteúdo</th>
                    <td>
                        <select name='header[option_A_type]' class="header_option_type">
                            <option value="text" data-show-id="#rposul_header_option_text_A">Texto</option>
                            <option value="image" data-show-id="#rposul_header_option_image_A">Imagem</option>
                        </select>                        
                    </td>
                </tr>

                <tr id="rposul_header_option_image_A" class="rposul_header_options">
                    <th></th>
                    <td>
                        <?php submit_button('Selecionar imagem', 'header_photo hide-if-no-js button small', 'imag_a', false, array('data-input-name' => 'header[image][]')); ?>                        
                    </td>
                </tr>
                <tr id="rposul_header_option_text_A" class="rposul_header_options">
                    <th></th>
                    <td>
                        <input name='header[text][]' type="text"/>
                    </td>
                </tr>


                <tr class="show_toggle show_if_double">
                    <th>Conteúdo</th>
                    <td>
                        <select name='header[option_B_type]' class="header_option_type">
                            <option value="text" data-show-id='#rposul_header_option_text_B'>Texto</option>
                            <option value="image" data-show-id="#rposul_header_option_image_B">Imagem</option>
                        </select>                        
                    </td>
                </tr>

                <tr id="rposul_header_option_image_B" class="rposul_header_options">
                    <th></th>
                    <td>
                        <?php submit_button('Selecionar imagem', 'header_photo hide-if-no-js button small', 'imag_b', false, array('data-input-name' => 'header[image][]')); ?>                        
                    </td>
                </tr>
                <tr id="rposul_header_option_text_B" class="rposul_header_options">
                    <th></th>
                    <td>
                        <input name='header[text][]' type="text"/>
                    </td>
                </tr>

            </tbody>
        </table>
        <?php submit_button(__('Add'), 'primary', 'save-columnist') ?>
    </form>


</div><!--End .wrap -->