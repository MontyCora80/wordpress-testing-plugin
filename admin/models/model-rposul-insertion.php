<?php

class Rposul_Insertion extends Rposul_Base_Object {

    protected static $table_name = "rposul_insertion";
    public $name;
    public $page;
    public $attachment_id;
    public $date_created;
    public $date_modified;

    public function save() {
        global $wpdb;

        $data = array(
            'name' => $this->name,
            'page' => $this->page,
            'attachment_id' => $this->attachment_id,
            'date_modified' => current_time('mysql')
        );


        if ($this->is_new()) {
            $page_data['date_created'] = $page_data['date_modified'];

            $returnvalue = $wpdb->insert($wpdb->prefix . static::$table_name, $data);
            if ($returnvalue !== false) {
                $this->id = $wpdb->insert_id;
            } else {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        } else {
            $returnvalue = $wpdb->update(
                    $wpdb->prefix . static::$table_name, $data, array("id" => $this->id));

            if ($returnvalue === false) {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        }
        return true;
    }

    public function __construct($param_array = array()) {
        parent::__construct($param_array);

        $this->name = arr_get($param_array, "name", '');
        $this->page = arr_get($param_array, "page", 2);
        $this->attachment_id = arr_get($param_array, "attachment_id", 0);
        $this->date_created = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_created", null));
        $this->date_modified = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_modified", null));
    }

    public static function truncate_table() {
        global $wpdb;
        $wpdb->query("TRUNCATE {$wpdb->prefix}" . self::$table_name);
    }

    public static function create_table($upgrading_from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . self::$table_name;
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name TINYTEXT NOT NULL,
            page TINYINT NOT NULL,
            attachment_id BIGINT NOT NULL,
            date_modified DATETIME,
            date_created DATETIME,
            UNIQUE KEY id (id)
            ) $charset_collate;";
        dbDelta($sql);
    }

    public static function delete_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$table_name);
    }

}
