<?php

class Rposul_Advertisement extends Rposul_Base_Object {

    protected static $table_name = "rposul_ads";
    protected static $auxiliary_table_name = "rposul_ads_occurrences";
    public static $PLACEMENT_OPTIONS = array('start' => 'Início', 'end' => "Fim");
    public static $TYPE_OPTIONS = array('full' => 'Página completa', 'bottom' => "Inferior página");
    public static $TYPE_COMPATIBILITY = array(
        'full' => null,
        'bottom' => array(RPOSUL_TEMPLATEID_ONE_PAGE, RPOSUL_TEMPLATEID_SMALL_NEWS)
    );

    // CAREFUL.... IF WE CHANGE THIS WE MAY BREAK THE DATABASE
    const DATE_FORMAT = "d-m-y";

    public $title;
    public $startdate;
    public $enddate;
    public $schedule;
    public $frequency;
    public $meta;
    public $include;
    public $exclude;
    public $image_id;
    public $section_id;
    public $relative_placement;
    public $type;
    public $page;
    public $date_created;
    public $date_modified;

    public static function is_templateid_compatible($ad_type, $templateid) {
        $options = self::$TYPE_COMPATIBILITY[$ad_type];
        if ($options == null) {
            return true;
        } else {
            return in_array($templateid, $options);
        }
    }

    public function delete() {
        if ($this->delete_occurrences_data() !== false) {
            Rposul_Newspaper::remove_advertisement($this);
            RPOSUL_Options::update_option(RPOSUL_OPTION_INVALIDADE_NEWSPAPER, true);
            parent::delete();
        } else {
            return false;
        }
    }

    private function delete_occurrences_data() {
        global $wpdb;
        if (!$this->is_new()) {
            $returnvalue = $wpdb->delete($wpdb->prefix . self::$auxiliary_table_name, array("ad_id" => $this->get_id()));
            if ($returnvalue === false) {
                return false;
            }
        }
        return true;
    }

