<?php

namespace Lagdo\Adminer\Drivers\Mysql;

use Lagdo\Adminer\Drivers\AbstractServer;

use function Lagdo\Adminer\Drivers\h;
use function Lagdo\Adminer\Drivers\lang;
use function Lagdo\Adminer\Drivers\is_utf8;
use function Lagdo\Adminer\Drivers\idf_unescape;
use function Lagdo\Adminer\Drivers\number_type;

class Mysql extends AbstractServer
{
    /**
     * @inheritDoc
     */
    public function getDriver()
    {
        return "server";
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "MySQL";
    }

    /**
     * Get a connection to the server, based on the config and available packages
     */
    protected function createConnection()
    {
        if(extension_loaded("mysqli"))
        {
            return new Mysqli\Connection();
        }
        if(extension_loaded("mysql") && !((ini_bool("sql.safe_mode") || ini_bool("mysql.allow_local_infile")) && extension_loaded("pdo_mysql")))
        {
            return new Mysql\Connection();
        }
        if(extension_loaded("pdo_mysql"))
        {
            return new Pdo\Connection();
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        global $types, $structured_types;
        $connection = $this->createConnection();
        list($server, $username, $password) = $this->adminer->credentials();
        if ($this->connection->connect($server, \compact('username', 'password'))) {
            $this->connection->set_charset(charset($connection)); // available in MySQLi since PHP 5.0.5
            $this->connection->query("SET sql_quote_show_create = 1, autocommit = 1");
            if ($this->min_version('5.7.8', 10.2, $connection)) {
                $structured_types[lang('Strings')][] = "json";
                $types["json"] = 4294967295;
            }
            return $connection;
        }
        $return = $this->connection->error;
        if (function_exists('iconv') && !is_utf8($return) && strlen($s = iconv("windows-1250", "utf-8", $return)) > strlen($return)) { // windows-1250 - most common Windows encoding
            $return = $s;
        }
        return $return;
    }

    /**
     * @inheritDoc
     */
    public function idf_escape($idf)
    {
        return "`" . str_replace("`", "``", $idf) . "`";
    }

    /**
     * Get cached list of databases
     * @param bool
     * @return array
     */
    public function get_databases($flush) {
        // !!! Caching and slow query handling are temporarily disabled !!!
        $query = $this->min_version(5) ?
            "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME" :
            "SHOW DATABASES";
        return $this->get_vals($query);

        // SHOW DATABASES can take a very long time so it is cached
        // $return = get_session("dbs");
        // if ($return === null) {
        //     $query = ($this->min_version(5)
        //         ? "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME"
        //         : "SHOW DATABASES"
        //     ); // SHOW DATABASES can be disabled by skip_show_database
        //     $return = ($flush ? slow_query($query) : $this->get_vals($query));
        //     restart_session();
        //     set_session("dbs", $return);
        //     stop_session();
        // }
        // return $return;
    }

    /**
     * Formulate SQL query with limit
     * @param string everything after SELECT
     * @param string including WHERE
     * @param int
     * @param int
     * @param string
     * @return string
     */
    public function limit($query, $where, $limit, $offset = 0, $separator = " ") {
        return " $query$where" . ($limit !== null ? $separator . "LIMIT $limit" . ($offset ? " OFFSET $offset" : "") : "");
    }

    /**
     * Get database collation
     * @param string
     * @param array result of collations()
     * @return string
     */
    public function db_collation($db, $collations) {
        $return = null;
        $create = $this->connection->result("SHOW CREATE DATABASE " . $this->idf_escape($db), 1);
        if (preg_match('~ COLLATE ([^ ]+)~', $create, $match)) {
            $return = $match[1];
        } elseif (preg_match('~ CHARACTER SET ([^ ]+)~', $create, $match)) {
            // default collation
            $return = $collations[$match[1]][-1];
        }
        return $return;
    }

    /**
     * Get supported engines
     * @return array
     */
    public function engines() {
        $return = [];
        foreach ($this->get_rows("SHOW ENGINES") as $row) {
            if (preg_match("~YES|DEFAULT~", $row["Support"])) {
                $return[] = $row["Engine"];
            }
        }
        return $return;
    }

    /**
     * Get logged user
     * @return string
     */
    public function logged_user() {
        return $this->connection->result("SELECT USER()");
    }

    /**
     * Get tables list
     * @return array array($name => $type)
     */
    public function tables_list() {
        return $this->get_key_vals($this->min_version(5)
            ? "SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"
            : "SHOW TABLES"
        );
    }

    /**
     * Count tables in all databases
     * @param array
     * @return array array($db => $tables)
     */
    public function count_tables($databases) {
        $return = [];
        foreach ($databases as $db) {
            $return[$db] = count($this->get_vals("SHOW TABLES IN " . $this->idf_escape($db)));
        }
        return $return;
    }

    /**
     * Get table status
     * @param string
     * @param bool return only "Name", "Engine" and "Comment" fields
     * @return array array($name => array("Name" => , "Engine" => , "Comment" => , "Oid" => , "Rows" => , "Collation" => , "Auto_increment" => , "Data_length" => , "Index_length" => , "Data_free" => )) or only inner array with $name
     */
    public function table_status($name = "", $fast = false) {
        $return = [];
        foreach ($this->get_rows($fast && $this->min_version(5)
            ? "SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() " . ($name != "" ? "AND TABLE_NAME = " . $this->q($name) : "ORDER BY Name")
            : "SHOW TABLE STATUS" . ($name != "" ? " LIKE " . $this->q(addcslashes($name, "%_\\")) : "")
        ) as $row) {
            if ($row["Engine"] == "InnoDB") {
                // ignore internal comment, unnecessary since MySQL 5.1.21
                $row["Comment"] = preg_replace('~(?:(.+); )?InnoDB free: .*~', '\1', $row["Comment"]);
            }
            if (!isset($row["Engine"])) {
                $row["Comment"] = "";
            }
            if ($name != "") {
                return $row;
            }
            $return[$row["Name"]] = $row;
        }
        return $return;
    }

    /**
     * Find out whether the identifier is view
     * @param array
     * @return bool
     */
    public function is_view($table_status) {
        return $table_status["Engine"] === null;
    }

    /**
     * Check if table supports foreign keys
     * @param array result of table_status
     * @return bool
     */
    public function fk_support($table_status) {
        return preg_match('~InnoDB|IBMDB2I~i', $table_status["Engine"])
            || (preg_match('~NDB~i', $table_status["Engine"]) && $this->min_version(5.6));
    }

    /**
     * Get information about fields
     * @param string
     * @return array array($name => array("field" => , "full_type" => , "type" => , "length" => , "unsigned" => , "default" => , "null" => , "auto_increment" => , "on_update" => , "collation" => , "privileges" => , "comment" => , "primary" => ))
     */
    public function fields($table) {
        $return = [];
        foreach ($this->get_rows("SHOW FULL COLUMNS FROM " . $this->table($table)) as $row) {
            preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~', $row["Type"], $match);
            $return[$row["Field"]] = array(
                "field" => $row["Field"],
                "full_type" => $row["Type"],
                "type" => $match[1],
                "length" => $match[2],
                "unsigned" => ltrim($match[3] . $match[4]),
                "default" => ($row["Default"] != "" || preg_match("~char|set~", $match[1]) ? (preg_match('~text~', $match[1]) ? stripslashes(preg_replace("~^'(.*)'\$~", '\1', $row["Default"])) : $row["Default"]) : null),
                "null" => ($row["Null"] == "YES"),
                "auto_increment" => ($row["Extra"] == "auto_increment"),
                "on_update" => (preg_match('~^on update (.+)~i', $row["Extra"], $match) ? $match[1] : ""), //! available since MySQL 5.1.23
                "collation" => $row["Collation"],
                "privileges" => array_flip(preg_split('~, *~', $row["Privileges"])),
                "comment" => $row["Comment"],
                "primary" => ($row["Key"] == "PRI"),
                // https://mariadb.com/kb/en/library/show-columns/, https://github.com/vrana/adminer/pull/359#pullrequestreview-276677186
                "generated" => preg_match('~^(VIRTUAL|PERSISTENT|STORED)~', $row["Extra"]),
            );
        }
        return $return;
    }

    /**
     * Get table indexes
     * @param string
     * @param string ConnectionInterface to use
     * @return array array($key_name => array("type" => , "columns" => [], "lengths" => [], "descs" => []))
     */
    public function indexes($table, $connection2 = null) {
        $return = [];
        foreach ($this->get_rows("SHOW INDEX FROM " . $this->table($table), $connection2) as $row) {
            $name = $row["Key_name"];
            $return[$name]["type"] = ($name == "PRIMARY" ? "PRIMARY" : ($row["Index_type"] == "FULLTEXT" ? "FULLTEXT" : ($row["Non_unique"] ? ($row["Index_type"] == "SPATIAL" ? "SPATIAL" : "INDEX") : "UNIQUE")));
            $return[$name]["columns"][] = $row["Column_name"];
            $return[$name]["lengths"][] = ($row["Index_type"] == "SPATIAL" ? null : $row["Sub_part"]);
            $return[$name]["descs"][] = null;
        }
        return $return;
    }

    /**
     * Get foreign keys in table
     * @param string
     * @return array array($name => array("db" => , "ns" => , "table" => , "source" => [], "target" => [], "on_delete" => , "on_update" => ))
     */
    public function foreign_keys($table) {
        global $on_actions;
        static $pattern = '(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';
        $return = [];
        $create_table = $this->connection->result("SHOW CREATE TABLE " . $this->table($table), 1);
        if ($create_table) {
            preg_match_all("~CONSTRAINT ($pattern) FOREIGN KEY ?\\(((?:$pattern,? ?)+)\\) REFERENCES ($pattern)(?:\\.($pattern))? \\(((?:$pattern,? ?)+)\\)(?: ON DELETE ($on_actions))?(?: ON UPDATE ($on_actions))?~", $create_table, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                preg_match_all("~$pattern~", $match[2], $source);
                preg_match_all("~$pattern~", $match[5], $target);
                $return[idf_unescape($match[1])] = array(
                    "db" => idf_unescape($match[4] != "" ? $match[3] : $match[4]),
                    "table" => idf_unescape($match[4] != "" ? $match[4] : $match[3]),
                    "source" => array_map('idf_unescape', $source[0]),
                    "target" => array_map('idf_unescape', $target[0]),
                    "on_delete" => ($match[6] ? $match[6] : "RESTRICT"),
                    "on_update" => ($match[7] ? $match[7] : "RESTRICT"),
                );
            }
        }
        return $return;
    }

    /**
     * Get view SELECT
     * @param string
     * @return array array("select" => )
     */
    public function view($name) {
        return array("select" => preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU', '',
            $this->connection->result("SHOW CREATE VIEW " . $this->table($name), 1)));
    }

