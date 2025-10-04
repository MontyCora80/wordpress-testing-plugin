<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

add_action('wp_ajax_get_posts_by_date', 'get_posts_by_date');
add_action('wp_ajax_add_newspaper_pages', 'add_newspaper_pages');
add_action('wp_ajax_delete_newspaper_page', 'delete_newspaper_page');
add_action('wp_ajax_get_pages_error', 'get_pages_error');
add_action('wp_ajax_move_post', 'move_post');
add_action('wp_ajax_move_page', 'move_page');
add_action('wp_ajax_move_section', 'move_section');
add_action('wp_ajax_move_advertisement', 'move_advertisement');
add_action('wp_ajax_move_insertion', 'move_insertion');
add_action('wp_ajax_get_generate_status', 'get_generate_status');
add_action('wp_ajax_get_remote_log', 'get_remote_log');
add_action('wp_ajax_save_page_settings', 'save_page_settings');
add_action('wp_ajax_check_newspaper_for_errors', 'check_newspaper_for_errors');
add_action('wp_ajax_set_header_enabled', 'set_header_enabled');

function set_header_enabled() {
    $id = intval($_POST['id']);
    $value = filter_var($_POST['value'], FILTER_VALIDATE_BOOLEAN);
    $headers = Rposul_Header::get(array('WHERE' => "id=$id"));
    if (empty($headers)) {
        wp_send_json_error();
    }
    $header = $headers[0];
    $header->enabled = $value;
    $ret = $header->save();
    if ($ret == true) {
        wp_send_json_success();
    } else {
        wp_send_json_error($ret);
    }
}

function save_page_settings() {
    $pages = Rposul_Page::get(array('WHERE' => "id={$_POST['page_id']}"));
    $page = $pages[0];
    $page->template_id = $_POST['template_id'] ? $_POST['template_id'] : null;
    $page->extra_values = wp_parse_args($page->extra_values, $_POST['extra_values']);
    $page->extra_values['header_type'] = $_POST['header_id'];
    $page->save();
    wp_send_json_success();
}

function get_posts_by_date() {
    $args = array(
        "date" => new DateTime($_POST['date']),
        "fields" => "all",
        'orderby' => 'post_date'
    );
    wp_send_json(rposul_get_posts_li($args));
}

function add_newspaper_pages() {
    $quantity = $_POST['quantity'];
    $position = $_POST['position'];
    $pagenumbers = array();
    $pageids = array();
    $newpages = array();


    for ($index = 0; $index < $quantity; $index++) {
        $newpages[] = new Rposul_Page();
    }
    $pages = Rposul_Page::get();
    array_splice($pages, $position - 1, 0, $newpages);

    for ($index = 0; $index < count($pages); $index++) {
        $page = $pages[$index];
        /* @var $page Rposul_Page */
        $page->ordinal = $index + 1;
        $was_new = $page->is_new();
        $page->save();
        if ($was_new) {
            $pagenumbers[] = $page->ordinal;
            $pageids[] = $page->get_id();
        }
    }


    wp_send_json(array(
        'pageids' => $pageids,
        'pagenumbers' => $pagenumbers
            )
    );
}

function delete_newspaper_page() {
    $page_id = $_POST['page_id'];
    Rposul_Page::delete_by_id($page_id);
    $pages = Rposul_Page::get();
    for ($index = 0; $index < count($pages); $index++) {
        $page = $pages[$index];
        /* @var $page Rposul_Page */
        $page->ordinal = $index + 1;
        $page->save();
    }

    wp_send_json_success();
}

function get_generate_status() {
    wp_send_json(Rposul_Exporter_PyExporterComm::get_state()); //send and die
}

function get_remote_log() {
    wp_send_json(str_replace("\n", "<br>", Rposul_Exporter_PyExporterComm::get_log())); //send and die
}

function move_page() {
    $source_page_id = arr_get($_POST, 'page_id');
    $target_position = arr_get($_POST, 'target_position');
    if ($source_page_id !== null && target_position !== null) {
        $pages = Rposul_Page::get();
        $selected_page_position = null;
        //Correct any inconsistencies 
        for ($index = 0; $index < count($pages); $index++) {
            $page = $pages[$index];
            /* @var $page Rposul_Page */
            $page->ordinal = $index + 1;
            if ($page->get_id() == $source_page_id) {
                $selected_page_position = $page->ordinal;
            }
        }

        if ($target_position != $selected_page_position) {
            $out = array_splice($pages, $selected_page_position - 1, 1);
            array_splice($pages, $target_position - 1, 0, $out);
        }

        for ($index = 0; $index < count($pages); $index++) {
            $page = $pages[$index];
            /* @var $page Rposul_Page */
            $page->ordinal = $index + 1;
            $page->save();
        }
    }
    wp_send_json_success();
}

