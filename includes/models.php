<?PHP

namespace jbh;

use Exception;

class DatabaseConnector
{
	protected $connection;
	protected $type;

	private $queries = array(
		'listTables' => array(
			'mysql' => 'SHOW FULL tables',
			'sqlite' => 'SELECT * FROM sqlite_schema WHERE type =\'table\' AND name NOT LIKE \'sqlite_%\'',
			'sqlsrv' => 'SELECT DISTINCT TABLE_NAME FROM information_schema.tables'
		),
		'getTableInformation' => array(
			'mysql' => 'DESCRIBE ?',
			'sqlite' => 'PRAGMA table_info(?)',
			'sqlsrv' => 'SELECT * FROM information_schema.columns WHERE TABLE_NAME = ? order by ORDINAL_POSITION'
		),
		'getTableIndexes' => array(
			'mysql' => 'SHOW INDEX FROM ?',
			'sqlite' => 'SELECT * FROM sqlite_master WHERE type = \'index\' AND tbl_name = ?',
			'sqlsrv' => 'SELECT * FROM sys.indexes WHERE object_id = (SELECT object_id FROM sys.objects WHERE name = ?)'
		),
		'getTableCreation' => array(
			'mysql' => 'SHOW CREATE TABLE ?',
			'sqlite' => 'SELECT sql FROM sqlite_schema WHERE name = ?',
			'sqlsrv' => false //Not available without a stored procedure.
		),
		'createTable' => array(
			'mysql' => 'CREATE TABLE IF NOT EXISTS ? ()',
			'sqlite' => 'CREATE TABLE IF NOT EXISTS ? (column_name datatype, column_name datatype);',
			'sqlsrv' => ''
		)
	);

	public function __construct(string $hostPath, int $port, string $db, string $user, string $pass, string $type, string $charset = 'utf8mb4', bool|null $trustCertificate = null)
	{
		$this->type = strtolower(trim($type));
		try
		{
			//Creating DSN string.
			$dsn = $this->type;
			if ($this->type === 'mysql')
				$dsn .= ':host=';
			elseif ($this->type === 'sqlite')
				$dsn .= ':';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ':Server=';

			$dsn .= $hostPath;

			if ($this->type === 'mysql' || $this->type === 'sqlsrv')
				$dsn .= ';port=' . strval($port);

			if ($this->type === 'mysql')
				$dsn .= ';dbname=';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ';Database=';

			$dsn .= $db;

			if ($this->type === 'mysql')
				$dsn .= ';charset=' . $charset;
			if ($this->type === 'sqlsrv' && $trustCertificate !== null)
				$dsn .= ';TrustServerCertificate=' . strval(intval($trustCertificate));

			//Attempting connection.
			$this->connection = new \PDO($dsn, $user, $pass);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
			$this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		}
		catch (\PDOException $e)
		{
			exit($e->getMessage());
		}

		return $this->connection;
	}

