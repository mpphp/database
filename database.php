<?php

/**
 * Insert a record in to the database.
 *
 * @param array $data <p>
 * An associative array of the record to be inserted into the database.
 * The key must be the same with the column name in the database
 * where the value is inserted.
 * </p>
 * @param string $table <p>
 * The name of the database table that would hold the newly created
 * record.
 * </p>
 * @param bool $return_insert_id <p>
 * For a successful insert query mysqli_query() will return True
 * setting $return_insert_id to true will call mysqli_insert_id()
 * requesting the id of the newly inserted record.
 * </p>
 * @param mysqli|null $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @return bool|int|mysqli_result|string
 */
function db__create(array $data, string $table, $return_insert_id = false, $conn = null, array $config = [])
{
    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    /* 
     * Set result to false just in case nothing matches the connection type.
     * This would let us know nothing happened instead of causing a variable
     * not set error.
     * */
    $result = false;

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            /*
             * Prepare the data.
             *
             * For more on data preparation using this approach refer to
             * dbPrepareMysqliStatement() documentation below.
             * */
            $preparedData = db__prepare_mysqli_statement($data, $conn);

            /*
             * Construct the statement for an insert query.
             * We will need to break this up into bits to enable us perform
             * dynamic data insertion.
             * */
            $statement  = "INSERT INTO {$table} ";

            /*
             * WOW! WHAT'S GOING ON HERE?
             *
             * We are trying to specify the columns we are inserting records
             * into using an unfamiliar approach, let me explain...
             * implode() is a php function used to join array elements with
             * a string and array_keys() return's an array of all the keys
             * of an array. So basically what we are trying to do here is
             * that we are asking php to get all the keys in our prepared
             * data using array_keys() and the form a string out of it
             * separating each one with a comma.
             *
             * @example ['fruit' => 'orange', 'car' => 'Toyota'] will now be "fruit, car".
             * @reference For more on implode() visit http://php.net/manual/en/function.implode.php
             * @reference For more on array_keys() http://php.net/manual/en/function.array-keys.php
             * */
            $statement .= "(" . implode(', ', array_keys($preparedData)) . ") ";
            /*
             * WOW! WHAT'S GOING ON HERE?
             *
             * Well the same exact thing mentioned above only this time we are
             * asking for a string of all the values in our prepared data
             * using array_values() which returns an array of all the
             * values of an array
             *
             * @example ['fruit' => 'orange', 'car' => 'Toyota'] will now be "orange, Toyota".
             * @reference For more on array_values() http://php.net/manual/en/function.array-values.php
             * */
            $statement .= "VALUES(" . implode(', ', array_values($preparedData)) . ")";

            /*
             * So with the above code we will now have a valid database statement that looks
             * like this "INSERT INTO items (fruit, car) VALUES('orange', 'Toyota')" ready
             * for query execution.
             *
             * For more on query execution using this approach refer to
             * dbExecuteStatement() documentation below.
             * */
            $result = db__execute_statement($statement, $conn, $config);

            /*
             * If the user specified they want the id of the newly inserted
             * record, return the result from mysqli_insert_id(),  else
             * return a boolean.*/
            $result = $return_insert_id ? mysqli_insert_id($conn) : $result;

            break;
        default;
    }

    return $result;
}

/**
 * Delete a record from the database.
 *
 * @param string $table <p>
 * The name of the database table where the record will be removed from.
 * </p>
 * @param array $clauses <p>
 * This is an array containing an array of the where clauses that would
 * be used to select the record be to deleted.
 * e.g.
 * 1. this [['id', '=', 45]] would mean "WHERE id = 45".
 * 2. This [['id', '=', 45], ['age', '>', 50]] WHERE id = 45 AND age = 50.
 * </p>
 * @param int $limit <p>
 * Limit the record that should be selected. This is generally a good
 * practice especially where the selection clause passed isn't by
 * a unique record to avoid accidentally modifying or deleting
 * records in your database.
 * </p>
 * @param mysqli|null $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @return bool|mysqli_result
 */
