<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

const RPOSUL_OPTION_SKIP_PAGES = "rposul_skip_pages";
const RPOSUL_OPTION_INVALIDADE_NEWSPAPER = "rposul_invalidate_newspaper";
const RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS = "rposul_selected_cover_template_options";
const RPOSUL_OPTION_SELECTED_BACKCOVER_TEMPLATE_OPTIONS = "rposul_selected_backcover_template_options";
const RPOSUL_OPTION_NEWSPAPER_DATE = "rposul_newspaper_date";
const RPOSUL_OPTION_ARTICLE_START_DATE = "rposul_article_start_date";
const RPOSUL_OPTION_ARTICLE_END_DATE = "rposul_article_end_date";
const RPOSUL_OPTION_PAGE_TO_GENERATE = "rposul_page_to_generate";
const RPOSUL_OPTION_DEFAULT_HEADER = "rposul_default_header";
const RPOSUL_OPTION_DOUBLE_NEWS_DATE = "rposul_double_news_date";

class RPOSUL_Options {

    private static $default_values = array(
        RPOSUL_OPTION_SKIP_PAGES => array(),
        RPOSUL_OPTION_NEWSPAPER_DATE => "",
        RPOSUL_OPTION_ARTICLE_START_DATE => "",
        RPOSUL_OPTION_ARTICLE_END_DATE => "");

    const TABLE_NAME = "rposul_options";

    public static function clear() {
        
        //Default header should not be  erased on clear
        //The right way to do this is add a column 'transient' on the DB
        //and instead of truncating de table, removing values which 
        //are transient
        //Issue #1086
        $default_header = self::get_option(RPOSUL_OPTION_DEFAULT_HEADER, 0);
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . self::TABLE_NAME);
        self::update_option(RPOSUL_OPTION_DEFAULT_HEADER, $default_header);
    }

    public static function update_option($option, $value) {
        global $wpdb;
        $option = trim($option);
        if (empty($option))
            return false;

        wp_protect_special_option($option);

        if (is_object($value)) {
            $value = clone $value;
        }
        $value = sanitize_option($option, $value);
        $old_value = RPOSUL_Options::get_option($option, false);

        // If the new and old values are the same, no need to update.
        if ($value === $old_value) {
            return false;
        }

        /** This filter is documented in wp-includes/option.php */
        if (false === $old_value) {
            return RPOSUL_Options::add_option($option, $value);
        }

        $serialized_value = maybe_serialize($value);

        $update_args = array(
            'option_value' => $serialized_value,
        );

        $result = $wpdb->update($wpdb->prefix . self::TABLE_NAME, $update_args, array('option_name' => $option));
        if (!$result) {
            return false;
        }

        return true;
    }

    public static function get_option($option, $default = null) {
        global $wpdb;

        $option = trim($option);
        if (empty($option)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors();
        $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM " . $wpdb->prefix . self::TABLE_NAME . " WHERE option_name = %s LIMIT 1", $option));
        $wpdb->suppress_errors($suppress);
        if (is_object($row)) {
            return maybe_unserialize($row->option_value);
        } else {
            if (!isset($default) && array_key_exists($option, self::$default_values)) {
                return self::$default_values[$option];
            } else {
                return $default;
            }
        }
    }

    public static function add_option($option, $value) {
        global $wpdb;
        $option = trim($option);
        if (empty($option))
            return false;

        if (is_object($value))
            $value = clone $value;

        $value = sanitize_option($option, $value);

        $serialized_value = maybe_serialize($value);
        $result = $wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . self::TABLE_NAME . " (`option_name`, `option_value`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`)", $option, $serialized_value));
        if (!$result)
            return false;
        return true;
    }

}