	public function executeStatement(string $query, $params = [])
	{
		try
		{
			$stmt = $this->connection->prepare($query);

			if ($stmt === false)
				throw new \Exception('Unable to do prepared statement: ' . $query);

			$stmt->execute($params);
			return $stmt;
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	public function select(string $query, $params = [])
	{
		try
		{
			$stmt = $this->executeStatement($query, $params);
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function update(string $query, $params = [])
	{
		try
		{
			$stmt = $this->executeStatement($query, $params);
			return $stmt->rowCount();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function listTables($includeViews = true)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		if ($includeViews === false && $this->type === 'mysql')
			$query .= ' WHERE Table_Type = \'BASE TABLE\'';
		elseif ($includeViews === false && $this->type === 'sqlsrv')
			$query .= ' WHERE TABLE_TYPE = \'BASE TABLE\'';

		try
		{
			$stmt = $this->executeStatement($query);
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableInformation(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		elseif ($this->type === 'sqlite')
			$query = 'PRAGMA table_info(?)';
		elseif ($this->type === 'sqlsrv')
			$query = 'SELECT * FROM information_schema.columns WHERE TABLE_NAME = ? order by ORDINAL_POSITION';
		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableIndexes(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableCreation(string $table)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($table));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	//$columns is expected to follow the structure below:
	// [
	// 	0 => array(
	// 		'name' => '',
	// 		'type' => '',
	// 		'index' => false,
	// 		'primary' => false,
	// 		'null' => false,
	// 		'default' => '', //Any type.
	// 		'foreign_key' => array()
	// 	),
	// ]
	public function createTable(string $tableName, array $columns)
	{
		$query = $this->queries[__FUNCTION__][$this->type];
		if ($query === false)
			return false;

		try
		{
			$stmt = $this->executeStatement($query, array($tableName,));
			return $stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}

		return false;
	}
}


class RaveTable extends Table
{
	public RaveConnector $connection;
}

class Table
{
	public TableRows $rows;

	public function newTable(TableColumns $columns)
	{
		$this->rows = new TableRows($columns);
	}

	/**
	 * Returns array of rows
	 *
	 * @return Array<row>
	 */
	public function getRows()
	{
		return $this->rows->getRows();
	}

	public function importData(array $data, bool $overwrite = false)
	{
		if ($overwrite === true)
			$this->rows->clearData();
		if ($data === false)
			return false;
		foreach ($data as $row)
		{
			$this->rows->addRow(new Row($row));
		}

		return true;
	}

	public function listColumns(bool $fullyQualifiedName = false)
	{
		return $this->rows->listColumns($fullyQualifiedName);
	}

	public function getColumns()
	{
		return $this->rows->getColumns();
	}

	public function getColumn(string $name)
	{
		return $this->rows->getColumn($name);
	}

	/**
	 * Returns HTML of the inputs.
	 *
	 * @return string
	 */
	public function displayInputs()
	{
		$output = '';
		$columns = $this->getColumns();

		$type = array(
			'bool' => 'select',
			'email' => 'email',
			'int' => 'number',
			'json' => 'text',
			'phone' => 'number',
			'string' => 'text',
		);
		$first = $this->getRows()[0];
		foreach ($first->values as $key => $value)
		{
			//Getting any label styles.
			if (isset($columns[$key]->labelStyles))
			{
				$labelStyles = '';
				foreach ($columns[$key]->labelStyles as $style => $styleValue)
				{
					$labelStyles = $style . ':' . $styleValue . ';';
				}
			}
			//Creating wrapper label.
			$output .= '<label for="' . $key . '"' . (isset($labelStyles) ? ' style="' . $labelStyles . '"' : '') . '>' . ucwords(str_replace('_', ' ', $key));
			//Getting any input styles.
			if (isset($columns[$key]->inputStyles))
			{
				$inputStyles = '';
				foreach ($columns[$key]->inputStyles as $style => $styleValue)
				{
					$inputStyles = $style . ':' . $styleValue . ';';
				}
			}
			//Creating select input.
			if (isset($columns[$key]->inputType) && strtolower($columns[$key]->inputType) === 'select')
			{
				//Select options.
				if (isset($columns[$key]->inputSelectOptions))
				{
					$output .= '<select class="select2" name="' . $key . (isset($columns[$key]->inputSelectOptions['Multiple']) ? '[]' : '') . '"' . (isset($columns[$key]->inputSelectOptions['Multiple']) ? ' multiple=' . $columns[$key]->inputSelectOptions['Multiple'] : '') . (isset($inputStyles) ? ' style="' . $inputStyles . '"' : '') . (isset($columns[$key]->inputSelectOptions['Allow New']) ? ' data-tags=' . $columns[$key]->inputSelectOptions['Allow New'] : '') . ' type="select">';
					foreach ($columns[$key]->inputSelectOptions as $name => $selectOption)
					{
						if ($name === 'Possible Values')
						{
							foreach ($selectOption as $valueName => $possibleValue) //Creating values.
							{
								if (!isset($selectedValue) && isset($columns[$key]->inputSelectOptions['Selected Values']) && sizeof($columns[$key]->inputSelectOptions['Selected Values']) > 0)
									$selectedValues = $columns[$key]->inputSelectOptions['Selected Values'];
								elseif ($columns[$key]->type === 'int')
									$selectedValues[] = intval($value);
								else
									$selectedValues[] = $value;

								$output .=  '<option value="' .  $possibleValue . '" ' . (in_array($possibleValue, $selectedValues) ? 'selected' : '') . '>' . $valueName . '</option>';
							}
						}
					}
				}
				else
					$output .= '<select class="select2" name="' . $key . '" type="select">';
				$output .= '</select>';
			}
			else //Creating standard input.
				$output .= '<input id="' . $key . '" name="' . $key . '"' . (isset($inputStyles) ? ' style="' . $inputStyles . '"' : '') . ' type="' . $type[$columns[$key]->type] . '" value="' .  $value . '" checked>';
			$output .= '</label>';
		}

		return $output;
	}
}


/**
 * Columns pull the most amount of work.
 */
class TableColumns
{
	public array $columns = array();

	public function __construct(Column ...$columns)
	{
		foreach ($columns as $column)
		{
			$this->columns[$column->name] = $column;
		}
	}

	public function addColumn(Column $column)
	{
		$this->columns[$column->name] = $column;
		return true;
	}

	public function listColumns(bool $fullyQualifiedName = false)
	{
		$columnsNames = array();

		foreach ($this->getColumns() as $column)
		{
			$columnsNames[] = $column->getFullColumnName($fullyQualifiedName);
		}

		return $columnsNames;
	}

	/**
	 * Returns array of columns
	 *
	 * @return Array<Column>
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	public function getColumn(string $name)
	{
		if (isset($this->columns[$name]))
			return $this->columns[$name];
		else
			return false;
	}

	public function importData(array $data)
	{
		foreach ($data as $row)
		{
			foreach ($this->getColumns() as $columns)
			{
				$this->columns[$columns->name]->addValue($row, $columns->name);
			}
		}

		return true;
	}
}

/**
 * Rows store the actual data. Each row is made up of X number if columns 
 */
class TableRows
{
	public TableColumns $columns;
	public array $rows = array();

	public function __construct(TableColumns $columns)
	{
		$this->initializateColumns($columns);
	}

	public function addRow(row $row)
	{
		$this->rows[] = $row;
	}

	/**
	 * Returns array of rows
	 *
	 * @return Array<row>
	 */
	public function getRows()
	{
		return $this->rows;
	}

	private function initializateColumns(TableColumns $columns)
	{
		$this->columns = $columns;
	}


	public function listColumns(bool $fullyQualifiedName = false)
	{
		return $this->columns->listColumns($fullyQualifiedName);
	}

	public function getColumns()
	{
		return $this->columns->getColumns();
	}

	public function getColumn(string $name)
	{
		return $this->columns->getColumn($name);
	}

	public function clearData()
	{
		$this->rows = array();
		return true;
	}
}

class Column
{
	public string $name;
	/**
	 * Valid types: `bool` | `date` | `email` | `int` | `json` | `phone` | `string`
	 *
	 * @var string
	 */
	public string $type;
	public string $table;
	/**
	 * Expects an associative array of CSS styles with the key as the property.
	 *
	 * Example: `array('background-color' => 'red');`
	 * 
	 * @var array|null
	 */
	public ?array $labelStyles;
	public ?string $inputType;
	public ?array $inputStyles;
	public array $inputSelectOptions;

	public function __construct(string $columnName, string $type, string $table, array $options = array())
	{
		$this->name = $columnName;
		$this->type = $type;
		$this->table = $table;
		foreach ($options as $key => $option)
		{
			if ($key === 'Input Type')
				$this->inputType = $option;
			elseif ($key === 'Input Styles')
				$this->inputStyles = $option;
			elseif ($key === 'Label Styles')
				$this->labelStyles = $option;
			elseif ($key === 'Select Options')
				$this->inputSelectOptions = $option;
		}
	}

	public function getFullColumnName(bool $fullyQualifiedName = false)
	{
		if ($this->table === '' || $fullyQualifiedName === false)
			return $this->name;
		else
			return $this->table . '.' . $this->name;
	}
}

class Row
{
	public array $values = array();

	/**
	 * Providing an array will push each element of the array onto the variable stack.
	 *
	 * @param mixed $value
	 */
	public function __construct(array $data)
	{
		$this->values = $data;
	}

	public function getValues()
	{
		return $this->values;
	}

	public function addValue(mixed $data)
	{
		$this->values[] = $data;
	}
}

class RaveConnector extends DatabaseConnector
{
	/**
	 * Returns all users.
	 *
	 * @return array|false
	 */
	public function getAllUsersByDepartment(string $department)
	{
		return $this->select('SELECT * FROM users', array($department));
	}

	public function updateUserInformation(RaveTable $table, string $ID, array $data)
	{
		if (filter_var($ID, FILTER_VALIDATE_EMAIL) === false)
			return false;

		$tables = array();

		foreach ($data as $key => $value)
		{
			$column = $table->getColumn($key);
			if ($column === false)
				throw new \Exception('Failed to get column: ' . $key, E_USER_ERROR);
			$tables[$column->table]['columns'][$key] = $column;
		}

		foreach ($tables as $tableName => $table)
		{
			$setList = array();
			foreach ($data as $key => $value)
			{
				if ($table['columns'][$key] === null)
					continue;
				$setList[] = $table['columns'][$key]->getFullColumnName(true) . '= ?';
				$parameters[] = $value;
			}

			if (sizeof($setList) === 0)
				return false;

			$parameters[] = $ID;

			$this->executeStatement('UPDATE ' . $tableName . ' SET ' . implode(', ', $setList) . ' WHERE Unique_LoaderID = ?', $parameters);
		}
	}

	public function getAvailableRoles(string $department)
	{
		$results = $this->select('SELECT DISTINCT `Roles`.Role, `Roles`.ID From `People_Lists` LEFT JOIN `Roles` ON `People_Lists`.Role = `Roles`.ID WHERE department = ?', array($department));
		if ($results === false)
			return false;

		$output = array();
		foreach ($results as $row)
		{
			if (in_array(intval($row['ID']), ADMINISTRATOR_ONLY_ROLES))
				continue;
			$output[$row['Role']] = intval($row['ID']);
		}

		ksort($output); //Sort by key.
		return $output;
	}

	public function getAvailableLists(string $department)
	{
		$results = $this->select('SELECT DISTINCT `Lists`.ListID, `Lists`.ListName From `People_Lists` LEFT JOIN `Lists` ON `People_Lists`.ListID = `Lists`.ListID WHERE department = ?', array($department));
		if ($results === false)
			return false;

		$output = array();
		foreach ($results as $row)
		{
			$output[$row['ListName']] = intval($row['ListID']);
		}

		ksort($output); //Sort by key.
		return $output;
	}

	public function createNewLists(array &$lists)
	{
		$results = $this->select('SELECT `Lists`.ListID, `Lists`.ListName, (SELECT MAX(`Lists`.ListID) FROM `Lists`) as MaxID FROM `Lists`');
		if ($results === false)
			return false;

		$results = rotate($results);
		$newID = $results[2][0] + 1;
		foreach ($lists as &$listID)
		{
			if ((is_numeric($listID) && !in_array($listID, $results[0])) || (!is_numeric($listID) && !in_array($listID, $results[1])))
			{
				$this->executeStatement('INSERT INTO `Lists` (ListID, ListName) VALUES (?, ?)', array($newID, $listID));
				$listID = $newID;
				++$newID;
			}
			else
				$listID = intval($listID);
		}
		return true;
	}

	public function removeUnassignedLists(string $ID, array $listIDs)
	{
		return $this->executeStatement('DELETE FROM `People_Lists` WHERE Unique_LoaderID = ? AND ListID NOT IN(' . implode(', ', $listIDs) . ')', [$ID]);
	}

	public function createNewUserLists(string $ID, array $listIDs, array $currentLists = array())
	{
		foreach ($listIDs as $listID)
		{
			//Removing previously existing lists.
			if (in_array($listID, $currentLists))
				continue;
			else
			{
				$this->executeStatement(
					'INSERT INTO `People_Lists` 
					(Unique_LoaderID,
					Rave_Handle,
					FirstName,
					Last_Name,
					`language`,
					suspended,
					email_1,
					email_2,
					mobile_phone_1,
					mobile_carrier_1,
					mobile_1_voice_preference,
					mobile_phone_2,
					mobile_carrier_2,
					mobile_2_voice_preference,
					mobile_phone_3,
					mobile_carrier_3,
					mobile_3_voice_preference,
					landline_phone_1,
					landline_phone_1_ext,
					landline_phone_2,
					landline_phone_2_ext,
					landline_phone_3,
					landline_phone_3_ext,
					`role`,
					department,
					building,
					class,
					on_campus,
					ListID,
					NotifSMS,
					NotifEmail)
				SELECT
					Unique_LoaderID,
					Rave_Handle,
					FirstName,
					Last_Name,
					`language`,
					suspended,
					email_1,
					email_2,
					mobile_phone_1,
					mobile_carrier_1,
					mobile_1_voice_preference,
					mobile_phone_2,
					mobile_carrier_2,
					mobile_2_voice_preference,
					mobile_phone_3,
					mobile_carrier_3,
					mobile_3_voice_preference,
					landline_phone_1,
					landline_phone_1_ext,
					landline_phone_2,
					landline_phone_2_ext,
					landline_phone_3,
					landline_phone_3_ext,
					`role`,
					department,
					building,
					class,
					on_campus,
					?,
					NotifSMS,
					NotifEmail
				FROM `People_Lists`
				WHERE 
					Unique_LoaderID = ? AND ListID <> ?
				LIMIT 1',
					array($listID, $ID, $listID)
				);
			}
		}
	}

	public function smartloaderExport()
	{
		function removeNullColumns(array &$data)
		{
			function getNullColumns(array $data)
			{
				$nullColumns = array_fill_keys(array_keys($data[0]), null);
				foreach ($data as $row)
				{
					foreach ($row as $key => $value)
					{
						if ($nullColumns[$key] !== null)
							continue;
						elseif ($value !== null && $value !== '' && $value !== '0000-00-00')
							$nullColumns[$key] = true;
					}
				}

				return array_keys($nullColumns, null, true);
			}

			function removeKeys(array &$data, array $keys)
			{
				foreach ($keys as $key)
				{
					unset($data[$key]);
				}
			}

			$nullColumns = getNullColumns($data);
			foreach ($data as &$row)
			{
				removeKeys($row, $nullColumns);
			}

			return $data;
		}


		$results = $this->select('SELECT * FROM RaveUpload');
		if ($results === false)
			return false;
		$results = removeNullColumns($results);

		return $results;
	}

	public function getLists(string $ID)
	{
		if (filter_var($ID, FILTER_VALIDATE_EMAIL) === false)
			return false;
		$results = $this->select('SELECT ListID FROM People_Lists WHERE Unique_LoaderID = ?', array($ID));
		if ($results == false)
			return false;
		else
			return array_unique(flatten($results));
	}
}

class Mailer
{
	public $senderEmail;

	private function __construct(string $senderEmail)
	{
		$this->senderEmail = $senderEmail;
	}

	public function sendMail(array|string $destination, string $subject, string $message, array|string $carbonCopy = '', array|string $blindCarbonCopy = '', array $additionalHeaders = array())
	{
		//Formatting destination.
		if (is_array($destination))
			$destination = implode(',', $destination);
		if (is_array($carbonCopy))
			$carbonCopy = implode(',', $carbonCopy);
		if (is_array($blindCarbonCopy))
			$blindCarbonCopy = implode(',', $blindCarbonCopy);


		$headers['From'] = $this->senderEmail;

		if ($carbonCopy !== '')
			$headers['CC'] = $carbonCopy;
		if ($blindCarbonCopy !== '')
			$headers['BCC'] = $blindCarbonCopy;

		$headers['MIME-Version'] = '1.0';
		$headers['Content-type'] = 'text/html';

		foreach ($additionalHeaders as $name => $header)
		{
			$headers[$name] = $header;
		}
		mail($destination, $subject, $message, $headers);
	}

	// private function checkEmailSentStatus()
	// {
	// }
}

class SFTPConnection
{
	private $connection;
	private $sftp;

	public function __construct(string $host, int $port = 22)
	{
		$this->connection = @ssh2_connect($host, $port);
		if (!$this->connection)
			throw new \Exception("Could not connect to $host on port $port.");
	}

	public function login(string $username, string $password)
	{
		if (!@ssh2_auth_password($this->connection, $username, $password))
			throw new \Exception("Could not authenticate with username $username and password $password.");

		$this->sftp = @ssh2_sftp($this->connection);
		if (!$this->sftp)
			throw new \Exception('Could not initialize SFTP subsystem.');
	}

	public function uploadFile(string $local_file, string $remote_file)
	{
		$stream = @fopen("ssh2.sftp://" . intval($this->sftp) . "/SFTP/inbound/$remote_file", 'w');

		if (!$stream)
			throw new \Exception("Could not open file: $remote_file");

		$data_to_send = @file_get_contents($local_file);
		if ($data_to_send === false)
			throw new \Exception("Could not open local file: $local_file.");

		if (@fwrite($stream, $data_to_send) === false)
			throw new \Exception("Could not send data from file: $local_file.");

		@fclose($stream);
	}


	function scanFilesystem(string $remote_file, bool $recursive = true)
	{
		$dir = "ssh2.sftp://$this->sftp/$remote_file";
		$tempArray = array();
		$handle = opendir($dir);

		//List all the files
		while (false !== ($file = readdir($handle)))
		{
			if (substr($file, 0, 1) === '.')
				continue;

			if ($recursive && is_dir($file))
				$tempArray[$file] = $this->scanFilesystem("$dir/$file");
			else
				$tempArray[] = $file;
		}
		closedir($handle);
		return $tempArray;
	}

	public function receiveFile(string $remote_file, string $local_file)
	{
		$stream = @fopen("ssh2.sftp://$this->sftp/$remote_file", 'r');
		if (!$stream)
			throw new \Exception("Could not open file: $remote_file");
		$contents = fread($stream, filesize("ssh2.sftp://$this->sftp/$remote_file"));
		file_put_contents($local_file, $contents);
		@fclose($stream);
	}

	public function deleteFile(string $remote_file)
	{
		unlink("ssh2.sftp://$this->sftp/$remote_file");
	}
}


class ScopeTimer
{
	public $name;
	public $startTime;

	function __construct($name = 'Timer')
	{
		$this->startTime = microtime(true);
		$this->name = $name;
	}

	function __destruct()
	{
		$elapsed_time = microtime(true) - $this->startTime;
		echo $this->name . ': ' . $elapsed_time . 'ms';
	}

	//$timer = new ScopeTimer(__FILE__);
}