    public function save() {
        global $wpdb;

        //If schedule is 'once' and dates are included - set to 'custom':
        if (( empty($this->schedule) || 'once' == $this->schedule ) && !empty($this->include)) {
            $this->schedule = 'custom';
        }

        $ad_data = array(
            'title' => $this->title,
            'startdate' => $this->startdate->format(self::DATE_FORMAT),
            'enddate' => $this->enddate->format(self::DATE_FORMAT),
            'schedule' => $this->schedule,
            'frequency' => $this->frequency,
            'meta' => maybe_serialize($this->meta),
            'include' => $this->include,
            'exclude' => $this->exclude,
            'image_id' => $this->image_id,
            'section_id' => $this->section_id,
            'relative_placement' => $this->relative_placement,
            'type' => $this->type,
            'page' => $this->page,
            'date_modified' => current_time('mysql')
        );


        if ($this->is_new()) {
            $ad_data['date_created'] = $ad_data['date_modified'];
            $returnvalue = $wpdb->insert($wpdb->prefix . static::$table_name, $ad_data);
            if ($returnvalue !== false) {
                $this->id = $wpdb->insert_id;
            } else {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        } else {

            $returnvalue = $wpdb->update(
                    $wpdb->prefix . static::$table_name, $ad_data, array("id" => $this->id));

            if ($returnvalue === false) {
                return new Wp_Error('base', 'N&atilde;o foi poss&iacute;vel salvar.');
            }
        }

        //its overkill but i think its best that we delete all occurrences data
        //and after readd it to make sure everything is fine...
        //someday this can be improved
        $this->delete_occurrences_data();
        $occurrences = $this->calculate_occurrences_dates();
        foreach ($occurrences as $date) {
            $occ_data = array(
                'ad_id' => $this->get_id(),
                'date' => $date->format(self::DATE_FORMAT)
            );

            $returnvalue = $wpdb->insert($wpdb->prefix . static::$auxiliary_table_name, $occ_data);
        }

        RPOSUL_Options::update_option(RPOSUL_OPTION_INVALIDADE_NEWSPAPER, true);
        return true;
    }

    private function convert_include_exclude_to_date_array($data) {
        $filtereddata = array_filter(explode(',', $data), function($value) {
            return trim($value) !== '';
        });
        array_walk($filtereddata, function(&$value) {
            $value = DateTime::createFromFormat(Rposul_Advertisement::DATE_FORMAT, trim($value));
        });
        $filtereddata = array_filter($filtereddata, function($value) {
            return $value !== false;
        });
        return $filtereddata;
    }

    private function calculate_occurrences_dates() {

        $start = $this->startdate;
        $end = $this->enddate;
        $until = $this->enddate;
        $schedule_meta = $this->meta;

        $occurrences = array(); //occurrences array



        $include = $this->convert_include_exclude_to_date_array($this->include);
        $exclude = $this->convert_include_exclude_to_date_array($this->exclude);

        $exclude = array_udiff($exclude, $include, 'rposul_compare_date');
        $include = array_udiff($include, $exclude, 'rposul_compare_date');

        //White list schedule
        if (!in_array($this->schedule, array('once', 'daily', 'weekly', 'monthly', 'yearly', 'custom'))) {
            return new WP_Error('calculate_occurrences_dates', __('Schedule not recognised.'));
        }

        //Ensure event frequency is a positive integer. Else set to 1.
        $frequency = max(absint($this->frequency), 1);
        $all_day = 1;
        $number_occurrences = 0;

        //Check dates are supplied and are valid
        if (!( $start instanceof DateTime )) {
            return new WP_Error('calculate_occurrences_dates', __('Start date not provided.'));
        }

        if (!( $end instanceof DateTime )) {
            $end = clone $start;
        }

        //If use 'number_occurrences' to limit recurring event, set dummy 'schedule_last' date.
        if (!( $until instanceof DateTime ) && $number_occurrences && in_array($this->schedule, array('daily', 'weekly', 'monthly', 'yearly'))) {
            //Set dummy "last occurrance" date.
            $until = clone $start;
        } else {
            $number_occurrences = 0;
        }

        if ('once' == $this->schedule || 'custom' == $this->schedule || !( $until instanceof DateTime )) {
            $until = clone $start;
            $end = clone $start;
        }

        //Check dates are in chronological order        
        if ($end < $start) {
            return new WP_Error('calculate_occurrences_dates', __('Start date occurs after end date.'));
        }

        if ($until < $start) {
            return new WP_Error('calculate_occurrences_dates', __('Schedule end date is before is before the start date.'));
        }


        //Now set timezones
        $timezone = rposul_get_timezone();
        $start->setTimezone($timezone);
        $end->setTimezone($timezone);
        $until->setTimezone($timezone);
        $hour = intval($start->format('H'));
        $min = intval($start->format('i'));

        $start_days = array();
        $workaround = '';
        $icaldays = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
        $weekdays = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $ical2day = array('SU' => 'Sunday', 'MO' => 'Monday', 'TU' => 'Tuesday', 'WE' => 'Wednesday', 'TH' => 'Thursday', 'FR' => 'Friday', 'SA' => 'Saturday');

        //Set up schedule
        switch ($this->schedule) :
            case 'once':
            case 'custom':
                $frequency = 1;
                $schedule_meta = '';
                $until = clone $start;
                $start_days[] = clone $start;
                $workaround = 'once'; //Not strictly a workaround.
                break;

            case 'daily':
                $interval = sprintf('+%d day', $frequency);
                $start_days[] = clone $start;
                break;

            case 'weekly':
                $schedule_meta = ( $schedule_meta ? array_filter($schedule_meta) : array() );
                if (!empty($schedule_meta) && is_array($schedule_meta)) :
                    foreach ($schedule_meta as $day) :
                        $start_day = clone $start;
                        $start_day->modify($ical2day[$day]);
                        $start_days[] = $start_day;
                    endforeach;
                else :
                    $schedule_meta = array($icaldays[$start->format('w')]);
                    $start_days[] = clone $start;
                endif;

                $interval = sprintf('+%d week', $frequency);
                break;

            case 'monthly':
                $start_days[] = clone $start;
                $rule_value = explode('=', $schedule_meta, 2);
                $rule = $rule_value[0];
                $values = !empty($rule_value[1]) ? explode(',', $rule_value[1]) : array(); //Should only be one value, but may support more in future
                $values = array_filter($values);

                if ('BYMONTHDAY' == $rule) :
                    $date = (int) $start_days[0]->format('d');
                    $interval = sprintf('+%d month', $frequency);

                    if ($date >= 29) {
                        $workaround = 'short months';    //This case deals with 29/30/31 of month
                    }

                    $schedule_meta = 'BYMONTHDAY=' . $date;

                else :
                    if (empty($values)) {
                        $date = (int) $start_days[0]->format('d');
                        $n = ceil($date / 7); // nth weekday of month.
                        $day_num = intval($start_days[0]->format('w')); //0 (Sun) - 6(Sat)
                    } else {
                        //expect e.g. array( 2MO )
                        preg_match('/^(-?\d{1,2})([a-zA-Z]{2})/', $values[0], $matches);
                        $n = (int) $matches[1];
                        $day_num = array_search($matches[2], $icaldays); //(Sun) - 6(Sat)
                    }

                    if (5 == $n) {
                        $n = -1; //If 5th, interpret it as last.
                    }
                    $ordinal = array('1' => 'first', '2' => 'second', '3' => 'third', '4' => 'fourth', '-1' => 'last');

                    if (!isset($ordinal[$n])) {
                        return new WP_Error('calculate_occurrences_dates', __('Invalid monthly schedule (invalid ordinal)'));
                    }

                    $ical_day = $icaldays[$day_num];  //ical day from day_num (SU - SA)
                    $day = $weekdays[$day_num]; //Full day name from day_num (Sunday -Monday)
                    $schedule_meta = 'BYDAY=' . $n . $ical_day; //E.g. BYDAY=2MO
                    $interval = $ordinal[$n] . ' ' . $day . ' of +' . $frequency . ' month'; //E.g. second monday of +1 month
                    //Work around for PHP <5.3
                    if (!function_exists('date_diff')) {
                        $workaround = 'php5.2';
                    }
                endif;
                break;

            case 'yearly':
                $start_days[] = clone $start;
                if ('29-02' == $start_days[0]->format('d-m')) {
                    $workaround = 'leap year';
                }
                $interval = sprintf('+%d year', $frequency);
                break;
        endswitch; //End $schedule['schedule'] switch
        //Now we have setup and validated the schedules - loop through and generate occurrences
        foreach ($start_days as $index => $start_day) :
            $current = clone $start_day;
            $occurrence_n = 0;

            switch ($workaround) :
                //Not really a workaround. Just add the occurrence and finish.
                case 'once':
                    $current->setTime($hour, $min);
                    $occurrences[] = clone $current;
                    break;

                //Loops for monthly events that require php5.3 functionality
                case 'php5.2':
                    while ($current <= $until || $occurrence_n < $number_occurrences) :
                        $current->setTime($hour, $min);
                        $occurrences[] = clone $current;
                        $current = _eventorganiser_php52_modify($current, $interval);
                        $occurrence_n++;
                    endwhile;
                    break;

                //Loops for monthly events on the 29th/30th/31st
                case 'short months':
                    $day_int = intval($start_day->format('d'));

                    //Set the first month
                    $current_month = clone $start_day;
                    $current_month = date_create($current_month->format('Y-m-1'));

                    while ($current_month <= $until || $occurrence_n < $number_occurrences) :
                        $month_int = intval($current_month->format('m'));
                        $year_int = intval($current_month->format('Y'));

                        if (checkdate($month_int, $day_int, $year_int)) {
                            $current = new DateTime($day_int . '-' . $month_int . '-' . $year_int, $timezone);
                            $current->setTime($hour, $min);
                            $occurrences[] = clone $current;
                            $occurrence_n++;
                        }
                        $current_month->modify($interval);
                    endwhile;
                    break;

                //To be used for yearly events occuring on Feb 29
                case 'leap year':
                    $current_year = clone $current;
                    $current_year->modify('-1 day');

                    while ($current_year <= $until || $occurrence_n < $number_occurrences) :
                        $is_leap_year = (int) $current_year->format('L');

                        if ($is_leap_year) {
                            $current = clone $current_year;
                            $current->modify('+1 day');
                            $current->setTime($hour, $min);
                            $occurrences[] = clone $current;
                            $occurrence_n++;
                        }

                        $current_year->modify($interval);
                    endwhile;
                    break;

                default:
                    while ($current <= $until || $occurrence_n < $number_occurrences) :
                        $current->setTime($hour, $min);
                        $occurrences[] = clone $current;
                        $current->modify($interval);
                        $occurrence_n++;
                    endwhile;
                    break;

            endswitch; //End 'workaround' switch;
        endforeach;

        //Now schedule meta is set up and occurrences are generated.
        if ($number_occurrences > 0) {
            //If recurrence is limited by #occurrences. Do that here.
            sort($occurrences);
            $occurrences = array_slice($occurrences, 0, $number_occurrences);
            $until = end($occurrences);
        }

        //Cast includes/exclude to timezone
        $tz = rposul_get_timezone();
        if ($include) {
            foreach ($include as $included_date) {
                $included_date->setTimezone($tz);
            }
        }
        if ($exclude) {
            foreach ($exclude as $excluded_date) {
                $excluded_date->setTimezone($tz);
            }
        }

        //Add inclusions, removes exceptions and duplicates
        if (defined('WP_DEBUG') && WP_DEBUG) {
            //Make sure 'included' dates doesn't appear in generate date
            $include = array_udiff($include, $occurrences, 'rposul_compare_date');
        }
        $occurrences = array_merge($occurrences, $include);
        $occurrences = array_udiff($occurrences, $exclude, 'rposul_compare_date');
        $occurrences = remove_duplicates_datetime($occurrences);

        //Sort occurrences
        sort($occurrences);

        if (empty($occurrences) || !$occurrences[0] || !( $occurrences[0] instanceof DateTime )) {
            return new WP_Error('calculate_occurrences_dates', __('Event does not contain any dates.'));
        }

        return $occurrences;
    }

    public function __construct($param_array = array()) {
        parent::__construct($param_array);
        $startdate_data = arr_get($param_array, "startdate") != null ? DateTime::createFromFormat(self::DATE_FORMAT, $param_array['startdate']) : new DateTime('now');
        $enddate_data = arr_get($param_array, "enddate") != null ? DateTime::createFromFormat(self::DATE_FORMAT, $param_array['enddate']) : new DateTime('now');

        $this->title = arr_get($param_array, "title", '');
        $this->startdate = $startdate_data;
        $this->enddate = $enddate_data;
        $this->schedule = arr_get($param_array, "schedule", 'once');
        $this->frequency = arr_get($param_array, "frequency", 1);
        $this->meta = maybe_unserialize(arr_get($param_array, "meta"));
        $this->include = arr_get($param_array, "include", '');
        $this->exclude = arr_get($param_array, "exclude", '');
        $this->image_id = arr_get($param_array, "image_id", '');
        $this->section_id = arr_get($param_array, "section_id", '0');
        $this->type = arr_get($param_array, "type", 'full');
        $this->page = arr_get($param_array, "page", 2);
        $placement_values = array_values(self::$PLACEMENT_OPTIONS);
        $this->relative_placement = arr_get($param_array, "relative_placement", $placement_values[0]);

        $this->date_created = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_created", null));
        $this->date_modified = rposul_get_datetime_from_mysqldatetime(arr_get($param_array, "date_modified", null));
    }

    public function import_from_post_data() {
        //Collect raw data
        $raw_data = ( isset($_POST['eo_input']) ? $_POST['eo_input'] : array() );

        $raw_data['upload_image_input'] = arr_get($_POST, 'upload_image_input');
        $raw_data['ad_title'] = arr_get($_POST, 'ad_title');

        $placement_values = array_values(self::$PLACEMENT_OPTIONS);
        $type_values = array_values(self::$TYPE_OPTIONS);
        $parsed_raw_data = wp_parse_args($raw_data, array(
            'StartDate' => '', 'schedule' => 'once', 'event_frequency' => 1, 'upload_image_input' => '',
            'schedule_end' => '', 'schedule_meta' => '', 'days' => array(), 'include' => '', 'exclude' => '',
            'section' => 0, 'relative_placement' => $placement_values[0], 'type' => $type_values[0], 'page' => '3'
        ));

        $meta = $parsed_raw_data['schedule_meta'] != '' ? $parsed_raw_data['schedule_meta'] : $parsed_raw_data['days'];
        $startdate = $parsed_raw_data['StartDate'] == '' ? new DateTime('now') : DateTime::createFromFormat(self::DATE_FORMAT, $parsed_raw_data['StartDate']);
        $enddate = $parsed_raw_data['schedule_end'] == '' ? new DateTime('now') : DateTime::createFromFormat(self::DATE_FORMAT, $parsed_raw_data['schedule_end']);

        $this->title = $parsed_raw_data['ad_title'];
        $this->type = $parsed_raw_data['type'];
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->schedule = $parsed_raw_data['schedule'];
        $this->frequency = $parsed_raw_data['event_frequency'];
        $this->meta = $meta;
        $this->include = $parsed_raw_data['include'];
        $this->exclude = $parsed_raw_data['exclude'];
        $this->image_id = $parsed_raw_data['upload_image_input'];
        $this->section_id = $parsed_raw_data['section'];
        $this->relative_placement = $parsed_raw_data['relative_placement'];
        $this->page = $parsed_raw_data['page'];
    }

    /**
     * Returns a string representing the reocurrences summary of this advertisement
     * 
     * @return String The summary
     */
    public function get_summary() {
        global $wp_locale;

        $ical2day = array(
            'SU' => $wp_locale->weekday[0], 'MO' => $wp_locale->weekday[1],
            'TU' => $wp_locale->weekday[2], 'WE' => $wp_locale->weekday[3],
            'TH' => $wp_locale->weekday[4], 'FR' => $wp_locale->weekday[5],
            'SA' => $wp_locale->weekday[6]);

        $return = '';

        switch ($this->schedule):
            case 'once':
                $return = 'apenas uma vez';
                break;
            case 'custom':
                $return = 'customizado';
                break;
            case 'daily':
                $return .= ($this->frequency == 1) ? 'todos os dias' : sprintf('a cada %d dias', $this->frequency);
                break;

            case 'weekly':
                $return .= ($this->frequency == 1) ? 'toda semana na' : sprintf('a cada %d semanas na', $this->frequency);
                foreach ($this->meta as $ical_day) {
                    $days[] = $ical2day[$ical_day];
                }
                $return .= ' ' . implode(', ', $days);
                break;

            case 'monthly':
                $bymonthday = preg_match('/^BYMONTHDAY=/', $this->meta);
                if ($bymonthday) {
                    $return .= ($this->frequency == 1) ? 'todo mês no dia' : sprintf('a cada %d mêses no dia', $this->frequency);
                    $return .= ' ' . $this->startdate->format('j');
                } else {
                    //byday                        
                    $return .= $this->frequency == 1 ? 'todo mês na' : sprintf('a cada %d mêses na', $this->frequency);
                    $return .= ' ';


                    //$n = intval($matches[1]) + 1;
                    $week_occurrence = get_weekday_occurrence_for_month($this->startdate);
                    $nth = array('primeira', 'segunda', 'terceira', 'quarta');
                    $return .= ($week_occurrence == -1) ? "última" : $nth[$week_occurrence];
                    $return .= ' ' . $wp_locale->weekday[$this->startdate->format('w')];
                }
                break;
            case 'yearly':
                $return .= ($this->frequency == 1) ? 'todos os anos' : sprintf('a cada %d anos', $this->frequency);
                break;

        endswitch;
        return $return;
    }

    public static function get_from_today($args = array(), $date = null) {
        global $wpdb;
        if (!empty($date)) {
            $datetime = $date;
        } else {
            $datetime = DateTime::createFromFormat(RPOSUL_DATE_FORMAT, RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE));
        }
        if (empty($datetime)) {
            return array();
        }

        $main_table = $wpdb->prefix . self::$table_name;
        $aux_table = $wpdb->prefix . self::$auxiliary_table_name;
        $datetime_string = $datetime->format(self::DATE_FORMAT);
        $args['SELECT'] = " rp.* FROM $main_table as rp LEFT JOIN $aux_table as ao ON rp.id=ao.ad_id AND ao.date='$datetime_string'";
        if (array_key_exists('WHERE', $args)) {
            $args['WHERE'] .= " AND ao.id IS NOT NULL";
        } else {
            $args['WHERE'] = "ao.id IS NOT NULL";
        }
        return self::get($args);
    }