    /**
     * Get sorted grouped list of collations
     * @return array
     */
    public function collations() {
        $return = [];
        foreach ($this->get_rows("SHOW COLLATION") as $row) {
            if ($row["Default"]) {
                $return[$row["Charset"]][-1] = $row["Collation"];
            } else {
                $return[$row["Charset"]][] = $row["Collation"];
            }
        }
        ksort($return);
        foreach ($return as $key => $val) {
            asort($return[$key]);
        }
        return $return;
    }

    /**
     * Find out if database is information_schema
     * @param string
     * @return bool
     */
    public function information_schema($db) {
        return ($this->min_version(5) && $db == "information_schema")
            || ($this->min_version(5.5) && $db == "performance_schema");
    }

    /**
     * Get escaped error message
     * @return string
     */
    public function error() {
        return h(preg_replace('~^You have an error.*syntax to use~U', "Syntax error", $this->connection->error));
    }

    /**
     * Create database
     * @param string
     * @param string
     * @return string
     */
    public function create_database($db, $collation) {
        return $this->queries("CREATE DATABASE " . $this->idf_escape($db) . ($collation ? " COLLATE " . $this->q($collation) : ""));
    }

    /**
     * Drop databases
     * @param array
     * @return bool
     */
    public function drop_databases($databases) {
        $return = $this->apply_queries("DROP DATABASE", $databases, 'idf_escape');
        // restart_session();
        // set_session("dbs", null);
        return $return;
    }