function db__delete(string $table, array $clauses, int $limit = 1, $conn = null, array $config = [])
{
    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    /*
     * Set result to false just in case nothing matches the connection type.
     * This would let us know nothing happened instead of causing a variable
     * not set error.
     * */
    $result = false;

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            /*
             * Construct the statement for a delete query.
             * We will need to break this up into bits to enable us perform
             * dynamic data insertion.
             * */
            $statement = "DELETE FROM {$table} ";

            /*
             * WOW! WHAT'S GOING ON HERE?
             *
             * Remember the weired array clause passed as an argument?
             * Well the dbArrayClauseConverter() demystifies it before
             * we can use it in a mysqli statement so that our
             * "[['id', '=', 45], ['age', '>', 50]]" array can look like
             * "WHERE id = 45 AND age = 50".
             *
             * For more on array array clause demystification please refer to
             * dbArrayClauseConverter() documentation below.
             * */
            $statement .= db__array_clause_converter($clauses, $conn);

            // Set limit.
            $statement .= "LIMIT {$limit}";

            /*
             * So with the above code we will now have a valid database statement that looks
             * like this "DELETE FROM items WHERE id = 45 and age = 50" and ready for
             * query execution.
             *
             * For more on query execution using this approach refer to
             * dbExecuteStatement() documentation below.
             * */
            $result = db__execute_statement($statement, $conn, $config);

            break;
    }

    return $result;
}

/**
 * Retrieve records from the database.
 *
 * @param string $table <p>
 * The name of the database table where the record will be retrieved from.
 * </p>
 * @param array $clauses <p>
 * This is an array containing an array of the where clauses that would
 * be used to select the records. Not specifying any constraints will
 * return all the records.
 * e.g.
 * 1. this [['id', '=', 45]] would mean "WHERE id = 45".
 * 2. This [['id', '=', 45], ['age', '>', 50]] WHERE id = 45 AND age = 50.
 * </p>
 * @param int $limit <p>
 * Limit the record that should be selected. By default limit is set to 15.
 * </p>
 * @param array $order <p>
 * This is an array specifying the order which the record should be returned.
 * e.g. ['id', 'asc']-------------------------------------------------------------------- Verify
 * @param array $columns <p>
 * Specify the columns to be returned. Not setting this will return all the
 * columns.
 * </p>
 * @param null $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value on while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @return array
 */
function db__read(string $table, array $clauses = [], array $columns = ['*'], int $limit = 15, array $order = [],
                 $conn = null, array $config = [])
{
    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    // Set data variable to an empty array.
    $data = [];

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            /*
             * Construct the statement for a select query.
             * We will need to break this up into bits to enable us perform
             * dynamic data insertion.
             * */
            $statement = "SELECT ";

            /*
             * Join our column array elements into a string with a comma separating each element
             * */
            $statement .= implode(', ', $columns);

            $statement .= " FROM {$table}";

            /*
             * WOW! WHAT'S GOING ON HERE?
             *
             * Remember the weired array clause passed as an argument?
             * Well the dbArrayClauseConverter() demystifies it before
             * we can use it in a mysqli statement so that our
             * "[['id', '=', 45], ['age', '>', 50]]" array can look like
             * "WHERE id = 45 AND age = 50".
             *
             * For more on array array clause demystification please refer to
             * dbArrayClauseConverter() documentation below.
             * */
            $statement .= db__array_clause_converter($clauses, $conn);

            /*
             * Here we are checking to see if the user specified a preferred order
             * for the selected records.
             * */
            $statement .= !empty($order) ? " ORDER BY ".implode(" ", $order) : null;

            // Set limit.
            $statement .= " LIMIT {$limit}";

            /*
             * So with the above code we will now have a valid database statement that looks
             * like this "SELECT FROM items WHERE id = 45 and age = 50" and ready for
             * query execution.
             *
             * For more on query execution using this approach refer to
             * dbExecuteStatement() documentation below.
             * */
            $result = db__execute_statement($statement, $conn, $config);

            // This is to make sure we don't call mysqli_fetch_assoc() from the view.
            if (mysqli_num_rows($result) > 1) {

                /*
                 * Check to see if the number of rows returned from the database
                 * is higher than one so that we can extract it in to an array.
                 * */

                // Assign the results to a the data variable.
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }

            } else {

                // Assign the result to a the data variable.
                $data = mysqli_fetch_assoc($result);

            }

            // Free the memory associated with this result.
            mysqli_free_result($result);

            break;
    }

    // Return data retrieved from the database.
    return $data;
}

/**
 * Update a record in the database.
 *
 * @param array $data <p>
 * An associative array of the record to be inserted into the database.
 * The key must be the same with the column name in the database
 * where the value is inserted.
 * </p>
 * @param array $clauses <p>
 * This is an array containing an array of the clauses that would
 * be used to select the records to be updated.
 * </p>
 * @param string $table <p>
 * The name of the database table where the record will be retrieved from.
 * </p>
 * @param int $limit <p>
 * Limit the record that should be selected.
 * </p>
 * @param null $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value on while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @return bool|mysqli_result
 */
