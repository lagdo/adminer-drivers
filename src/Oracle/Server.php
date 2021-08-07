<?php

namespace Lagdo\Adminer\Drivers\Oracle;

use Lagdo\Adminer\Drivers\AbstractServer;

class Server extends AbstractServer
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "Oracle (beta)";
    }

    /**
     * @inheritDoc
     */
    protected function createConnection()
    {
        if(($this->connection))
        {
            // Do not create if it already exists
            return;
        }

        if(extension_loaded("oci8"))
        {
            $this->connection = new Oci\Connection($this->adminer, $this, 'oci8');
        }
        if(extension_loaded("pdo_oci"))
        {
            $this->connection = new Pdo\Connection($this->adminer, $this, 'PDO_OCI');
        }
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        if (!$this->connection) {
            return null;
        }

        list($server, $username, $password) = $this->adminer->credentials();
        if (!$this->connection->open($server, \compact('username', 'password'))) {
            return $this->connection->error;
        }

        $this->driver = new Driver($this->adminer, $this, $this->connection);
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function idf_escape($idf)
    {
        return '"' . str_replace('"', '""', $idf) . '"';
    }

    public function get_databases($flush) {
        return $this->adminer->get_vals("SELECT tablespace_name FROM user_tablespaces ORDER BY 1");
    }

    public function limit($query, $where, $limit, $offset = 0, $separator = " ") {
        return ($offset ? " * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $query$where) t " .
            "WHERE rownum <= " . ($limit + $offset) . ") WHERE rnum > $offset" :
            ($limit !== null ? " * FROM (SELECT $query$where) WHERE rownum <= " . ($limit + $offset) :
            " $query$where"
        ));
    }

    public function limit1($table, $query, $where, $separator = "\n") {
        return " $query$where"; //! limit
    }

    public function db_collation($db, $collations) {
        return $this->connection->result("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'"); //! respect $db
    }

    public function logged_user() {
        return $this->connection->result("SELECT USER FROM DUAL");
    }

    public function get_current_db() {
        $db = $this->connection->_current_db ? $this->connection->_current_db : $this->getCurrentDatabase();
        unset($this->connection->_current_db);
        return $db;
    }

    public function where_owner($prefix, $owner = "owner") {
        if (!$this->schema) {
            return '';
        }
        return "$prefix$owner = sys_context('USERENV', 'CURRENT_SCHEMA')";
    }

    public function views_table($columns) {
        $owner = where_owner('');
        return "(SELECT $columns FROM all_views WHERE " . ($owner ? $owner : "rownum < 0") . ")";
    }

    public function tables_list() {
        $view = views_table("view_name");
        $owner = where_owner(" AND ");
        return $this->adminer->get_key_vals("SELECT table_name, 'table' FROM all_tables WHERE tablespace_name = " . $this->q($this->getCurrentDatabase()) . "$owner
UNION SELECT view_name, 'view' FROM $view
ORDER BY 1"
        ); //! views don't have schema
    }

    public function count_tables($databases) {
        $return = [];
        foreach ($databases as $db) {
            $return[$db] = $this->connection->result("SELECT COUNT(*) FROM all_tables WHERE tablespace_name = " . $this->q($db));
        }
        return $return;
    }

    public function table_status($name = "", $fast = false) {
        $return = [];
        $search = $this->q($name);
        $db = get_current_db();
        $view = views_table("view_name");
        $owner = where_owner(" AND ");
        foreach ($this->adminer->get_rows('SELECT table_name "Name", \'table\' "Engine", avg_row_len * num_rows "Data_length", num_rows "Rows" FROM all_tables WHERE tablespace_name = ' . $this->q($db) . $owner . ($name != "" ? " AND table_name = $search" : "") . "
UNION SELECT view_name, 'view', 0, 0 FROM $view" . ($name != "" ? " WHERE view_name = $search" : "") . "
ORDER BY 1"
        ) as $row) {
            if ($name != "") {
                return $row;
            }
            $return[$row["Name"]] = $row;
        }
        return $return;
    }

    public function is_view($table_status) {
        return $table_status["Engine"] == "view";
    }

    public function fk_support($table_status) {
        return true;
    }

    public function fields($table) {
        $return = [];
        $owner = where_owner(" AND ");
        foreach ($this->adminer->get_rows("SELECT * FROM all_tab_columns WHERE table_name = " . $this->q($table) . "$owner ORDER BY column_id") as $row) {
            $type = $row["DATA_TYPE"];
            $length = "$row[DATA_PRECISION],$row[DATA_SCALE]";
            if ($length == ",") {
                $length = $row["CHAR_COL_DECL_LENGTH"];
            } //! int
            $return[$row["COLUMN_NAME"]] = array(
                "field" => $row["COLUMN_NAME"],
                "full_type" => $type . ($length ? "($length)" : ""),
                "type" => strtolower($type),
                "length" => $length,
                "default" => $row["DATA_DEFAULT"],
                "null" => ($row["NULLABLE"] == "Y"),
                //! "auto_increment" => false,
                //! "collation" => $row["CHARACTER_SET_NAME"],
                "privileges" => array("insert" => 1, "select" => 1, "update" => 1),
                //! "comment" => $row["Comment"],
                //! "primary" => ($row["Key"] == "PRI"),
            );
        }
        return $return;
    }

    public function indexes($table, $connection2 = null) {
        $return = [];
        $owner = where_owner(" AND ", "aic.table_owner");
        foreach ($this->adminer->get_rows("SELECT aic.*, ac.constraint_type, atc.data_default
FROM all_ind_columns aic
LEFT JOIN all_constraints ac ON aic.index_name = ac.constraint_name AND aic.table_name = ac.table_name AND aic.index_owner = ac.owner
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = " . $this->q($table) . "$owner
ORDER BY ac.constraint_type, aic.column_position", $connection2) as $row) {
            $index_name = $row["INDEX_NAME"];
            $column_name = $row["DATA_DEFAULT"];
            if ($column_name) {
                $column_name = $this->idf_unescape($column_name);
            } else {
                $column_name = $row["COLUMN_NAME"];
            }
            $return[$index_name]["type"] = ($row["CONSTRAINT_TYPE"] == "P" ? "PRIMARY" : ($row["CONSTRAINT_TYPE"] == "U" ? "UNIQUE" : "INDEX"));
            $return[$index_name]["columns"][] = $column_name;
            $return[$index_name]["lengths"][] = ($row["CHAR_LENGTH"] && $row["CHAR_LENGTH"] != $row["COLUMN_LENGTH"] ? $row["CHAR_LENGTH"] : null);
            $return[$index_name]["descs"][] = ($row["DESCEND"] && $row["DESCEND"] == "DESC" ? '1' : null);
        }
        return $return;
    }

    public function view($name) {
        $view = views_table("view_name, text");
        $rows = $this->adminer->get_rows('SELECT text "select" FROM ' . $view . ' WHERE view_name = ' . $this->q($name));
        return reset($rows);
    }

    public function collations() {
        return []; //!
    }

    public function create_database($db, $collation) {
        return false;
    }

    public function drop_databases($databases) {
        return false;
    }

    public function explain($connection, $query) {
        $connection->query("EXPLAIN PLAN FOR $query");
        return $connection->query("SELECT * FROM plan_table");
    }

    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        $alter = $drop = [];
        $orig_fields = ($table ? $this->fields($table) : []);
        foreach ($fields as $field) {
            $val = $field[1];
            if ($val && $field[0] != "" && $this->idf_escape($field[0]) != $val[0]) {
                $this->adminer->queries("ALTER TABLE " . $this->table($table) . " RENAME COLUMN " . $this->idf_escape($field[0]) . " TO $val[0]");
            }
            $orig_field = $orig_fields[$field[0]];
            if ($val && $orig_field) {
                $old = $this->adminer->process_field($orig_field, $orig_field);
                if ($val[2] == $old[2]) {
                    $val[2] = "";
                }
            }
            if ($val) {
                $alter[] = ($table != "" ? ($field[0] != "" ? "MODIFY (" : "ADD (") : "  ") . implode($val) . ($table != "" ? ")" : ""); //! error with name change only
            } else {
                $drop[] = $this->idf_escape($field[0]);
            }
        }
        if ($table == "") {
            return $this->adminer->queries("CREATE TABLE " . $this->table($name) . " (\n" . implode(",\n", $alter) . "\n)");
        }
        return (!$alter || $this->adminer->queries("ALTER TABLE " . $this->table($table) . "\n" . implode("\n", $alter)))
            && (!$drop || $this->adminer->queries("ALTER TABLE " . $this->table($table) . " DROP (" . implode(", ", $drop) . ")"))
            && ($table == $name || $this->adminer->queries("ALTER TABLE " . $this->table($table) . " RENAME TO " . $this->table($name)))
        ;
    }

    public function alter_indexes($table, $alter) {
        $drop = [];
        $queries = [];
        foreach ($alter as $val) {
            if ($val[0] != "INDEX") {
                //! descending UNIQUE indexes results in syntax error
                $val[2] = preg_replace('~ DESC$~', '', $val[2]);
                $create = ($val[2] == "DROP"
                    ? "\nDROP CONSTRAINT " . $this->idf_escape($val[1])
                    : "\nADD" . ($val[1] != "" ? " CONSTRAINT " . $this->idf_escape($val[1]) : "") . " $val[0] " . ($val[0] == "PRIMARY" ? "KEY " : "") . "(" . implode(", ", $val[2]) . ")"
                );
                array_unshift($queries, "ALTER TABLE " . $this->table($table) . $create);
            } elseif ($val[2] == "DROP") {
                $drop[] = $this->idf_escape($val[1]);
            } else {
                $queries[] = "CREATE INDEX " . $this->idf_escape($val[1] != "" ? $val[1] : uniqid($table . "_")) . " ON " . $this->table($table) . " (" . implode(", ", $val[2]) . ")";
            }
        }
        if ($drop) {
            array_unshift($queries, "DROP INDEX " . implode(", ", $drop));
        }
        foreach ($queries as $query) {
            if (!$this->adminer->queries($query)) {
                return false;
            }
        }
        return true;
    }

    public function foreign_keys($table) {
        $return = [];
        $query = "SELECT c_list.CONSTRAINT_NAME as NAME,
c_src.COLUMN_NAME as SRC_COLUMN,
c_dest.OWNER as DEST_DB,
c_dest.TABLE_NAME as DEST_TABLE,
c_dest.COLUMN_NAME as DEST_COLUMN,
c_list.DELETE_RULE as ON_DELETE
FROM ALL_CONSTRAINTS c_list, ALL_CONS_COLUMNS c_src, ALL_CONS_COLUMNS c_dest
WHERE c_list.CONSTRAINT_NAME = c_src.CONSTRAINT_NAME
AND c_list.R_CONSTRAINT_NAME = c_dest.CONSTRAINT_NAME
AND c_list.CONSTRAINT_TYPE = 'R'
AND c_src.TABLE_NAME = " . $this->q($table);
        foreach ($this->adminer->get_rows($query) as $row) {
            $return[$row['NAME']] = array(
                "db" => $row['DEST_DB'],
                "table" => $row['DEST_TABLE'],
                "source" => array($row['SRC_COLUMN']),
                "target" => array($row['DEST_COLUMN']),
                "on_delete" => $row['ON_DELETE'],
                "on_update" => null,
            );
        }
        return $return;
    }

    public function truncate_tables($tables) {
        return $this->adminer->apply_queries("TRUNCATE TABLE", $tables);
    }

    public function drop_views($views) {
        return $this->adminer->apply_queries("DROP VIEW", $views);
    }

    public function drop_tables($tables) {
        return $this->adminer->apply_queries("DROP TABLE", $tables);
    }

    public function last_id() {
        return 0; //!
    }

    public function schemas() {
        $return = $this->adminer->get_vals("SELECT DISTINCT owner FROM dba_segments WHERE owner IN (SELECT username FROM dba_users WHERE default_tablespace NOT IN ('SYSTEM','SYSAUX')) ORDER BY 1");
        return ($return ? $return : $this->adminer->get_vals("SELECT DISTINCT owner FROM all_tables WHERE tablespace_name = " . $this->q($this->getCurrentDatabase()) . " ORDER BY 1"));
    }

    public function get_schema() {
        return $this->connection->result("SELECT sys_context('USERENV', 'SESSION_USER') FROM dual");
    }

    public function set_schema($scheme, $connection2 = null) {
        if (!$connection2) {
            $connection2 = $this->connection;
        }
        return $connection2->query("ALTER SESSION SET CURRENT_SCHEMA = " . $this->idf_escape($scheme));
    }

    public function show_variables() {
        return $this->adminer->get_key_vals('SELECT name, display_value FROM v$parameter');
    }

    public function process_list() {
        return $this->adminer->get_rows('SELECT sess.process AS "process", sess.username AS "user", sess.schemaname AS "schema", sess.status AS "status", sess.wait_class AS "wait_class", sess.seconds_in_wait AS "seconds_in_wait", sql.sql_text AS "sql_text", sess.machine AS "machine", sess.port AS "port"
FROM v$session sess LEFT OUTER JOIN v$sql sql
ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');
    }

    public function show_status() {
        $rows = $this->adminer->get_rows('SELECT * FROM v$instance');
        return reset($rows);
    }

    public function support($feature) {
        return preg_match('~^(columns|database|drop_col|indexes|descidx|processlist|scheme|sql|status|table|variables|view)$~', $feature); //!
    }

    public function driver_config() {
        $types = [];
        $structured_types = [];
        foreach (array(
            $this->adminer->lang('Numbers') => array("number" => 38, "binary_float" => 12, "binary_double" => 21),
            $this->adminer->lang('Date and time') => array("date" => 10, "timestamp" => 29, "interval year" => 12, "interval day" => 28), //! year(), day() to second()
            $this->adminer->lang('Strings') => array("char" => 2000, "varchar2" => 4000, "nchar" => 2000, "nvarchar2" => 4000, "clob" => 4294967295, "nclob" => 4294967295),
            $this->adminer->lang('Binary') => array("raw" => 2000, "long raw" => 2147483648, "blob" => 4294967295, "bfile" => 4294967296),
        ) as $key => $val) {
            $types += $val;
            $structured_types[$key] = array_keys($val);
        }
        return array(
            'possible_drivers' => array("OCI8", "PDO_OCI"),
            'jush' => "oracle",
            'types' => $types,
            'structured_types' => $structured_types,
            'unsigned' => [],
            'operators' => array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "IN", "IS NULL", "NOT LIKE", "NOT REGEXP", "NOT IN", "IS NOT NULL", "SQL"),
            'functions' => array("length", "lower", "round", "upper"),
            'grouping' => array("avg", "count", "count distinct", "max", "min", "sum"),
            'edit_functions' => array(
                array( //! no parentheses
                    "date" => "current_date",
                    "timestamp" => "current_timestamp",
                ), array(
                    "number|float|double" => "+/-",
                    "date|timestamp" => "+ interval/- interval",
                    "char|clob" => "||",
                )
            ),
        );
    }
}
