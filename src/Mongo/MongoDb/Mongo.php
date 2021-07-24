<?php

namespace Lagdo\Adminer\Drivers\Mongo;

$drivers["mongo"] = "MongoDB (alpha)";

if (isset($_GET["mongo"])) {
    define("DRIVER", "mongo");
}

class Mongo extends \Lagdo\Adminer\Drivers\Mongo {

    var $operators = array(
        "=",
        "!=",
        ">",
        "<",
        ">=",
        "<=",
        "regex",
        "(f)=",
        "(f)!=",
        "(f)>",
        "(f)<",
        "(f)>=",
        "(f)<=",
        "(date)=",
        "(date)!=",
        "(date)>",
        "(date)<",
        "(date)>=",
        "(date)<=",
    );

    function get_databases($flush) {
        global $connection;
        $return = array();
        foreach ($connection->executeCommand('admin', array('listDatabases' => 1)) as $dbs) {
            foreach ($dbs->databases as $db) {
                $return[] = $db->name;
            }
        }
        return $return;
    }

    function count_tables($databases) {
        $return = array();
        return $return;
    }

    function tables_list() {
        global $connection;
        $collections = array();
        foreach ($connection->executeCommand($connection->_db_name, array('listCollections' => 1)) as $result) {
            $collections[$result->name] = 'table';
        }
        return $collections;
    }

    function drop_databases($databases) {
        return false;
    }

    function indexes($table, $connection2 = null) {
        global $connection;
        $return = array();
        foreach ($connection->executeCommand($connection->_db_name, array('listIndexes' => $table)) as $index) {
            $descs = array();
            $columns = array();
            foreach (get_object_vars($index->key) as $column => $type) {
                $descs[] = ($type == -1 ? '1' : null);
                $columns[] = $column;
            }
            $return[$index->name] = array(
                "type" => ($index->name == "_id_" ? "PRIMARY" : (isset($index->unique) ? "UNIQUE" : "INDEX")),
                "columns" => $columns,
                "lengths" => array(),
                "descs" => $descs,
            );
        }
        return $return;
    }

    function fields($table) {
        global $driver;
        $fields = fields_from_edit();
        if (!$fields) {
            $result = $driver->select($table, array("*"), null, null, array(), 10);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    foreach ($row as $key => $val) {
                        $row[$key] = null;
                        $fields[$key] = array(
                            "field" => $key,
                            "type" => "string",
                            "null" => ($key != $driver->primary),
                            "auto_increment" => ($key == $driver->primary),
                            "privileges" => array(
                                "insert" => 1,
                                "select" => 1,
                                "update" => 1,
                            ),
                        );
                    }
                }
            }
        }
        return $fields;
    }

    function found_rows($table_status, $where) {
        global $connection;
        $where = where_to_query($where);
        $toArray = $connection->executeCommand($connection->_db_name, array('count' => $table_status['Name'], 'query' => $where))->toArray();
        return $toArray[0]->n;
    }

    function sql_query_where_parser($queryWhere) {
        $queryWhere = preg_replace('~^\sWHERE \(?\(?(.+?)\)?\)?$~', '\1', $queryWhere);
        $wheres = explode(' AND ', $queryWhere);
        $wheresOr = explode(') OR (', $queryWhere);
        $where = array();
        foreach ($wheres as $whereStr) {
            $where[] = trim($whereStr);
        }
        if (count($wheresOr) == 1) {
            $wheresOr = array();
        } elseif (count($wheresOr) > 1) {
            $where = array();
        }
        return where_to_query($where, $wheresOr);
    }

    function where_to_query($whereAnd = array(), $whereOr = array()) {
        global $adminer;
        $data = array();
        foreach (array('and' => $whereAnd, 'or' => $whereOr) as $type => $where) {
            if (is_array($where)) {
                foreach ($where as $expression) {
                    list($col, $op, $val) = explode(" ", $expression, 3);
                    if ($col == "_id" && preg_match('~^(MongoDB\\\\BSON\\\\ObjectID)\("(.+)"\)$~', $val, $match)) {
                        list(, $class, $val) = $match;
                        $val = new $class($val);
                    }
                    if (!in_array($op, $adminer->operators)) {
                        continue;
                    }
                    if (preg_match('~^\(f\)(.+)~', $op, $match)) {
                        $val = (float) $val;
                        $op = $match[1];
                    } elseif (preg_match('~^\(date\)(.+)~', $op, $match)) {
                        $dateTime = new DateTime($val);
                        $class = 'MongoDB\BSON\UTCDatetime';
                        $val = new $class($dateTime->getTimestamp() * 1000);
                        $op = $match[1];
                    }
                    switch ($op) {
                        case '=':
                            $op = '$eq';
                            break;
                        case '!=':
                            $op = '$ne';
                            break;
                        case '>':
                            $op = '$gt';
                            break;
                        case '<':
                            $op = '$lt';
                            break;
                        case '>=':
                            $op = '$gte';
                            break;
                        case '<=':
                            $op = '$lte';
                            break;
                        case 'regex':
                            $op = '$regex';
                            break;
                        default:
                            continue 2;
                    }
                    if ($type == 'and') {
                        $data['$and'][] = array($col => array($op => $val));
                    } elseif ($type == 'or') {
                        $data['$or'][] = array($col => array($op => $val));
                    }
                }
            }
        }
        return $data;
    }
}
