<?php

namespace Lagdo\Adminer\Drivers\Db\Sqlite;

use Lagdo\Adminer\Drivers\Db\Server as AbstractServer;

use Exception;

class Server extends AbstractServer
{
    /**
     * @inheritDoc
     */
    public function getName()
    {
        return "SQLite 3";
    }

    /**
     * @inheritDoc
     */
    protected function createConnection()
    {
        if (($this->connection)) {
            // Do not create if it already exists
            return;
        }

        if (class_exists("SQLite3")) {
            $this->connection = new Sqlite\Connection($this->db, $this->util, $this, 'SQLite3');
        }
        elseif (extension_loaded("pdo_sqlite")) {
            $this->connection = new Pdo\Connection($this->db, $this->util, $this, 'PDO_SQLite');
        }

        if($this->connection !== null) {
            $this->driver = new Driver($this->db, $this->util, $this, $this->connection);
        }
    }

    /**
     * @inheritDoc
     */
    public function connect()
    {
        list($filename, $options) = $this->db->getOptions();
        if ($options['password'] != "") {
            return $this->util->lang('Database does not support password.');
        }
        if (!$this->connection) {
            return null;
        }

        $this->connection->open($filename, $options);
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function idf_escape($idf)
    {
        return '"' . str_replace('"', '""', $idf) . '"';
    }

    public function get_databases($flush)
    {
        return [];
    }

    public function limit($query, $where, $limit, $offset = 0, $separator = " ")
    {
        return " $query$where" . ($limit !== null ? $separator . "LIMIT $limit" . ($offset ? " OFFSET $offset" : "") : "");
    }

    public function limit1($table, $query, $where, $separator = "\n")
    {
        return preg_match('~^INTO~', $query) ||
            $this->connection->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')") ?
            $this->limit($query, $where, 1, 0, $separator) :
            //! use primary key in tables with WITHOUT rowid
            " $query WHERE rowid = (SELECT rowid FROM " . $this->table($table) . $where . $separator . "LIMIT 1)";
    }

    public function db_collation($db, $collations)
    {
        return $this->connection->result("PRAGMA encoding"); // there is no database list so $db == $this->current_db()
    }

    public function logged_user()
    {
        return get_current_user(); // should return effective user
    }

    public function tables_list()
    {
        return $this->db->get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");
    }

    public function count_tables($databases)
    {
        return [];
    }

    public function table_status($name = "", $fast = false)
    {
        $return = [];
        foreach ($this->db->get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') " . ($name != "" ? "AND name = " . $this->q($name) : "ORDER BY name")) as $row) {
            $row["Rows"] = $this->connection->result("SELECT COUNT(*) FROM " . $this->idf_escape($row["Name"]));
            $return[$row["Name"]] = $row;
        }
        foreach ($this->db->get_rows("SELECT * FROM sqlite_sequence", null, "") as $row) {
            $return[$row["name"]]["Auto_increment"] = $row["seq"];
        }
        return ($name != "" ? $return[$name] : $return);
    }

    public function is_view($table_status)
    {
        return $table_status["Engine"] == "view";
    }

    public function fk_support($table_status)
    {
        return !$this->connection->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");
    }

    public function fields($table)
    {
        $return = [];
        $primary = "";
        foreach ($this->db->get_rows("PRAGMA table_info(" . $this->table($table) . ")") as $row) {
            $name = $row["name"];
            $type = strtolower($row["type"]);
            $default = $row["dflt_value"];
            $return[$name] = array(
                "field" => $name,
                "type" => (preg_match('~int~i', $type) ? "integer" : (preg_match('~char|clob|text~i', $type) ? "text" : (preg_match('~blob~i', $type) ? "blob" : (preg_match('~real|floa|doub~i', $type) ? "real" : "numeric")))),
                "full_type" => $type,
                "default" => (preg_match("~'(.*)'~", $default, $match) ? str_replace("''", "'", $match[1]) : ($default == "NULL" ? null : $default)),
                "null" => !$row["notnull"],
                "privileges" => array("select" => 1, "insert" => 1, "update" => 1),
                "primary" => $row["pk"],
            );
            if ($row["pk"]) {
                if ($primary != "") {
                    $return[$primary]["auto_increment"] = false;
                } elseif (preg_match('~^integer$~i', $type)) {
                    $return[$name]["auto_increment"] = true;
                }
                $primary = $name;
            }
        }
        $sql = $this->connection->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = " . $this->q($table));
        preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i', $sql, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = str_replace('""', '"', preg_replace('~^"|"$~', '', $match[1]));
            if ($return[$name]) {
                $return[$name]["collation"] = trim($match[3], "'");
            }
        }
        return $return;
    }