function move_post() {
    $sourcePageId = arr_get($_POST, 'source_page');
    $targetIndex = arr_get($_POST, 'target_page_position');
    $targetPageId = arr_get($_POST, 'target_page');
    $postid = arr_get($_POST, 'post_id');
    if ($sourcePageId !== null) {
        $query_result = Rposul_Page::get(array("WHERE" => "id=$sourcePageId"));
        $sourcePage = $query_result[0];
        /* @var $sourcePage Rposul_Page */

        if (($key = array_search($postid, $sourcePage->post_ids)) !== false) {
            unset($sourcePage->post_ids[$key]);
            $sourcePage->post_ids = array_values($sourcePage->post_ids);
        }

        if ($sourcePageId != $targetPageId) {
            $sourcePage->save();
        }
    }

    if ($targetPageId !== null) {
        if ($sourcePageId == $targetPageId) {
            $targetPage = $sourcePage;
        } else {
            $query_result = Rposul_Page::get(array("WHERE" => "id=$targetPageId"));
            $targetPage = $query_result[0];
        }

        array_splice($targetPage->post_ids, $targetIndex, 0, $postid);
        $targetPage->save();
    }
    // returns the pages error so we can profit from this call to comunicate    
    get_pages_error();
}

function move_advertisement() {
    $sourcePageId = arr_get($_POST, 'source_page');
    $targetIndex = arr_get($_POST, 'target_page_position');
    $targetPageId = arr_get($_POST, 'target_page');
    $ad_id = arr_get($_POST, 'advertisement_id');

    if ($sourcePageId !== null) {
        $query_result = Rposul_Page::get(array("WHERE" => "id=$sourcePageId"));
        $sourcePage = $query_result[0];
        /* @var $sourcePage Rposul_Page */
        if (($key = array_search($ad_id, $sourcePage->advertisement_ids)) !== false) {
            unset($sourcePage->advertisement_ids[$key]);
            $sourcePage->advertisement_ids = array_values($sourcePage->advertisement_ids);
        }
        if ($sourcePageId != $targetPageId) {
            $sourcePage->save();
        }
    }

    if ($targetPageId !== null) {
        if ($sourcePageId == $targetPageId) {
            $targetPage = $sourcePage;
        } else {
            $query_result = Rposul_Page::get(array("WHERE" => "id=$targetPageId"));
            $targetPage = $query_result[0];
        }

        array_splice($targetPage->advertisement_ids, $targetIndex, 0, $ad_id);
        $targetPage->save();
    }
    // returns the pages error so we can profit from this call to comunicate
    get_pages_error();
}

function move_insertion() {
    $sourcePageId = arr_get($_POST, 'source_page');
    $targetPageId = arr_get($_POST, 'target_page');
    $insertion_id = arr_get($_POST, 'insertion_id');

    if ($sourcePageId !== null && $sourcePageId !== $targetPageId) {
        $query_result = Rposul_Page::get(array("WHERE" => "id=$sourcePageId"));
        $sourcePage = $query_result[0];
        /* @var $sourcePage Rposul_Page */
        if (($key = array_search($insertion_id, arr_get($sourcePage->extra_values, 'insertion_id'))) !== false) {
            unset($sourcePage->extra_values['insertion_id']);
        }

        if ($targetPageId !== null) {
            $query_result = Rposul_Page::get(array("WHERE" => "id=$targetPageId"));
            $targetPage = $query_result[0];
            if (arr_get($targetPage->extra_values, 'insertion_id')) {
                // TODO cannot move to this page because 
            }
            $targetPage->extra_values['insertion_id'] = $insertion_id;
            $targetPage->save();
        }
        $sourcePage->save();
    }

    // returns the pages error so we can profit from this call to comunicate
    get_pages_error();
}

function check_newspaper_for_errors() {
    $page = filter_var($_POST['page'], FILTER_VALIDATE_INT);
    $query_params = array();
    if (!empty($page)) {
        $query_params['WHERE'] = "ordinal=$page";
    }
    $newspaper_pages = Rposul_Page::get($query_params);

    $errors = False;
    foreach ($newspaper_pages as $pg) {
        $errors = rposul_wp_error_merge($errors, $pg->has_errors());
    }

    $result = rposul_wp_error_merge($errors, Rposul_Newspaper::check_ads($newspaper_pages));
    if (is_wp_error($result)) {
        // Issue #873 Issue #707
        // TODO remove this when resolving issue #707
        $result->remove('warning');
        if (!empty($result->get_error_messages())) {
            wp_send_json_error($result->get_error_messages());
        }
        //wp_send_json_error($result);
    }
    wp_send_json_success();
}

function get_pages_error($pages = array()) {
    if (!$pages) {
        $pages = Rposul_Page::get();
    }
    $pagesidswitherror = array();

    foreach ($pages as $page) {
        $errors = $page->has_errors();
        if (is_wp_error($errors)) {
            $pagesidswitherror[$page->get_id()] = $errors;
        }
    }
    wp_send_json($pagesidswitherror); //send and die	
}
