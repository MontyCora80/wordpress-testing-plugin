<?php

class Rposul_Header extends Rposul_Base_Object {

    protected static $table_name = "rposul_headers";
    public $name;
    public $type;
    public $content;
    public $enabled;
    public $headrule_enabled;
    public $date_created;
    public $date_modified;

    public function save() {
        global $wpdb;

        $data = array(
            'name' => $this->name,
            'type' => $this->type,
            'enabled' => $this->enabled,
            'headrule' => $this->headrule_enabled,
            'content' => maybe_serialize($this->content),
            'date_modified' => current_time('mysql')
        );


        if ($this->is_new()) {
            $data['date_created'] = $data['date_modified'];
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

        if (empty(Rposul_Options::get_option(RPOSUL_OPTION_DEFAULT_HEADER))) {
            Rposul_Options::add_option(RPOSUL_OPTION_DEFAULT_HEADER, $this->id);
        }
        return true;
    }

    public function __construct($param_array = array()) {
        parent::__construct($param_array);

        $this->name = arr_get($param_array, "name", '');
        $this->type = arr_get($param_array, "type", 'single');
        $this->enabled = arr_get($param_array, "enabled", true);
        $this->headrule_enabled = arr_get($param_array, "headrule", true);
        $this->content = maybe_unserialize(arr_get($param_array, "content", array()));
        $this->date_created = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_created", null));
        $this->date_modified = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_modified", null));
    }

    public static function create_table($upgrading_from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . self::$table_name;
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name TINYTEXT NOT NULL,
            enabled TINYINT(1) NOT NULL,
            headrule TINYINT(1) NOT NULL,
            type TINYTEXT NOT NULL,
            content LONGTEXT NOT NULL,
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
