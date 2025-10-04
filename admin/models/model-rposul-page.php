<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Rposul_Page extends Rposul_Base_Object {

    public static $table_name = "rposul_pages";
    public static $auxiliary_pageposts_table_name = "rposul_pageposts";
    public static $auxiliary_pageads_table_name = "rposul_pageads";
    public $template_id;
    public $post_ids;
    public $ordinal;
    public $advertisement_ids;
    public $extra_values;    
    public $date_created;
    public $date_modified;

    public function __construct($param_array = array()) {
        parent::__construct($param_array);
        $this->template_id = arr_get($param_array, "template_id");
        $this->ordinal = arr_get($param_array, "ordinal");
        $this->extra_values = maybe_unserialize(arr_get($param_array, "extra_values"));
        if (!$this->extra_values) {
            $this->extra_values = array();
        }

        $this->date_created = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_created", null));
        $this->date_modified = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_modified", null));

        //Retrieving posts
        $pageposts = arr_get($param_array, "pageposts", array());
        $this->post_ids = array();
        foreach ($pageposts as $post) {
            if (isset($this->post_ids[$post['ordinal']])) {
                $this->post_ids[] = $post['post_id'];
            } else {
                $this->post_ids[$post['ordinal']] = $post['post_id'];
            }
        }
        ksort($this->post_ids);
        $this->post_ids = array_values($this->post_ids);

        //Retrieving ads
        $pageads = arr_get($param_array, "pageads", array());
        $this->advertisement_ids = array();
        foreach ($pageads as $ad) {
            if (isset($this->advertisement_ids[$ad['ordinal']])) {
                $this->advertisement_ids[] = $ad['advertisement_id'];
            } else {
                $this->advertisement_ids[$ad['ordinal']] = $ad['advertisement_id'];
            }
        }
        ksort($this->advertisement_ids);
        $this->advertisement_ids = array_values($this->advertisement_ids);
    }

    private function get_automatic_template_id($post_ids = null) {
        if (empty($post_ids)) {
            $post_ids = $this->post_ids;
        }

        if ($this->is_cover()) {
            $cover_options = RPOSUL_Options::get_option(RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS);
            return arr_get($cover_options, 'templateid');
        } else {
            if (arr_get($this->extra_values, 'insertion_id')) {
                return RPOSUL_TEMPLATEID_BLANK;
            }
            switch (count($post_ids)) {
                case 0:
                    return RPOSUL_TEMPLATEID_BLANK;
                case 1:
                    return RPOSUL_TEMPLATEID_ONE_PAGE;
                case 2:
                    return RPOSUL_TEMPLATEID_TWO_PER_PAGE;
                case 3:
                    return RPOSUL_TEMPLATEID_THREE_PER_PAGE;
                case 4:
                    return RPOSUL_TEMPLATEID_FOUR_PER_PAGE;
                default:
                    return RPOSUL_TEMPLATEID_SMALL_NEWS;
            }
        }
    }

    public function has_errors() {
        $template = $this->convert_to_template();
        if (is_wp_error($template)) {
            return $template;
        }
        
        return $template->is_export_ready();            
    }

    public function convert_to_template() {

        $valid_statuses = rposul_valid_post_status();
        $tmp_post_ids = $this->post_ids;
        foreach ($tmp_post_ids as $key => $pid) {
            $status = get_post_status($pid);
            if (empty($status) || !in_array($status, $valid_statuses)) {
                unset($tmp_post_ids[$key]);
            }
        }
        $post_ids = array_values($tmp_post_ids);

        if ($this->template_id !== null) {
            $selected_template = $this->template_id;
        } else {
            $selected_template = $this->get_automatic_template_id($post_ids);
        }

        if ($this->is_cover()) {

            if (DISABLE_COVER_GENERATION) {
                return new TexBlankTemplate(array(), array(), array());
            }

            $cover_options = RPOSUL_Options::get_option(RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS);
            if (!isset($cover_options) || !$cover_options) {
                return new WP_Error("error", "Opções de capa inexistentes.");
            }

            foreach ($cover_options as $key => $value) {
                if (strpos($key, 'news_') !== false && $value === "") {
                    return new WP_Error("error", "Opções de capa inválidas.");
                }
            }

            switch ($selected_template) {
                case RPOSUL_TEMPLATEID_COVER_SIMPLE_FOUR_NEWS:
                    return new TexCoverSimpleFourNews($cover_options, $post_ids);
                case RPOSUL_TEMPLATEID_COVER_BORDERED_IMAGE_FOUR_NEWS:
                    return new TexCoverBorderedImagesFourNews($cover_options, $post_ids);
                case RPOSUL_TEMPLATEID_COVER_BORDERED_IMAGE_FIVE_NEWS:
                    return new TexCoverBorderedImagesFiveNews($cover_options, $post_ids);
                case RPOSUL_TEMPLATEID_COVER_BLANK:
                    return new TexCoverBlankTemplate($cover_options, $post_ids);
                default:
                    return new WP_Error('internal_error', "Id de template não suportado");
            }
        } else {
            switch ($selected_template) {
                case RPOSUL_TEMPLATEID_THREE_LINEAR_POSTS:
                    return new TexThreeLinearPostsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_ONE_PAGE:
                    return new TexOnePostTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_TWO_PER_PAGE:
                    return new TexTwoPostsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_FOUR_PER_PAGE:
                    return new TexFourPostsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_THREE_PER_PAGE:
                    return new TexThreePostsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_COLUMNISTS:
                    return new TexColumnistsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_SMALL_NEWS:
                    return new TexSmallPostsTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                case RPOSUL_TEMPLATEID_BLANK:
                    return new TexBlankTemplate($post_ids, $this->advertisement_ids, $this->extra_values);
                default:
                    return new WP_Error('internal_error', "Id de template não suportado");
            }
        }
    }

    public function is_cover() {
        return arr_get($this->extra_values, 'is_cover', false);
    }

    /**
     * Saves the data from this object and inserts it into the databse 
     * if this is a new object or updates it if it is not.
     * Also sets the current id of this object if the operation was successful.
     * 
     * @global type $wpdb The wordpress database object
     * @return boolean True if the save was successful false otherwise
     */
    public function save() {
        global $wpdb;

        $page_data = array(
            'template_id' => $this->template_id,
            'extra_values' => maybe_serialize($this->extra_values),
            'ordinal' => $this->ordinal,
            'date_modified' => current_time('mysql')
        );
        if ($this->is_new()) {
            $page_data['date_created'] = $page_data['date_modified'];

            if (!$this->ordinal) {
                $this->ordinal = $wpdb->get_var("SELECT MAX(ordinal) FROM " . $wpdb->prefix . static::$table_name) + 1;
                $page_data['ordinal'] = $this->ordinal;
            }
            $returnvalue = $wpdb->insert($wpdb->prefix . static::$table_name, $page_data);
            if ($returnvalue !== false) {
                $this->id = $wpdb->insert_id;
            } else {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        } else {

            $returnvalue = $wpdb->update(
                    $wpdb->prefix . static::$table_name, $page_data, array("id" => $this->id));

            if ($returnvalue === false) {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        }

        self::delete_pageposts_by_page_id($this->id);
        foreach ($this->post_ids as $key => $value) {
            $pagepost_data = array(
                'page_id' => $this->id,
                'post_id' => $value,
                'ordinal' => $key
            );

            $returnvalue = $wpdb->insert($wpdb->prefix . static::$auxiliary_pageposts_table_name, $pagepost_data);
            if ($returnvalue === false) {
                return new Wp_Error('base', 'Houve um erro ao salvar os posts selecionados.');
            }
        }

        self::delete_pageads_by_page_id($this->id);
        foreach ($this->advertisement_ids as $key => $value) {
            $pageads_data = array(
                'page_id' => $this->id,
                'advertisement_id' => $value,
                'ordinal' => $key
            );

            $returnvalue = $wpdb->insert($wpdb->prefix . static::$auxiliary_pageads_table_name, $pageads_data);
            if ($returnvalue === false) {
                return new Wp_Error('base', 'Houve um erro ao salvar os posts selecionados.');
            }
        }
        return true;
    }

    protected static function find($args = array()) {
        if (!isset($args["ORDER BY"])) {
            $args['ORDER BY'] = 'ordinal ASC';
        }
        $pages_values = parent::find($args);
        for ($index = 0; $index < count($pages_values); $index++) {
            $pages_values[$index]['pageposts'] = self::find_pageposts(array("WHERE" => "page_id={$pages_values[$index]['id']}", "ORDER BY" => "ordinal ASC"));
            $pages_values[$index]['pageads'] = self::find_pageads(array("WHERE" => "page_id={$pages_values[$index]['id']}", "ORDER BY" => "ordinal ASC"));
        }
        return $pages_values;
    }

    public static function find_pageposts($args = array()) {
        global $wpdb;
        return $wpdb->get_results(add_args_to_sql("SELECT * FROM " . $wpdb->prefix . self::$auxiliary_pageposts_table_name, $args), "ARRAY_A");
    }

    public static function find_pageads($args = array()) {
        global $wpdb;
        return $wpdb->get_results(add_args_to_sql("SELECT * FROM " . $wpdb->prefix . self::$auxiliary_pageads_table_name, $args), "ARRAY_A");
    }

    public static function delete_pageposts_by_page_id($page_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . self::$auxiliary_pageposts_table_name, array("page_id" => $page_id));
    }

    public static function delete_pageads_by_page_id($page_id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . self::$auxiliary_pageads_table_name, array("page_id" => $page_id));
    }

    public static function delete_by_id($id) {
        global $wpdb;
        RPOSUL_Options::update_option(RPOSUL_OPTION_INVALIDADE_NEWSPAPER, true);
        return $wpdb->delete($wpdb->prefix . self::$table_name, array("id" => $id)) &&
                self::delete_pageposts_by_page_id($id) && self::delete_pageads_by_page_id($id);
    }

    /**
     * Truncate all tables related to this class
     * 
     * @global type $wpdb
     */
    public static function delete_all() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}" . self::$table_name);
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}" . self::$auxiliary_pageposts_table_name);
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}" . self::$auxiliary_pageads_table_name);
    }

    public static function create_table($upgrading_from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . self::$table_name;
        $sql = "CREATE TABLE $table_name (
		  id BIGINT(9) NOT NULL AUTO_INCREMENT,
		  template_id SMALLINT(6) NULL,
		  extra_values LONGTEXT NULL,
                  ordinal TINYINT,
                  date_modified DATETIME,
                  date_created DATETIME,
		  UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($sql);

        $auxiliary_table_name = $wpdb->prefix . self::$auxiliary_pageposts_table_name;
        $auxiliary_sql = "CREATE TABLE $auxiliary_table_name (
		  id BIGINT(9) NOT NULL AUTO_INCREMENT,
                  page_id BIGINT(9) NOT NULL,
		  post_id BIGINT NOT NULL,
		  ordinal TINYINT(4) NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($auxiliary_sql);

        $auxiliary_table_name = $wpdb->prefix . self::$auxiliary_pageads_table_name;
        $auxiliary_sql = "CREATE TABLE $auxiliary_table_name (
		  id BIGINT(9) NOT NULL AUTO_INCREMENT,
                  page_id BIGINT(9) NOT NULL,
		  advertisement_id BIGINT NOT NULL,
		  ordinal TINYINT(4) NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";
        dbDelta($auxiliary_sql);
    }

    public static function delete_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$table_name);
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$auxiliary_pageposts_table_name);
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$auxiliary_pageads_table_name);
    }

}