    public function indexes($table, $connection2 = null)
    {
        if (!is_object($connection2)) {
            $connection2 = $this->connection;
        }
        $return = [];
        $sql = $connection2->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = " . $this->q($table));
        if (preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i', $sql, $match)) {
            $return[""] = array("type" => "PRIMARY", "columns" => [], "lengths" => [], "descs" => []);
            preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i', $match[1], $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $return[""]["columns"][] = $this->idf_unescape($match[2]) . $match[4];
                $return[""]["descs"][] = (preg_match('~DESC~i', $match[5]) ? '1' : null);
            }
        }
        if (!$return) {
            foreach ($this->fields($table) as $name => $field) {
                if ($field["primary"]) {
                    $return[""] = array("type" => "PRIMARY", "columns" => array($name), "lengths" => [], "descs" => array(null));
                }
            }
        }
        $sqls = $this->db->get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = " . $this->q($table), $connection2);
        foreach ($this->db->get_rows("PRAGMA index_list(" . $this->table($table) . ")", $connection2) as $row) {
            $name = $row["name"];
            $index = array("type" => ($row["unique"] ? "UNIQUE" : "INDEX"));
            $index["lengths"] = [];
            $index["descs"] = [];
            foreach ($this->db->get_rows("PRAGMA index_info(" . $this->idf_escape($name) . ")", $connection2) as $row1) {
                $index["columns"][] = $row1["name"];
                $index["descs"][] = null;
            }
            if (preg_match('~^CREATE( UNIQUE)? INDEX ' . preg_quote($this->idf_escape($name) . ' ON ' . $this->idf_escape($table), '~') . ' \((.*)\)$~i', $sqls[$name], $regs)) {
                preg_match_all('/("[^"]*+")+( DESC)?/', $regs[2], $matches);
                foreach ($matches[2] as $key => $val) {
                    if ($val) {
                        $index["descs"][$key] = '1';
                    }
                }
            }
            if (!$return[""] || $index["type"] != "UNIQUE" || $index["columns"] != $return[""]["columns"] || $index["descs"] != $return[""]["descs"] || !preg_match("~^sqlite_~", $name)) {
                $return[$name] = $index;
            }
        }
        return $return;
    }

    public function foreign_keys($table)
    {
        $return = [];
        foreach ($this->db->get_rows("PRAGMA foreign_key_list(" . $this->table($table) . ")") as $row) {
            $foreign_key = &$return[$row["id"]];
            //! idf_unescape in SQLite2
            if (!$foreign_key) {
                $foreign_key = $row;
            }
            $foreign_key["source"][] = $row["from"];
            $foreign_key["target"][] = $row["to"];
        }
        return $return;
    }

    public function view($name)
    {
        return array("select" => preg_replace(
            '~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU',
            '',
            $this->connection->result("SELECT sql FROM sqlite_master WHERE name = " .
            $this->q($name))
        )); //! identifiers may be inside []
    }

    public function collations()
    {
        $create = $this->util->input()->hasTable();
        return (($create) ? $this->db->get_vals("PRAGMA collation_list", 1) : []);
    }

    public function check_sqlite_name($name)
    {
        // avoid creating PHP files on unsecured servers
        $extensions = "db|sdb|sqlite";
        if (!preg_match("~^[^\\0]*\\.($extensions)\$~", $name)) {
            $this->connection->error = $this->util->lang('Please use one of the extensions %s.', str_replace("|", ", ", $extensions));
            return false;
        }
        return true;
    }

    public function create_database($db, $collation)
    {
        if (file_exists($db)) {
            $this->connection->error = $this->util->lang('File exists.');
            return false;
        }
        if (!check_sqlite_name($db)) {
            return false;
        }
        try {
            $link = new Min_SQLite($db);
        } catch (Exception $ex) {
            $this->connection->error = $ex->getMessage();
            return false;
        }
        $link->query('PRAGMA encoding = "UTF-8"');
        $link->query('CREATE TABLE adminer (i)'); // otherwise creates empty file
        $link->query('DROP TABLE adminer');
        return true;
    }

    public function drop_databases($databases)
    {
        $this->connection->__construct(":memory:"); // to unlock file, doesn't work in PDO on Windows
        foreach ($databases as $db) {
            if (!@unlink($db)) {
                $this->connection->error = $this->util->lang('File exists.');
                return false;
            }
        }
        return true;
    }

    public function rename_database($name, $collation)
    {
        if (!check_sqlite_name($name)) {
            return false;
        }
        $this->connection->__construct(":memory:");
        $this->connection->error = $this->util->lang('File exists.');
        return @rename($this->current_db(), $name);
    }

    public function auto_increment()
    {
        return " PRIMARY KEY AUTOINCREMENT";
    }

    public function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning)
    {
        $use_all_fields = ($table == "" || $foreign);
        foreach ($fields as $field) {
            if ($field[0] != "" || !$field[1] || $field[2]) {
                $use_all_fields = true;
                break;
            }
        }
        $alter = [];
        $originals = [];
        foreach ($fields as $field) {
            if ($field[1]) {
                $alter[] = ($use_all_fields ? $field[1] : "ADD " . implode($field[1]));
                if ($field[0] != "") {
                    $originals[$field[0]] = $field[1][0];
                }
            }
        }
        if (!$use_all_fields) {
            foreach ($alter as $val) {
                if (!$this->db->queries("ALTER TABLE " . $this->table($table) . " $val")) {
                    return false;
                }
            }
            if ($table != $name && !$this->db->queries("ALTER TABLE " . $this->table($table) . " RENAME TO " . $this->table($name))) {
                return false;
            }
        } elseif (!$this->recreate_table($table, $name, $alter, $originals, $foreign, $auto_increment)) {
            return false;
        }
        if ($auto_increment) {
            $this->db->queries("BEGIN");
            $this->db->queries("UPDATE sqlite_sequence SET seq = $auto_increment WHERE name = " . $this->q($name)); // ignores error
            if (!$this->connection->affected_rows) {
                $this->db->queries("INSERT INTO sqlite_sequence (name, seq) VALUES (" . $this->q($name) . ", $auto_increment)");
            }
            $this->db->queries("COMMIT");
        }
        return true;
    }

    protected function recreate_table($table, $name, $fields, $originals, $foreign, $auto_increment, $indexes = [])
    {
        if ($table != "") {
            if (!$fields) {
                foreach ($this->fields($table) as $key => $field) {
                    if ($indexes) {
                        $field["auto_increment"] = 0;
                    }
                    $fields[] = $this->util->process_field($field, $field);
                    $originals[$key] = $this->idf_escape($key);
                }
            }
            $primary_key = false;
            foreach ($fields as $field) {
                if ($field[6]) {
                    $primary_key = true;
                }
            }
            $drop_indexes = [];
            foreach ($indexes as $key => $val) {
                if ($val[2] == "DROP") {
                    $drop_indexes[$val[1]] = true;
                    unset($indexes[$key]);
                }
            }
            foreach ($this->indexes($table) as $key_name => $index) {
                $columns = [];
                foreach ($index["columns"] as $key => $column) {
                    if (!$originals[$column]) {
                        continue 2;
                    }
                    $columns[] = $originals[$column] . ($index["descs"][$key] ? " DESC" : "");
                }
                if (!$drop_indexes[$key_name]) {
                    if ($index["type"] != "PRIMARY" || !$primary_key) {
                        $indexes[] = array($index["type"], $key_name, $columns);
                    }
                }
            }
            foreach ($indexes as $key => $val) {
                if ($val[0] == "PRIMARY") {
                    unset($indexes[$key]);
                    $foreign[] = "  PRIMARY KEY (" . implode(", ", $val[2]) . ")";
                }
            }
            foreach ($this->foreign_keys($table) as $key_name => $foreign_key) {
                foreach ($foreign_key["source"] as $key => $column) {
                    if (!$originals[$column]) {
                        continue 2;
                    }
                    $foreign_key["source"][$key] = $this->idf_unescape($originals[$column]);
                }
                if (!isset($foreign[" $key_name"])) {
                    $foreign[] = " " . $this->format_foreign_key($foreign_key);
                }
            }
            $this->db->queries("BEGIN");
        }
        foreach ($fields as $key => $field) {
            $fields[$key] = "  " . implode($field);
        }
        $fields = array_merge($fields, array_filter($foreign));
        $temp_name = ($table == $name ? "adminer_$name" : $name);
        if (!$this->db->queries("CREATE TABLE " . $this->table($temp_name) . " (\n" . implode(",\n", $fields) . "\n)")) {
            // implicit ROLLBACK to not overwrite $this->connection->error
            return false;
        }
        if ($table != "") {
            if ($originals && !$this->db->queries("INSERT INTO " . $this->table($temp_name) .
                " (" . implode(", ", $originals) . ") SELECT " . implode(
                    ", ",
                    array_map(function ($key) {
                   return $this->idf_escape($key);
               }, array_keys($originals))
                ) . " FROM " . $this->table($table))) {
                return false;
            }
            $triggers = [];
            foreach ($this->triggers($table) as $trigger_name => $timing_event) {
                $trigger = $this->trigger($trigger_name);
                $triggers[] = "CREATE TRIGGER " . $this->idf_escape($trigger_name) . " " .
                    implode(" ", $timing_event) . " ON " . $this->table($name) . "\n$trigger[Statement]";
            }
            $auto_increment = $auto_increment ? 0 :
                $this->connection->result("SELECT seq FROM sqlite_sequence WHERE name = " .
                $this->q($table)); // if $auto_increment is set then it will be updated later
            // drop before creating indexes and triggers to allow using old names
            if (!$this->db->queries("DROP TABLE " . $this->table($table)) ||
                ($table == $name && !$this->db->queries("ALTER TABLE " . $this->table($temp_name) .
                " RENAME TO " . $this->table($name))) || !$this->alter_indexes($name, $indexes)
            ) {
                return false;
            }
            if ($auto_increment) {
                $this->db->queries("UPDATE sqlite_sequence SET seq = $auto_increment WHERE name = " . $this->q($name)); // ignores error
            }
            foreach ($triggers as $trigger) {
                if (!$this->db->queries($trigger)) {
                    return false;
                }
            }
            $this->db->queries("COMMIT");
        }
        return true;
    }

    protected function index_sql($table, $type, $name, $columns)
    {
        return "CREATE $type " . ($type != "INDEX" ? "INDEX " : "")
            . $this->idf_escape($name != "" ? $name : uniqid($table . "_"))
            . " ON " . $this->table($table)
            . " $columns"
        ;
    }

    public function alter_indexes($table, $alter)
    {
        foreach ($alter as $primary) {
            if ($primary[0] == "PRIMARY") {
                return $this->recreate_table($table, $table, [], [], [], 0, $alter);
            }
        }
        foreach (array_reverse($alter) as $val) {
            if (!$this->db->queries(
                $val[2] == "DROP" ? "DROP INDEX " . $this->idf_escape($val[1]) :
                $this->index_sql($table, $val[0], $val[1], "(" . implode(", ", $val[2]) . ")")
            )) {
                return false;
            }
        }
        return true;
    }

    public function truncate_tables($tables)
    {
        return $this->db->apply_queries("DELETE FROM", $tables);
    }

    public function drop_views($views)
    {
        return $this->db->apply_queries("DROP VIEW", $views);
    }

    public function drop_tables($tables)
    {
        return $this->db->apply_queries("DROP TABLE", $tables);
    }

    public function move_tables($tables, $views, $target)
    {
        return false;
    }

    public function trigger($name)
    {
        if ($name == "") {
            return array("Statement" => "BEGIN\n\t;\nEND");
        }
        $idf = '(?:[^`"\s]+|`[^`]*`|"[^"]*")+';
        $trigger_options = $this->trigger_options();
        preg_match(
            "~^CREATE\\s+TRIGGER\\s*$idf\\s*(" . implode("|", $trigger_options["Timing"]) . ")\\s+([a-z]+)(?:\\s+OF\\s+($idf))?\\s+ON\\s*$idf\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",
            $this->connection->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = " . $this->q($name)),
            $match
        );
        $of = $match[3];
        return array(
            "Timing" => strtoupper($match[1]),
            "Event" => strtoupper($match[2]) . ($of ? " OF" : ""),
            "Of" => ($of[0] == '`' || $of[0] == '"' ? $this->idf_unescape($of) : $of),
            "Trigger" => $name,
            "Statement" => $match[4],
        );
    }

    public function triggers($table)
    {
        $return = [];
        $trigger_options = $this->trigger_options();
        foreach ($this->db->get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = " . $this->q($table)) as $row) {
            preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*(' . implode("|", $trigger_options["Timing"]) . ')\s*(.*?)\s+ON\b~i', $row["sql"], $match);
            $return[$row["name"]] = array($match[1], $match[2]);
        }
        return $return;
    }

    public function trigger_options()
    {
        return array(
            "Timing" => array("BEFORE", "AFTER", "INSTEAD OF"),
            "Event" => array("INSERT", "UPDATE", "UPDATE OF", "DELETE"),
            "Type" => array("FOR EACH ROW"),
        );
    }

    public function begin()
    {
        return $this->db->queries("BEGIN");
    }

    public function last_id()
    {
        return $this->connection->result("SELECT LAST_INSERT_ROWID()");
    }

    public function explain($connection, $query)
    {
        return $connection->query("EXPLAIN QUERY PLAN $query");
    }

    public function create_sql($table, $auto_increment, $style)
    {
        $return = $this->connection->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = " . $this->q($table));
        foreach ($this->indexes($table) as $name => $index) {
            if ($name == '') {
                continue;
            }
            $return .= ";\n\n" . $this->index_sql(
                $table,
                $index['type'],
                $name,
                "(" . implode(", ", array_map(function ($key) {
                    return $this->idf_escape($key);
                }, $index['columns'])) . ")"
            );
        }
        return $return;
    }

    public function truncate_sql($table)
    {
        return "DELETE FROM " . $this->table($table);
    }

    public function trigger_sql($table)
    {
        return implode($this->db->get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = " . $this->q($table)));
    }

    public function show_variables()
    {
        $return = [];
        foreach (array("auto_vacuum", "cache_size", "count_changes", "default_cache_size", "empty_result_callbacks", "encoding", "foreign_keys", "full_column_names", "fullfsync", "journal_mode", "journal_size_limit", "legacy_file_format", "locking_mode", "page_size", "max_page_count", "read_uncommitted", "recursive_triggers", "reverse_unordered_selects", "secure_delete", "short_column_names", "synchronous", "temp_store", "temp_store_directory", "schema_version", "integrity_check", "quick_check") as $key) {
            $return[$key] = $this->connection->result("PRAGMA $key");
        }
        return $return;
    }

    public function show_status()
    {
        $return = [];
        foreach ($this->db->get_vals("PRAGMA compile_options") as $option) {
            list($key, $val) = explode("=", $option, 2);
            $return[$key] = $val;
        }
        return $return;
    }

    public function support($feature)
    {
        return preg_match('~^(columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~', $feature);
    }

    public function driver_config()
    {
        return array(
            'possible_drivers' => array("SQLite3", "PDO_SQLite"),
            'jush' => "sqlite",
            'types' => array("integer" => 0, "real" => 0, "numeric" => 0, "text" => 0, "blob" => 0),
            'structured_types' => array_keys($this->types),
            'unsigned' => [],
            'operators' => array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "IN", "IS NULL", "NOT LIKE", "NOT IN", "IS NOT NULL", "SQL"), // REGEXP can be user defined function
            'functions' => array("hex", "length", "lower", "round", "unixepoch", "upper"),
            'grouping' => array("avg", "count", "count distinct", "group_concat", "max", "min", "sum"),
            'edit_functions' => array(
                array(
                    // "text" => "date('now')/time('now')/datetime('now')",
                ),
                array(
                    "integer|real|numeric" => "+/-",
                    // "text" => "date/time/datetime",
                    "text" => "||",
                )
            ),
        );
    }
}
