<div class="wrap">
    <h2>        
        Editorias
        <a href="#add" class="add-new-h2"><?php echo __("Add"); ?></a> 
    </h2>

    <form method="post">
        <?php submit_button(__('Clear'), 'secondary', 'clear-insertions') ?>
    </form>

    <form method="get">
        <!--Now we can render the completed list table--> 
        <?php // $ads_table->search_box($tax->labels->search_items, 's');  ?>
        <?php $insertions_table->display(); ?>
    </form>


    <h2 id="add">        
        Adicionar
    </h2>

    <form method="post">
        <input type="hidden" name="create-insertion" value="Y">
        <table class="widefat">
            <tbody>
                <tr>
                    <th>Nome de exibição</th>
                    <td><input type="text" name="insertion[name]" placeholder="Nome" required></td>
                </tr>
                <tr>
                    <th>Página</th>
                    <td><input type="number" name="insertion[page]" placeholder="Página" value="2" min="2" max="100" required></td>
                </tr>               
                <tr>
                    <th>Imagem</th>
                    <td>
                        <?php submit_button('Selecionar imagem', 'hide-if-no-js button small', 'insertion_photo', false, array('data-picker-id' => 'imagepicker')); ?>                        
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(__('Add'), 'primary', 'save-insertion') ?>
    </form>


</div><!--End .wrap -->