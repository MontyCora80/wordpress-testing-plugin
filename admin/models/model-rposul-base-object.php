<?php

interface iRposul_Database_Use_Object {

    static function create_table($upgrading_from_version);

    static function delete_table();
}

abstract class Rposul_Base_Object implements iRposul_Database_Use_Object {

    protected static $table_name;

    protected abstract function save();

    protected $id;

    public function get_id() {
        return $this->id;
    }

    public function is_new() {
        return empty($this->id) || $this->id <= 0;
    }

    public function __construct($param_array = array()) {
        $this->id = arr_get($param_array, "id", 0);
        

        if (!isset(static::$table_name)) {
            throw new Exception('$table_name property must be set in child classes');
        }
    }

    /**
     * Deleted the id key from $table_name if this isnt a new object and unsets
     * the current id of this object.
     * 
     * @global type $wpdb The wordpress database object
     * @return boolean True if the delete was successful false otherwise
     */
    public function delete() {
        $returnvalue = static::delete_by_id($this->get_id());
        $this->id = 0;
        return $returnvalue;
    }

    /**
     * 
     * 
     * STATIC FUNCTIONS
     * 
     * 
     */

    /**
     * Retrieve all instances of the inherited class contained in the database
     * through the find function.
     * 
     * @param ArrayObject $args Query args to filter the search
     * @return ArrayObject list of static constructors of this object
     */
    public static function get($args = array()) {
        $all_data = static::find($args);
        $objects = array();
        foreach ($all_data as $data) {
            $objects[] = new static($data);
        }
        return $objects;
    }

    /**
     * Find data though custom queries and returns it.
     * 
     * @global type $wpdb the wordpress database
     * @param type $args query args
     * @return type database results for the query on this object tabel
     */
    protected static function find($args = array()) {
        global $wpdb;
        $sql = add_args_to_sql("SELECT * FROM {$wpdb->prefix}" . static::$table_name, $args);
        return $wpdb->get_results($sql, "ARRAY_A");
    }

    public static function count($args = array()) {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}" . static::$table_name;
        $sql = add_args_to_sql($sql, $args);
        return $wpdb->get_var($sql);
    }

    public static function delete_by_id($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . static::$table_name, array("id" => $id));
    }

}
