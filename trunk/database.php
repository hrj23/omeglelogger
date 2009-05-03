<?php

/*

Database functions provided by the guys from #nomad

*/

/*  Function: db_connect()
 *
 *  Syntax: db_connect(  $db_host, $db_user, $db_password, $db_name, $db_port, $persist )
 *
 *  Description: Connects to the db, and returns the connection
 *
 *  Precondition:  None
 *
 *  Postcondition: Connects to the db, and returns the connection
 *
 */

function db_connect( $conn_name )
{
	if ( ! isset($GLOBALS['__DB_CONN'][$conn_name] ) )
		{
			$GLOBALS['__DB_CONN'][$conn_name] = FALSE;
		}
	if ( $GLOBALS['__DB_CONN'][$conn_name] == FALSE )
		{
			$db = $GLOBALS['__DB_LIST'][$conn_name];
			if ( isset($db['alias']) && !isset($GLOBALS['__DB_LIST'][$conn_name]['db']) )
				{
					// This is just an alias to another connection.
					$GLOBALS['__DB_CONN'][$conn_name] = db_connect($db['alias']);
					return($GLOBALS['__DB_CONN'][$conn_name]);
				}
			else
				{
					if ( isset($db['port']) )
						{
							$path = $db['host'].":".$db['port'];
						}
					else
						{
							$path = $db['host'];
						}

					if ( $db['nopersist'] == 1 )
						{
							$GLOBALS['__DB_CONN'][$conn_name] = @mysql_connect( $path, $db['user'], $db['pass'] );
						}
					else
						{
							$GLOBALS['__DB_CONN'][$conn_name] = @mysql_pconnect( $path, $db['user'], $db['pass'] );
						}

					if ( $GLOBALS['__DB_CONN'][$conn_name] == FALSE )
						{
							trigger_error("DB(".$conn_name.") Connetion Error: "  . mysql_errno() .": ". mysql_error() );
							die();
						}
					if( ! mysql_select_db( $db['db'], $GLOBALS['__DB_CONN'][$conn_name] ) )
						{
							trigger_error("DB(".$conn_name.") Select DB Error: " . mysql_errno() .": ". mysql_error() );
							die();
						}
				}
		}
	return($GLOBALS['__DB_CONN'][$conn_name]);
}

/*  Function: db_read()
 *
 *  Syntax: db_read( $query, $db_name, $db_host, $db_port, $db_user, $db_password )
 *
 *  Description:  Takes the query, and reads the db
 *
 *  Precondition:  $query is not null
 *
 *  Postcondition:  Returns the fetched array
 *
 */

function db_read( $query, $key_by = '', $conn_name = 'read' )
{
	$conn = db_connect($conn_name);

	$mysql_result = mysql_query( $query, $conn );

	if( ! $mysql_result )
		{
			trigger_error("DB(".$conn_name.") Read Error: Query = \"". $query ."\"" );
			trigger_error("DB(".$conn_name.") Read Error: Error = ". mysql_errno($conn).": ".mysql_error($conn) );
			echo ("DB(".$conn_name.") Read Error: SQL = ". $query );
			die();
		}

	$GLOBALS['SQL_NUM_ROWS'] = @mysql_num_rows( $mysql_result );

	if ( $GLOBALS['SQL_NUM_ROWS'] != 0 )
		{
			if ( $key_by == '' )
				{
					// Don't key by anything but how it gets returned
					for( $i = 0; $i < $GLOBALS['SQL_NUM_ROWS']; $i++ )
						{
							$res[$i] = mysql_fetch_array( $mysql_result, MYSQL_ASSOC );
						}
				}
			else
				{
					//we're going to sort our data by the key(s)
					$keys = explode(",",$key_by);
					if(count($keys) > 2){
						// if you specify 3 or more keys
						for ( $i = 0; $i < $GLOBALS['SQL_NUM_ROWS']; $i++ )
							{
								$temp_result = mysql_fetch_array( $mysql_result, MYSQL_ASSOC );
								//chainArray($keys, 0, $temp_result, NULL, $res);
								quickChain($keys,$temp_result,$res);
								//$res[$temp_result[$key_by]] = $temp_result;
							}
					} elseif (count($keys) == 2) {
						//optimized 2 keys
						for ( $i = 0; $i < $GLOBALS['SQL_NUM_ROWS']; $i++ )
							{
								$temp_result = mysql_fetch_array( $mysql_result, MYSQL_ASSOC );
								$res[$temp_result[$keys[0]]][$temp_result[$keys[1]]] = $temp_result;
							}

					} else {
						//optimized 1 key
						for ( $i = 0; $i < $GLOBALS['SQL_NUM_ROWS']; $i++ )
							{
								$temp_result = mysql_fetch_array( $mysql_result, MYSQL_ASSOC );
								$res[$temp_result[$key_by]] = $temp_result;
							}
					}
				}
		}
	else
		{
			$res = array();
		}

	@mysql_free_result( $mysql_result );

	return $res;
}

function dbRead( $query, $key_by = '', $conn_name = 'read' ){
	return db_read($query,$key_by,$conn_name);
}

/* Function: db_write()
 *
 *  Syntax: db_write( $query, $db_name, $db_host, $db_port, $db_user, $db_password )
 *
 *  Description: Takes query, writes to the db
 *
 *  Precondition: $query is not null
 *
 *  Postcondition: Writes to db, returns nothing
 *
 */