    /**
     * Rename database from DB
     * @param string new name
     * @param string
     * @return bool
     */
    public function rename_database($name, $collation) {
        $return = false;
        if ($this->create_database($name, $collation)) {
            $tables = [];
            $views = [];
            foreach ($this->tables_list() as $table => $type) {
                if ($type == 'VIEW') {
                    $views[] = $table;
                } else {
                    $tables[] = $table;
                }
            }
            $return = (!$tables && !$views) || $this->move_tables($tables, $views, $name);
            $this->drop_databases($return ? array($this->adminer->database()) : []);
        }
        return $return;
    }

    /**
     * Generate modifier for auto increment column
     * @return string
     */
    public function auto_increment() {
        $auto_increment_index = " PRIMARY KEY";
        // don't overwrite primary key by auto_increment
        if ($_GET["create"] != "" && $_POST["auto_increment_col"]) {
            foreach ($this->indexes($_GET["create"]) as $index) {
                if (in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"], $index["columns"], true)) {
                    $auto_increment_index = "";
                    break;
                }
                if ($index["type"] == "PRIMARY") {
                    $auto_increment_index = " UNIQUE";
                }
            }
        }
        return " AUTO_INCREMENT$auto_increment_index";
    }

    /**
     * Run commands to create or alter table
     * @param string "" to create
     * @param string new name
     * @param array of array($orig, $process_field, $after)
     * @param array of strings
     * @param string
     * @param string
     * @param string
     * @param string number
     * @param string
     * @return bool
     */
    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
        $alter = [];
        foreach ($fields as $field) {
            $alter[] = ($field[1]
                ? ($table != "" ? ($field[0] != "" ? "CHANGE " . $this->idf_escape($field[0]) : "ADD") : " ") . " " . implode($field[1]) . ($table != "" ? $field[2] : "")
                : "DROP " . $this->idf_escape($field[0])
            );
        }
        $alter = array_merge($alter, $foreign);
        $status = ($comment !== null ? " COMMENT=" . $this->q($comment) : "")
            . ($engine ? " ENGINE=" . $this->q($engine) : "")
            . ($collation ? " COLLATE " . $this->q($collation) : "")
            . ($auto_increment != "" ? " AUTO_INCREMENT=$auto_increment" : "")
        ;
        if ($table == "") {
            return $this->queries("CREATE TABLE " . $this->table($name) . " (\n" . implode(",\n", $alter) . "\n)$status$partitioning");
        }
        if ($table != $name) {
            $alter[] = "RENAME TO " . $this->table($name);
        }
        if ($status) {
            $alter[] = ltrim($status);
        }
        return ($alter || $partitioning ? $this->queries("ALTER TABLE " . $this->table($table) . "\n" . implode(",\n", $alter) . $partitioning) : true);
    }

    /**
     * Run commands to alter indexes
     * @param string escaped table name
     * @param array of array("index type", "name", array("column definition", ...)) or array("index type", "name", "DROP")
     * @return bool
     */
    public function alter_indexes($table, $alter) {
        foreach ($alter as $key => $val) {
            $alter[$key] = ($val[2] == "DROP"
                ? "\nDROP INDEX " . $this->idf_escape($val[1])
                : "\nADD $val[0] " . ($val[0] == "PRIMARY" ? "KEY " : "") . ($val[1] != "" ? $this->idf_escape($val[1]) . " " : "") . "(" . implode(", ", $val[2]) . ")"
            );
        }
        return $this->queries("ALTER TABLE " . $this->table($table) . implode(",", $alter));
    }

    /**
     * Run commands to truncate tables
     * @param array
     * @return bool
     */
    public function truncate_tables($tables) {
        return $this->apply_queries("TRUNCATE TABLE", $tables);
    }

    /**
     * Drop views
     * @param array
     * @return bool
     */
    public function drop_views($views) {
        return $this->queries("DROP VIEW " . implode(", ", array_map('table', $views)));
    }

    /**
     * Drop tables
     * @param array
     * @return bool
     */
    public function drop_tables($tables) {
        return $this->queries("DROP TABLE " . implode(", ", array_map('table', $tables)));
    }

    /**
     * Move tables to other schema
     * @param array
     * @param array
     * @param string
     * @return bool
     */
    public function move_tables($tables, $views, $target) {
        $rename = [];
        foreach ($tables as $table) {
            $rename[] = $this->table($table) . " TO " . $this->idf_escape($target) . "." . $this->table($table);
        }
        if (!$rename || $this->queries("RENAME TABLE " . implode(", ", $rename))) {
            $definitions = [];
            foreach ($views as $table) {
                $definitions[table($table)] = $this->view($table);
            }
            $this->connection->select_db($target);
            $db = $this->idf_escape($this->adminer->database());
            foreach ($definitions as $name => $view) {
                if (!$this->server->queries("CREATE VIEW $name AS " . str_replace(" $db.", " ", $view["select"])) || !$this->queries("DROP VIEW $db.$name")) {
                    return false;
                }
            }
            return true;
        }
        //! move triggers
        return false;
    }

    /**
     * Copy tables to other schema
     * @param array
     * @param array
     * @param string
     * @return bool
     */
    public function copy_tables($tables, $views, $target) {
        $this->queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
        foreach ($tables as $table) {
            $name = ($target == $this->adminer->database() ? $this->table("copy_$table") : $this->idf_escape($target) . "." . $this->table($table));
            if (($_POST["overwrite"] && !$this->queries("\nDROP TABLE IF EXISTS $name"))
                || !$this->queries("CREATE TABLE $name LIKE " . $this->table($table))
                || !$this->queries("INSERT INTO $name SELECT * FROM " . $this->table($table))
            ) {
                return false;
            }
            foreach ($this->get_rows("SHOW TRIGGERS LIKE " . $this->q(addcslashes($table, "%_\\"))) as $row) {
                $trigger = $row["Trigger"];
                if (!$this->queries("CREATE TRIGGER " . ($target == $this->adminer->database() ? $this->idf_escape("copy_$trigger") : $this->idf_escape($target) . "." . $this->idf_escape($trigger)) . " $row[Timing] $row[Event] ON $name FOR EACH ROW\n$row[Statement];")) {
                    return false;
                }
            }
        }
        foreach ($views as $table) {
            $name = ($target == $this->adminer->database() ? $this->table("copy_$table") : $this->idf_escape($target) . "." . $this->table($table));
            $view = $this->view($table);
            if (($_POST["overwrite"] && !$this->queries("DROP VIEW IF EXISTS $name"))
                || !$this->queries("CREATE VIEW $name AS $view[select]")) { //! USE to avoid db.table
                return false;
            }
        }
        return true;
    }

    /**
     * Get information about trigger
     * @param string trigger name
     * @return array array("Trigger" => , "Timing" => , "Event" => , "Of" => , "Type" => , "Statement" => )
     */
    public function trigger($name) {
        if ($name == "") {
            return [];
        }
        $rows = $this->get_rows("SHOW TRIGGERS WHERE `Trigger` = " . $this->q($name));
        return reset($rows);
    }

    /**
     * Get defined triggers
     * @param string
     * @return array array($name => array($timing, $event))
     */
    public function triggers($table) {
        $return = [];
        foreach ($this->get_rows("SHOW TRIGGERS LIKE " . $this->q(addcslashes($table, "%_\\"))) as $row) {
            $return[$row["Trigger"]] = array($row["Timing"], $row["Event"]);
        }
        return $return;
    }

    /**
     * Get trigger options
     * @return array ("Timing" => [], "Event" => [], "Type" => [])
     */
    public function trigger_options() {
        return array(
            "Timing" => array("BEFORE", "AFTER"),
            "Event" => array("INSERT", "UPDATE", "DELETE"),
            "Type" => array("FOR EACH ROW"),
        );
    }

    /**
     * Get information about stored routine
     * @param string
     * @param string "FUNCTION" or "PROCEDURE"
     * @return array ("fields" => array("field" => , "type" => , "length" => , "unsigned" => , "inout" => , "collation" => ), "returns" => , "definition" => , "language" => )
     */
    public function routine($name, $type) {
        global $enum_length, $inout, $types;
        $aliases = array("bool", "boolean", "integer", "double precision", "real", "dec", "numeric", "fixed", "national char", "national varchar");
        $space = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
        $type_pattern = "((" . implode("|", array_merge(array_keys($types), $aliases)) . ")\\b(?:\\s*\\(((?:[^'\")]|$enum_length)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";
        $pattern = "$space*(" . ($type == "FUNCTION" ? "" : $inout) . ")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$type_pattern";
        $create = $this->connection->result("SHOW CREATE $type " . $this->idf_escape($name), 2);
        preg_match("~\\(((?:$pattern\\s*,?)*)\\)\\s*" . ($type == "FUNCTION" ? "RETURNS\\s+$type_pattern\\s+" : "") . "(.*)~is", $create, $match);
        $fields = [];
        preg_match_all("~$pattern\\s*,?~is", $match[1], $matches, PREG_SET_ORDER);
        foreach ($matches as $param) {
            $fields[] = array(
                "field" => str_replace("``", "`", $param[2]) . $param[3],
                "type" => strtolower($param[5]),
                "length" => preg_replace_callback("~$enum_length~s", 'normalize_enum', $param[6]),
                "unsigned" => strtolower(preg_replace('~\s+~', ' ', trim("$param[8] $param[7]"))),
                "null" => 1,
                "full_type" => $param[4],
                "inout" => strtoupper($param[1]),
                "collation" => strtolower($param[9]),
            );
        }
        if ($type != "FUNCTION") {
            return array("fields" => $fields, "definition" => $match[11]);
        }
        return array(
            "fields" => $fields,
            "returns" => array("type" => $match[12], "length" => $match[13], "unsigned" => $match[15], "collation" => $match[16]),
            "definition" => $match[17],
            "language" => "SQL", // available in information_schema.ROUTINES.PARAMETER_STYLE
        );
    }

    /**
     * Get list of routines
     * @return array ("SPECIFIC_NAME" => , "ROUTINE_NAME" => , "ROUTINE_TYPE" => , "DTD_IDENTIFIER" => )
     */
    public function routines() {
        return $this->get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = " . $this->q($this->adminer->database()));
    }

    /**
     * Get list of available routine languages
     * @return array
     */
    public function routine_languages() {
        return []; // "SQL" not required
    }

    /**
     * Get routine signature
     * @param string
     * @param array result of routine()
     * @return string
     */
    public function routine_id($name, $row) {
        return $this->idf_escape($name);
    }

    /**
     * Get last auto increment ID
     * @return string
     */
    public function last_id() {
        return $this->connection->result("SELECT LAST_INSERT_ID()"); // mysql_insert_id() truncates bigint
    }

    /**
     * Explain select
     * @param ConnectionInterface
     * @param string
     * @return Statement
     */
    public function explain($connection, $query) {
        return $this->connection->query("EXPLAIN " . ($this->min_version(5.1) && !$this->min_version(5.7) ? "PARTITIONS " : "") . $query);
    }

    /**
     * Get approximate number of rows
     * @param array
     * @param array
     * @return int or null if approximate number can't be retrieved
     */
    public function found_rows($table_status, $where) {
        return ($where || $table_status["Engine"] != "InnoDB" ? null : $table_status["Rows"]);
    }

    /**
     * Get SQL command to create table
     * @param string
     * @param bool
     * @param string
     * @return string
     */
    public function create_sql($table, $auto_increment, $style) {
        $return = $this->connection->result("SHOW CREATE TABLE " . $this->table($table), 1);
        if (!$auto_increment) {
            $return = preg_replace('~ AUTO_INCREMENT=\d+~', '', $return); //! skip comments
        }
        return $return;
    }

    /**
     * Get SQL command to truncate table
     * @param string
     * @return string
     */
    public function truncate_sql($table) {
        return "TRUNCATE " . $this->table($table);
    }

    /**
     * Get SQL command to change database
     * @param string
     * @return string
     */
    public function use_sql($database) {
        return "USE " . $this->idf_escape($database);
    }

    /**
     * Get SQL commands to create triggers
     * @param string
     * @return string
     */
    public function trigger_sql($table) {
        $return = "";
        foreach ($this->get_rows("SHOW TRIGGERS LIKE " . $this->q(addcslashes($table, "%_\\")), null, "-- ") as $row) {
            $return .= "\nCREATE TRIGGER " . $this->idf_escape($row["Trigger"]) . " $row[Timing] $row[Event] ON " . $this->table($row["Table"]) . " FOR EACH ROW\n$row[Statement];;\n";
        }
        return $return;
    }

    /**
     * Get server variables
     * @return array ($name => $value)
     */
    public function show_variables() {
        return $this->get_key_vals("SHOW VARIABLES");
    }

    /**
     * Get process list
     * @return array ($row)
     */
    public function process_list() {
        return $this->get_rows("SHOW FULL PROCESSLIST");
    }

    /**
     * Get status variables
     * @return array ($name => $value)
     */
    public function show_status() {
        return $this->get_key_vals("SHOW STATUS");
    }

    /**
     * Convert field in select and edit
     * @param array one element from $this->fields()
     * @return string
     */
    public function convert_field($field) {
        if (preg_match("~binary~", $field["type"])) {
            return "HEX(" . $this->idf_escape($field["field"]) . ")";
        }
        if ($field["type"] == "bit") {
            return "BIN(" . $this->idf_escape($field["field"]) . " + 0)"; // + 0 is required outside MySQLnd
        }
        if (preg_match("~geometry|point|linestring|polygon~", $field["type"])) {
            return ($this->min_version(8) ? "ST_" : "") . "AsWKT(" . $this->idf_escape($field["field"]) . ")";
        }
    }

    /**
     * Convert value in edit after applying functions back
     * @param array one element from $this->fields()
     * @param string
     * @return string
     */
    public function unconvert_field($field, $return) {
        if (preg_match("~binary~", $field["type"])) {
            $return = "UNHEX($return)";
        }
        if ($field["type"] == "bit") {
            $return = "CONV($return, 2, 10) + 0";
        }
        if (preg_match("~geometry|point|linestring|polygon~", $field["type"])) {
            $return = ($this->min_version(8) ? "ST_" : "") . "GeomFromText($return, SRID($field[field]))";
        }
        return $return;
    }

    /**
     * Check whether a feature is supported
     * @param string "comment", "copy", "database", "descidx", "drop_col", "dump", "event", "indexes", "kill", "materializedview", "partitioning", "privileges", "procedure", "processlist", "routine", "scheme", "sequence", "status", "table", "trigger", "type", "variables", "view", "view_trigger"
     * @return bool
     */
    public function support($feature) {
        return !preg_match("~scheme|sequence|type|view_trigger|materializedview" . ($this->min_version(8) ? "" : "|descidx" . ($this->min_version(5.1) ? "" : "|event|partitioning" . ($this->min_version(5) ? "" : "|routine|trigger|view"))) . "~", $feature);
    }

    /**
     * Kill a process
     * @param int
     * @return bool
     */
    public function kill_process($val) {
        return $this->queries("KILL " . number($val));
    }

    /**
     * Return query to get connection ID
     * @return string
     */
    public function connection_id(){
        return "SELECT CONNECTION_ID()";
    }

    /**
     * Get maximum number of connections
     * @return int
     */
    public function max_connections() {
        return $this->connection->result("SELECT @@max_connections");
    }

    /**
     * Get driver config
     * @return array array('possible_drivers' => , 'jush' => , 'types' => , 'structured_types' => , 'unsigned' => , 'operators' => , 'functions' => , 'grouping' => , 'edit_functions' => )
     */
    public function driver_config() {
        $types = []; ///< @var array ($type => $maximum_unsigned_length, ...)
        $structured_types = []; ///< @var array ($description => array($type, ...), ...)
        foreach (array(
            lang('Numbers') => array("tinyint" => 3, "smallint" => 5, "mediumint" => 8, "int" => 10, "bigint" => 20, "decimal" => 66, "float" => 12, "double" => 21),
            lang('Date and time') => array("date" => 10, "datetime" => 19, "timestamp" => 19, "time" => 10, "year" => 4),
            lang('Strings') => array("char" => 255, "varchar" => 65535, "tinytext" => 255, "text" => 65535, "mediumtext" => 16777215, "longtext" => 4294967295),
            lang('Lists') => array("enum" => 65535, "set" => 64),
            lang('Binary') => array("bit" => 20, "binary" => 255, "varbinary" => 65535, "tinyblob" => 255, "blob" => 65535, "mediumblob" => 16777215, "longblob" => 4294967295),
            lang('Geometry') => array("geometry" => 0, "point" => 0, "linestring" => 0, "polygon" => 0, "multipoint" => 0, "multilinestring" => 0, "multipolygon" => 0, "geometrycollection" => 0),
        ) as $key => $val) {
            $types += $val;
            $structured_types[$key] = array_keys($val);
        }
        return array(
            'possible_drivers' => array("MySQLi", "MySQL", "PDO_MySQL"),
            'jush' => "sql", ///< @var string JUSH identifier
            'types' => $types,
            'structured_types' => $structured_types,
            'unsigned' => array("unsigned", "zerofill", "unsigned zerofill"), ///< @var array number variants
            'operators' => array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "REGEXP", "IN", "FIND_IN_SET", "IS NULL", "NOT LIKE", "NOT REGEXP", "NOT IN", "IS NOT NULL", "SQL"), ///< @var array operators used in select
            'functions' => array("char_length", "date", "from_unixtime", "lower", "round", "floor", "ceil", "sec_to_time", "time_to_sec", "upper"), ///< @var array functions used in select
            'grouping' => array("avg", "count", "count distinct", "group_concat", "max", "min", "sum"), ///< @var array grouping functions used in select
            'edit_functions' => array( ///< @var array of array("$type|$type2" => "$function/$function2") functions used in editing, [0] - edit and insert, [1] - edit only
                array(
                    "char" => "md5/sha1/password/encrypt/uuid",
                    "binary" => "md5/sha1",
                    "date|time" => "now",
                ), array(
                    number_type() => "+/-",
                    "date" => "+ interval/- interval",
                    "time" => "addtime/subtime",
                    "char|text" => "concat",
                )
            ),
        );
    }
}
