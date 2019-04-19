<?php

namespace Inpsyde\MultilingualPress2to3\Db;

use Exception;
use Throwable;
use UnexpectedValueException;
use wpdb as Wpdb;

/**
 * Functionality for running database operations using WPDB.
 *
 * @package MultilingualPress2to3
 */
trait DatabaseWpdbTrait
{

    /**
     * Inserts a record defined by provided data into a table with the specified name.
     *
     * @param string $name The name of the table to insert into.
     * @param array|object $data The data of the record to insert.
     *
     * @return int|null The inserted row's ID, if applicable; otherwise null;
     *
     * @throws Throwable If problem inserting.
     */
    protected function _insert($name, $data)
    {
        $data = (array) $data;
        $db = $this->_getDb();

        $db->insert($name, $data);
        $error = $db->last_error;
        $insertId = $db->insert_id;

        // Error while inserting
        if (empty($result) && !empty($error)) {
            throw new Exception($error);
        }

        // No new insert ID generated
        if ($insertId === 0) {
            return null;
        }

        return $insertId;
    }

    /**
     * Retrieves rows from the database matching the query conditions.
     *
     * @param string $query A query with optional `sprintf()` style placeholders.
     * @param array $values A list of values for the query placeholders.
     *
     * @return object[] A list of records.
     *
     * @throws Throwable If problem selecting.
     */
    protected function _select(string $query, $values = [])
    {
        $db = $this->_getDb();

        // Preparing query
        if (!empty($values)) {
            $prepared = $db->prepare($query, $values);

            if (!empty($query) && empty($prepared)) {
                throw new UnexpectedValueException($this->__('Could not prepare query "%1$s"', [$query]));
            }

            $query = $prepared;
        }

        $result = $db->get_results($query, 'OBJECT');
        $error = $db->last_error;

        // Error while selecting
        if (empty($result) && !empty($error)) {
            throw new Exception($this->__('Error selecting with query "%1$s":
            "%2$s"', [$query, $error]));
        }

        return $result;
    }

    /**
     * Creates a new table according to the specified parameters.
     *
     * @param string $tableName The name of the table to create.
     * @param array<string, array<int, mixed>> $fields A map of field names to field descriptors.
     * Each descriptor has the following keys, where asterisk (*) marks optional keys:
     *   type           Type of the field. Any of the allowed types. Case-insensitive.
     *   typemod*       A type modifier, such as "UNSIGNED" for numeric values.
     *   size*          The size of the field. If specified, will be added to the type verbatim.
     *   default*       If specified, will be added as the default value for the field.
     *                  Boolean, null, int, and float will not be quoted; everything else will be.
     *   null*          Whether or not the field allows NULL.
     *   autoincrement* Whether or not this field should be auto-incremented.
     * If the descriptor is not an array, it is assumed to be the type. This can be used as shorthand.
     * @param string[] $primaryKeys A list of field names which make the primary keys of the table.
     * All primary keys must be described in $fields.
     *
     * @return void
     *
     * @throws Exception If a primary key field is specified but not defined.
     * @throws Exception If a field with an empty name is specified.
     * @throws Exception If a field with an empty type is specified.
     * @throws Exception If problem making the change to the database.
     * @throws Throwable If problem creating.
     */
    protected function _createTable(string $tableName, array $fields, array $primaryKeys)
    {
        $db = $this->_getDb();
        $charset = $db->get_charset_collate();
        $query = "CREATE TABLE $tableName (\n";

        // Validating primary keys
        foreach ($primaryKeys as $key) {
            if (!array_key_exists($key, $fields)) {
                throw new Exception($this->__('No field descriptor specified for primary key "%1$s"', [$key]));
            }
        }

        foreach ($fields as $name => $field) {
            $name = trim($name);
            if (empty($name)) {
                throw new Exception($this->__('Fields are required to have a non-empty name'));
            }

            if (!is_array($field)) {
                $field = ['type' => (string) $field];
            }

            $type = isset($field['type']) ? strtolower(trim($field['type'])) : null;
            if (empty($type)) {
                throw new Exception($this->__('Field "%1$s" is required to have a non-empty type', [$name]));
            }

            $typeMod = isset($field['typemod']) ? strtoupper(trim($field['typemod'])) : null;
            $size = $field['size'] ?? null;
            $isNull = $field['null'] ?? true;
            $isAutoincrement = $field['autoincrement'] ?? false;

            $query .= "$name $type";
            $query .= !empty($size) ? "($size)" : '';
            $query .= !empty($typeMod) ? " $typeMod" : '';
            if (array_key_exists('default', $field)) {
                $default = $field['default'] ?? null;
                if (is_null($default)) {
                    $default = 'NULL';
                } elseif (is_int($default) || is_float($default)) {
                    $default = (string) $default;
                } elseif (is_bool($default)) {
                    $default = $default ? 'true' : 'false';
                } else {
                    $default = "'$default'";
                }

                $query .= " DEFAULT $default";
            }

            $query .= $isNull ? ' NULL' : ' NOT NULL';
            $query .= $isAutoincrement ? ' AUTO_INCREMENT' : '';
            $query .= ",\n";
        }

        $query .= sprintf('PRIMARY KEY  (%1$s)' . PHP_EOL, implode(',', $primaryKeys));
        $query .= ") $charset;";

        $this->_alterDb($query);
    }

    /**
     * Alters the table to accommodate the table structure in the given query.
     *
     * @param string $query The SQL to alter the table with.
     *
     * @throws Exception If alteration query results in an error.
     * @throws Throwable If problem altering.
     */
    protected function _alterDb(string $query)
    {
        $db = $this->_getDb();
        dbDelta($query);

        $error = $db->last_error;
        if (!empty($error)) {
            throw new Exception($error);
        }
    }

    /**
     * Prefixes a table name.
     *
     * @param string $name The name to prefix.
     *
     * @return string The prefixed name.
     *
     * @throws Throwable If problem prefixing.
     */
    protected function _getPrefixedTableName($name)
    {
        $prefix = $this->_getDb()->prefix;

        return "{$prefix}$name";
    }

    /**
     * Retrieves the WPDB adapter used by this instance.
     *
     * @return Wpdb
     *
     * @throws Throwable
     */
    abstract protected function _getDb();

    /**
     * Translates a string, and replaces placeholders.
     *
     * @since [*next-version*]
     *
     * @param string $string  The format string to translate.
     * @param array  $args    Placeholder values to replace in the string.
     * @param mixed  $context The context for translation.
     *
     * @return string The translated string.
     */
    abstract protected function __($string, $args = array(), $context = null);
}