    public static function get_count_from_today($args = array(), $date = null) {
        global $wpdb;
        if (!empty($date)) {
            $datetime = $date;
        } else {
            $datetime = DateTime::createFromFormat(RPOSUL_DATE_FORMAT, RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE));
        }
        if (empty($datetime)) {
            return null;
        }

        $main_table = $wpdb->prefix . self::$table_name;
        $aux_table = $wpdb->prefix . self::$auxiliary_table_name;
        $datetime_string = $datetime->format(self::DATE_FORMAT);
        $args['SELECT'] = " COUNT(*) FROM $main_table as rp LEFT JOIN $aux_table as ao ON rp.id=ao.ad_id AND ao.date='$datetime_string'";
        if (array_key_exists('WHERE', $args)) {
            $args['WHERE'] .= " AND ao.id IS NOT NULL";
        } else {
            $args['WHERE'] = "ao.id IS NOT NULL";
        }

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}" . static::$table_name;
        $sql = add_args_to_sql($sql, $args);
        return $wpdb->get_var($sql);
    }

    public static function create_table($upgrading_from_version) {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . self::$table_name;
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            title TINYTEXT NOT NULL,
            startdate TEXT NOT NULL,
            enddate TEXT NOT NULL,
            schedule TINYTEXT NOT NULL,
            frequency TINYTEXT NOT NULL,
            meta TINYTEXT NOT NULL,
            include LONGTEXT NOT NULL,
            exclude LONGTEXT NOT NULL,
            image_id BIGINT(20) NOT NULL,
            section_id BIGINT(20) NOT NULL,
            type TINYTEXT NOT NULL,
            relative_placement TINYTEXT NOT NULL,
            page TINYINT NOT NULL,
            date_modified DATETIME,
            date_created DATETIME,
            UNIQUE KEY id (id)
            ) $charset_collate;";
        dbDelta($sql);


        $aux_table_name = $wpdb->prefix . self::$auxiliary_table_name;
        $aux_sql = "CREATE TABLE $aux_table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            ad_id BIGINT(20) NOT NULL,
            date TEXT NOT NULL,
            UNIQUE KEY id (id)
            ) $charset_collate;";
        dbDelta($aux_sql);
    }

    public static function delete_table() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$table_name);
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::$auxiliary_table_name);
    }

}