function db__update(array $data, array $clauses, string $table, $limit = 1, $conn = null, array $config = [])
{

    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    /*
     * Set result to false just in case nothing matches the connection type.
     * This would let us know nothing happened instead of causing a variable
     * not set error.
     * */
    $result = false;

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            /*
             * Prepare the data.
             *
             * For more on data preparation using this approach refer to
             * dbPrepareMysqliStatement() documentation below.
             * */
            $preparedData = db__prepare_mysqli_statement($data, $conn);

            // Construct statement
            $statement  = "UPDATE {$table} SET";

            /*
             * Simple logic to determine when we hit the last item on the array to avoid
             * using a comma at the end of it which will cause mysql to fail.
             *
             * Help: Feel free to suggest a better approach by making a pull request
             * Or posting on the Facebook group page.
            */
            $i = count($preparedData);
            $x = 1;
            foreach ($preparedData as $key => $value) {
                $statement .= " {$key} = {$value}";
                if ($i !== $x) {
                    $statement .= ", ";
                    $x++;
                }
            }

            /*
             * WOW! WHAT'S GOING ON HERE?
             *
             * Remember the weired array clause passed as an argument?
             * Well the dbArrayClauseConverter() demystifies it before
             * we can use it in a mysqli statement so that our
             * "[['id', '=', 45], ['age', '>', 50]]" array can look like
             * "WHERE id = 45 AND age = 50".
             *
             * For more on array array clause demystification please refer to
             * dbArrayClauseConverter() documentation below.
             * */
            $statement .= db__array_clause_converter($clauses, $conn);

            // Set limit.
            $statement .= " LIMIT {$limit}";

            /*
             * So with the above code we will now have a valid database statement that looks
             * like this "UPDATE items SET id = 34, age 50 WHERE id = 45 and age = 50" and ready for
             * query execution.
             *
             * For more on query execution using this approach refer to
             * dbExecuteStatement() documentation below.
             * */
            $result = db__execute_statement($statement, $conn, $config);

            break;
    }

    // Return result.
    return $result;
}

/**
 * Closes a database connection.
 *
 * @param $conn <p>
 * The connection to be closed.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 */
function db__close_connection($conn = null, array $config = [])
{
    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    if (empty($config)) {
        global $app;

        $config = $app['configs']['app']['database'];
    }

    if ($conn === null) {
        $conn = $app['database'][$config['default']]['connection'];
    }

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysql...
        case 'mysql':

            // Closes a previously opened mysqli database connection
            mysqli_close($conn);

            break;

    }
}

/**
 * Open a database connection.
 *
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @param bool $return_connection
 * @return mysqli
 */
function db__open_connection(array $config = [], $return_connection = false)
{
    global $app;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    if (empty($config)) {
        global $app;

        $config = $app['configs']['app']['database'];
    }

    $conn = null;

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {
        case 'mysql':
            // Attempt mysql database connection
            $conn = mysqli_connect(
                $config['connections']['mysql']['host'],        // Database host.
                $config['connections']['mysql']['username'],    // Database username.
                $config['connections']['mysql']['password'],    // Database password.
                $config['connections']['mysql']['database'],    // Database name
                $config['connections']['mysql']['port'],        // Mysql port
                $config['connections']['mysql']['socket']       // Socket
            );

            // Print out any errors that may occur while attempting
            // to connect to database.
            // if (mysqli_connect_error()) {
            //     print "Database connection failed: "
            //         . mysqli_error($conn)
            //         . ' (' . mysqli_errno($conn) . ')';
            //     exit;
            // }
            break;
    }

    if($return_connection) {
        return $conn;
    }

    $app['database'][$config['default']]['connection'] = $conn;
}

/**
 * Execute a query statement.
 *
 * @param string $statement <p>
 * The statement to be executed.
 * </p>
 * @param mysqli $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value on while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 * @param array $config <p>
 * The configuration that would be used to perform the database query.
 * This is an associative array containing the type of connection
 * with "connection" as the key and mysqli, sqlite, e.t.c as the
 * value. This allows the application to dynamically switch
 * between databases without creating a new function for
 * a different api call.
 * </p>
 *
 * @return bool|mysqli_result
 */
function db__execute_statement(string $statement, $conn = null, array $config = [])
{
    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    /*
     * Set result to false just in case nothing matches the connection type.
     * This would let us know nothing happened instead of causing a variable
     * not set error.
     * */
    $result = false;

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn === null ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            /*
             * Performs a mysqli query on the database
             * @reference For more on mysqli_query() visit: http://php.net/manual/en/mysqli.query.php
             * */
            $result = mysqli_query($conn, $statement);

            /*
             * Test for a query error.
             * Note: This will only be triggered if there is a mysql error and not when null record is return.
             *
             * @reference For more on mysqli_affected_rows() visit: http://www.php.net/manual/en/mysqli.affected-rows.php
             * @reference For more on mysqli_error() visit: http://docs.php.net/manual/da/mysqli.error.php
             * @reference For more on mysqli_errno() visit: http://php.net/manual/en/mysqli.errno.php
             * */
            if (!$result && mysqli_affected_rows($conn) >= 0) {

                // Output error messages.
                print 'Database query failed: ' . mysqli_error($conn)
                    . ' (' . mysqli_errno($conn) . ')';
                exit;

            }
            break;
    }

    return $result;
}