function db_write( $query, $conn_name = "write" )
{
	$conn = db_connect($conn_name);

	$mysql_result = mysql_query( $query, $conn );

	if( $mysql_result == FALSE )
		{
			trigger_error("DB Write Error: Query = \"". $query ."\"");
			trigger_error("DB Write Error: Error = ". mysql_errno($conn).": ".mysql_error($conn) );
			die();
		}

	$GLOBALS['SQL_INSERT_ID'] = mysql_insert_id($conn);

	return $GLOBALS['SQL_INSERT_ID'];
}
function dbWrite($query, $conn_name = "write" ){
	return db_write($query,$conn_name);
}

/******************************************************************/
/**************** DATABASE UTILITY FUNCTIONS **********************/
/******************************************************************/
function db_insert( $table, $data_array, $update_where = '', $replace = false, $conn = "write" )
{
	if ( ! is_array($data_array) )
		{
			trigger_error("db_insert() called, but data_array is not an array",E_USER_NOTICE);
		}

	if ( $update_where == '' )
		{
			$sql_cmd = ($replace ? "REPLACE" : "INSERT")." INTO ";
			$sql_end = '';
		}
	else
		{
			$sql_cmd = "UPDATE ";
			$sql_end = $update_where;
		}

			$sql_cmd .= $table." SET ";
	foreach ( $data_array as $key => $value )
			{
				$sql_cmd .= "$key = '".addslashes($value)."', ";
			}
	$sql_cmd = substr($sql_cmd,0,-2).$sql_end;
	db_write($sql_cmd,$conn);
	return($GLOBALS['SQL_INSERT_ID']);
}

function dbInsert( $table, $data_array, $update_where = '', $replace = false, $conn = "write" ){
	return db_insert($table,$data_array,$update_where,$replace,$conn);
}

function db_unique( $table, $column, $value, $same_column = '', $same_value = '', $conn = 'read' )
{
	if ( $same_column != '' )
		{
			$where_part = " AND ".$same_column." != '".addslashes($same_value)."' ";
		}
	else
		{
			$where_part = '';
		}
	$db_result = db_read("SELECT $column FROM $table WHERE $column = '".addslashes($value)."'".$where_part." LIMIT 1",$conn);

	if ( isset($db_result[0][$column]) )
		{
			// The column is set
			//  this is not unique
			return FALSE;
		}
	else
		{
			// There is nothing in the column, this is unique
			return TRUE;
		}
}

function quickChain($keys,$data,&$array){
	if(is_array($keys)){
		//$i = 0;
		foreach($keys as $kKey => $kVal){
			//$i++;
			$keyString .= "['".$data[$kVal]."']";
			/*
			//safer method
			if(eval("return (is_array(\$array".$keyString.") ? 1 : 0);")){
				//is it an array?
				//echo "True<br>";
			} else {
				//ok we need to create the entry then
				//is this the last key?
				if(count($keys) == $i){
					//yes
					eval("\$array".$keyString." = \$data;");
				} else {
					//no
					eval("\$array".$keyString." = NULL;");
				}

				//echo "False<br>";

			}
			*/
		}

		//faster method
		eval("\$array".$keyString." = \$data;");
		return TRUE;
	}

	return FALSE;
}

//This function builds a skeleton array and pumps that into array_merge_clobber
//  chainArray is used on keys in db_read(...)
function chainArray($keys, $currentKey, &$data, $array, &$orig){
	//check to see if there is another key after this one
	if(isset($keys[$currentKey+1])){
		$array[$data[$keys[$currentKey]]] = chainArray($keys, ($currentKey+1), $data, $array, $ar2);
	} else {
		//this is the final key so set the data to the current array
		$array[$data[$keys[$currentKey]]] = $data;
		//if there is only one key, we can't return or our orig will not be merged
		if(count($keys) > 1){
			return $array;
		}
	}

	//update the final array
	if(!is_array($orig)){
		$orig = array();
	}
	$orig = array_merge_clobber($orig, $array);
	//$orig[$data[$keys[$currentKey]]] = $array[$data[$keys[$currentKey]]];
	return $array;
}

//from php.net does exactly what I want, which is slightly different from array_merge_recursive
function array_merge_clobber($a1,$a2) {
	if(!is_array($a1) || !is_array($a2)) return false;
	$newarray = $a1;
	while (list($key, $val) = each($a2)) {
	  if (is_array($val) && isset($newarray[$key]) && is_array($newarray[$key])) {
		$newarray[$key] = array_merge_clobber($newarray[$key], $val);
		} else {
		$newarray[$key] = $val;
		}
	}
	return $newarray;
}

//wrapper that always checks our global state
function dbEncode($string){
	global $USE_DBENCODING; //check the session var
	if($USE_DBENCODING){
		$pattern[0] = "/'/";
		$replace[0] = "\\'";
		$pattern[1] = '/\/';
		$replace[1] = '\\';

		return preg_replace($pattern,$replace,$string);

		//return eregi_replace("'","\'",$string);
	} else {
		return $string;
	}
}

//wrapper that always checks our global state
function dbDecode($string){
	global $USE_DBENCODING; //check the session var
	if($USE_DBENCODING){
		return eregi_replace("\'","'",$string);
	} else {
		return $string;
	}
}

?>
