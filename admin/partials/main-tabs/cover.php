<?php
if (!current_user_can('edit_others_pages')) {
    wp_die('You do not have sufficient permission to access this page.');
}

global $rposul_cover_error_template_id;
global $rposul_available_posts;
$rposul_cover_error_template_id = -1;

function get_available_posts() {
    $pageposts = Rposul_Page::find_pageposts();

    $pageposts_ids = array();
    foreach ($pageposts as $pagepost) {
        $pageposts_ids[] = absint($pagepost['post_id']);
    }

    $args = array(
        'include' => implode(',', $pageposts_ids),
        'post_status' => rposul_valid_post_status()
    );

    return get_posts($args);
}

$rposul_available_posts = get_available_posts();

function get_form_classes_e($template_id) {
    global $rposul_cover_error_template_id;
    echo "cover_form dirty-check";
    if ($rposul_cover_error_template_id == $template_id) {
        echo " begin-dirty";
    }
}

if (isset($is_backcover) && $is_backcover) {
    $option_storage_value = RPOSUL_OPTION_SELECTED_BACKCOVER_TEMPLATE_OPTIONS;
} else {
    $option_storage_value = RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS;
}

if (isset($_POST['cover_options']) && !$is_execution_running) {
    $t_options = $_POST['cover_options'];
    $array_test = $t_options;
    foreach ($array_test as $key => $value) {
        if (strpos($key, 'news_') !== 0 || $value === "") {
            unset($array_test[$key]);
        }
    }
    if (count(array_unique($array_test)) == count($array_test)) {
        RPOSUL_Options::update_option($option_storage_value, $t_options);
        print_notice_e('success', true, 'Opções salvas com sucesso!');
    } else {
        // we have duplicate post values
        //$t_options = RPOSUL_Options::get_option($option_storage_value, array());
        // we dont want to restore the saved values but keep the ones selected
        $rposul_cover_error_template_id = arr_get($t_options, 'templateid');
        print_notice_e('error', true, 'Não foi possível salvar as modificações. Posts duplicados não são permitidos.');
    }
} else {
    $t_options = RPOSUL_Options::get_option($option_storage_value, array());
}

function generate_combobox_options_e($selected_id) {
    global $rposul_available_posts;
    $combobox_options = "<option value=''>Selecione uma opção...</option>";
    foreach ($rposul_available_posts as $post) {
        $isselected = $selected_id == $post->ID ? "selected" : "";
        $combobox_options .= "<option value='$post->ID' $isselected>$post->post_title</option>";
    }

    echo $combobox_options;
}

function generate_color_options_e($selected_id) {
    $colors_available = array(
        'Bege' => 'bege',
        'Azul Grêmio' => 'gremio',
        'Vermelho Inter' => 'inter'
    );

    $combobox_options = "";
    foreach ($colors_available as $color_name => $color_value) {
        $isselected = selected($selected_id, $color_value, false);
        $combobox_options .= "<option value='$color_value' $isselected>$color_name</option>";
    }
    echo $combobox_options;
}
?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">
            <label>Layout</label>
        </th>
        <td>
            <select id="covermodel" name="cover_options[templateid]">
                <?php
                foreach (Rposul_Exporter_Constants::$template_cover_ids as $tid => $tvalues) {
                    $is_selected = selected(arr_get($t_options, 'templateid'), $tid, false);
                    echo "<option data-img-class='image_picker_style' data-img-src='" . plugin_dir_url(__FILE__) . "../images/{$tvalues['image']}' value='$tid' $is_selected>{$tvalues['name']}</option>";
                }
                ?>                                                
            </select>
        </td>
    </tr>
</table>

<?php
foreach (Rposul_Exporter_Constants::$template_cover_ids as $key => $value) {
    $current_template_id = $key;
    include plugin_dir_path(__FILE__) . "cover-options/template_$key.php";
}