/**
 * Prepare data for mysqli query execution
 * @param array|int|string $data <p>
 * The data to be prepared for a query statement.
 * </p>
 * @param mysqli $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value on while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 *
 * @return array|string
 */
function db__prepare_mysqli_statement($data, $conn)
{
    /*
     * Refer to MPPHP's $app variable created in the index.php file
     * to enable use use it in this function scope
     * */
    global $app;

    /*
     * Check if the user provided a database configuration for this query,
     * else use the MPPHP's default database configuration.
     * */
    $config = empty($config) ? $app['configs']['app']['database'] : $config;

    /*
     * Check if the user provided a database connection for this query,
     * else use the MPPHP's default database connection.
     * */
    $conn = $conn ? $conn : $app['database'][$config['default']]['connection'];

    /*
     * Check the type of database configuration to enable the application
     * use the appropriate database api.
     * */
    switch($config['default']) {

        // If it is mysqli...
        case 'mysql':

            // If the data passed is an array
            if (is_array($data)) {

                // Loop through array key value pair
                foreach ($data as $key => $value) {

                    // Search for the values that are not numeric
                    if (!is_numeric($value)) {

                        // Perform a mysqli real escape string and wrap a column around each one.
                        $data[$key] = "'" . mysqli_real_escape_string($conn, $value) . "'";
                    }
                }
            } else {

                // Search for the values that are not numeric
                if (!is_numeric($data)) {

                    // Perform a mysqli real escape string and wrap a column around each one.
                    $data = "'" . mysqli_real_escape_string($conn, $data) . "'";
                }
            }
            break;
    }

    // Return data.
    return $data;
}

/**
 * Convert the array into a mysqli clause statement.
 *
 * @param array $clauses <p>
 * An array containing an array of clauses to be converted.
 * This would look like this [['id', '=', 3]] or [['id', '>', 4], ['age', '<', 30]].
 * This function will separate each array clauses with AND and the elements in the
 * array as WHERE.
 * </p>
 * @param $conn <p>
 * The database connection that would be used to perform the
 * query to the database. Not setting this value on while
 * calling the function will cause it to default to
 * MPPHP's default database connection.
 * </p>
 *
 * @return null|string
 */
function db__array_clause_converter(array $clauses, $conn)
{
    // If the clause array is empty
    if (empty($clauses)) {
        return null; // Return Null
    }

    // Construct a where statement
    $statement = " WHERE";

    /*
     * Simple logic to determine when we hit the last item on the array to avoid
     * using a comma at the end of it which will cause mysql to fail.
     *
     * Note: Feel free to suggest a better approach by making a pull request
     * Or posting on the Facebook group page.
    */
    $i = count($clauses);
    $x = 1;

    // Loop through clauses
    foreach($clauses as $clause) {

        // Count the items in the array
        if (count($clause) > 2) { // If it is greater than 2
            // Construct statement
            $statement .= " {$clause[0]} {$clause[1]} " . db__prepare_mysqli_statement($clause[2], $conn);

            // Check if this is the last item on the array
            if ($i !== $x) { // If not append 'AND' to the statement
                $statement .= " AND ";
                $x++; // Increment $x
            }
        }else { // Else

            // Construct statement
            $statement .= " {$clause[0]} {$clause[1]}";

            // Check if this is the last item on the array
            if ($i !== $x) { // If not append 'AND' to the statement
                $statement .= " AND ";
                $x++; // Increment $x
            }
        }

    }

    return $statement;
}

/**
 * Select the data that should be passed into a database insert or update statement.
 * This is useful when you don't want to insert all the data submitted with
 * form into your database maybe cause the specific field isn't meant for the
 * database which will cause an error.
 * </p>
 * @param array $data <p>
 * The data to be inserted.
 * </p>
 * @param array $fillable_fields <p>
 * This is an array of the field keys you want to be inserted into the database.
 * </p>
 *
 * @return array
 */
function db__fillables(array $data, array $fillable_fields = []):array
{
    $fillables = [];

    foreach ($data as $key => $value) {
        foreach ($fillable_fields as $field) {
            if ($key === $field) {
                $fillables[$key] = $value;
            }
        }
    }

    return $fillables;
}
