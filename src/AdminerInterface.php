<?php

namespace Lagdo\Adminer\Drivers;

interface AdminerInterface
{
    /**
     * Get the request inputs
     *
     * @return InputInterface
     */
    public function input();

    /**
     * Translate string
     * The first parameter is mandatory. Optional parameters can follow.
     *
     * @param string
     *
     * @return string
     */
    public function lang($idf);

    /**
     * Get the database server options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Get SSL connection options
     *
     * @return array
     */
    public function connectSsl();

    /**
     * Select data from table
     *
     * @param array $select
     * @param array $where
     * @param array $group
     * @param array $order
     * @param int $limit
     * @param int $page
     *
     * @return string
     */
    public function buildSelectQuery(array $select, array $where, array $group, array $order = [], $limit = 1, $page = 0);

    /**
     * Execute and remember query
     * @param string or null to return remembered queries, end with ';' to use DELIMITER
     * @return Statement or array($queries, $time) if $query = null
     */
    public function queries($query);

    /**
     * Apply command to all array items
     * @param string
     * @param array
     * @param callback|null
     * @return bool
     */
    public function apply_queries($query, $tables, $escape = null);

    /**
     * Get list of values from database
     * @param string
     * @param mixed
     * @return array
     */
    public function get_vals($query, $column = 0);

    /**
     * Get keys from first column and values from second
     * @param string
     * @param ConnectionInterface
     * @param bool
     * @return array
     */
    public function get_key_vals($query, $connection2 = null, $set_keys = true);

    /**
     * Get all rows of result
     * @param string
     * @param ConnectionInterface
     * @param string
     * @return array of associative arrays
     */
    public function get_rows($query, $connection2 = null);

    /**
     * Get default value clause
     * @param array
     * @return string
     */
    public function default_value($field);

    /**
     * Escape for HTML
     * @param string
     * @return string
     */
    public function h($string);

    /**
     * Check whether the string is in UTF-8
     * @param string
     * @return bool
     */
    public function is_utf8($val);

    /** Check whether the string is e-mail address
    * @param string
    * @return bool
    */
    public function is_mail($email);

    /**
     * Check whether the string is URL address
     * @param string
     * @return bool
     */
    public function is_url($string);

    /**
     * Check if field should be shortened
     * @param array
     * @return bool
     */
    public function is_shortable($field);

    /**
     * Get INI boolean value
     * @param string
     * @return bool
     */
    public function ini_bool($ini);

    /**
     * Get INI bytes value
     * @param string
     * @return int
     */
    public function ini_bytes($ini);

    /**
     * Remove non-digits from a string
     * @param string
     * @return string
     */
    public function number($val);

    /**
     * Get regular expression to match numeric types
     * @return string
     */
    public function number_type();

    /**
     * Format elapsed time
     * @param float output of microtime(true)
     * @return string HTML code
     */
    public function format_time($start);

    /**
     * Format decimal number
     * @param int
     * @return string
     */
    public function format_number($val);

    /**
     * Convert \n to <br>
     * @param string
     * @return string
     */
    public function nl_br($string);

    /**
     * Find unique identifier of a row
     * @param array
     * @param array result of indexes()
     * @return array or null if there is no unique identifier
     */
    public function unique_array($row, $indexes);

    /**
     * Get SET NAMES if utf8mb4 might be needed
     *
     * @param string
     *
     * @return string
     */
    public function set_utf8mb4($create);

    /**
     * Remove current user definer from SQL command
     * @param string
     * @return string
     */
    public function remove_definer($query);

    /**
     * Find out foreign keys for each column
     * @param string
     * @return array array($col => array())
     */
    public function column_foreign_keys($table);

    /**
     * Get select clause for convertible fields
     * @param array
     * @param array
     * @param array
     * @return string
     */
    public function convert_fields($columns, $fields, $select = array());

    /**
     * Get query to compute number of found rows
     * @param string
     * @param array
     * @param bool
     * @param array
     * @return string
     */
    public function count_rows($table, $where, $is_group, $group);

    /**
     * Filter length value including enums
     * @param string
     * @return string
     */
    public function process_length($length);

    /**
     * Create SQL string from field type
     * @param array
     * @param string
     * @return string
     */
    public function process_type($field, $collate = "COLLATE");

    /**
     * Create SQL string from field
     * @param array basic field information
     * @param array information about field type
     * @return array array("field", "type", "NULL", "DEFAULT", "ON UPDATE", "COMMENT", "AUTO_INCREMENT")
     */
    public function process_field($field, $type_field);

    /**
     * Process edit input field
     * @param one field from fields()
     * @param array the user inputs
     * @return string or false to leave the original value
     */
    public function process_input($field, $inputs);

    /**
     * Get referencable tables with single column primary key except self
     * @param string
     * @return array ($table_name => $field)
     */
    public function referencable_primary($self);

    /**
     * Create SQL condition from parsed query string
     * @param array parsed query string
     * @param array
     * @return string
     */
    public function where($where, $fields = []);

    /**
     * Compute fields() from $_POST edit data
     * @param string $primary
     * @return array
     */
    public function fields_from_edit($primary);
}
