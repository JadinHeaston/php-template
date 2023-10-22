<?PHP

namespace jbh;

class DatabaseConnector
{
	protected \PDO $connection;
	protected $type;
	public \PDOStatement $stmt;

	public function __construct(string $host, int $port, string $db, string $user, string $pass, string $type, string $charset = 'utf8mb4', bool|NULL $trustCertificate = NULL)
	{
		$this->type = strtolower(trim($type));
		try
		{
			//Creating DSN string.
			$dsn = $this->type;
			if ($this->type === 'mysql')
				$dsn .= ':host=';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ':Server=';

			$dsn .= $host;

			if ($this->type === 'mysql')
				$dsn .= ';port=' . strval($port);

			if ($this->type === 'mysql')
				$dsn .= ';dbname=';
			elseif ($this->type === 'sqlsrv')
				$dsn .= ';Database=';

			$dsn .= $db;

			if ($this->type === 'mysql')
				$dsn .= ';charset=' . $charset;
			if ($this->type === 'sqlsrv' && $trustCertificate !== NULL)
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
			$this->stmt = $this->connection->prepare($query);

			if ($this->stmt === false)
				throw new \Exception('Unable to do prepared statement: ' . $query);

			$this->stmt->execute($params);
			return $this->stmt;
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
			$this->stmt = $this->executeStatement($query, $params);
			return $this->stmt->fetchAll();
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
			$this->stmt = $this->executeStatement($query, $params);
			return $this->stmt->rowCount();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function listTables($includeViews = true)
	{
		if ($this->type === 'mysql')
			$query = 'SHOW FULL tables';
		elseif ($this->type === 'sqlsrv')
			$query = 'SELECT DISTINCT TABLE_NAME FROM information_schema.tables';

		if ($includeViews === false && $this->type === 'mysql')
			$query .= ' WHERE Table_Type = \'BASE TABLE\'';
		elseif ($includeViews === false && $this->type === 'sqlsrv')
			$query .= ' WHERE TABLE_TYPE = \'BASE TABLE\'';

		try
		{
			$this->stmt = $this->executeStatement($query);
			return $this->stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getLastInsertID(): string
	{
		return $this->connection->lastInsertId();
	}

	public function getTableInformation(string $table)
	{
		if ($this->type === 'mysql')
			$query = 'DESCRIBE ?';
		elseif ($this->type === 'sqlsrv')
			$query = 'SELECT * FROM information_schema.columns WHERE TABLE_NAME = ? order by ORDINAL_POSITION';
		try
		{
			$this->stmt = $this->executeStatement($query, array($table));
			return $this->stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableIndexes(string $table)
	{
		if ($this->type === 'mysql')
			$query = 'SHOW INDEX FROM ?';
		elseif ($this->type === 'sqlsrv')
			$query = 'SELECT * FROM sys.indexes WHERE object_id = (SELECT object_id FROM sys.objects WHERE name = ?)';

		try
		{
			$this->stmt = $this->executeStatement($query, array($table));
			return $this->stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	public function getTableCreation(string $table)
	{
		if ($this->type === 'mysql')
			$query = 'SHOW CREATE TABLE ?';
		elseif ($this->type === 'sqlsrv')
			return false; //Not available without a stored procedure.

		try
		{
			$this->stmt = $this->executeStatement($query, array($table));
			return $this->stmt->fetchAll();
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
		return false;
	}

	/**
	 * Returns the final output query string that is internally constructed by the PDO.
	 *
	 * @param string $string
	 * @param array $array
	 * @return string
	 */
	public function debugBuildQuery(string $string, $array = [])
	{
		//Get the key lengths for each of the array elements.
		$keys = array_map('strlen', array_keys($array));

		//Sort the array by string length so the longest strings are replaced first.
		array_multisort($keys, SORT_DESC, $array);

		foreach ($array as $k => $v)
		{
			//Quote non-numeric values.
			$replacement = is_numeric($v) ? $v : "'{$v}'";

			//Replace the needle.
			$string = str_replace($k, $replacement, $string);
		}

		return $string;
	}

	/**
	 * Initiates a transaction
	 *
	 * Pass-through method: `PDO::beginTransaction()`
	 * @return bool TRUE on success or FALSE on failure.
	 * @throws PDOException If there is already a transaction started or the driver does not support transactions Note: An exception is raised even when the PDO::ATTR_ERRMODE attribute is not PDO::ERRMODE_EXCEPTION.
	 */
	public function beginTransaction()
	{
		return $this->connection->beginTransaction();
	}

	/**
	 * Commits a transaction
	 *
	 * Pass-through method: `PDO::commit()`
	 * @return bool TRUE on success or FALSE on failure.
	 * @throws PDOException if there is no active transaction.
	 */
	public function commit()
	{
		return $this->connection->commit();
	}

	/**
	 * Checks if inside a transaction
	 *
	 * Pass-through method: `PDO::inTransaction()`
	 * @return bool TRUE if a transaction is currently active, and FALSE if not.
	 */
	public function inTransaction()
	{
		return $this->connection->inTransaction();
	}

	/**
	 * Rolls back a transaction
	 *
	 * Pass-through method: `PDO::rollBack()`
	 * @return bool TRUE on success or FALSE on failure.
	 * @throws PDOException if there is no active transaction.
	 */
	public function rollBack()
	{
		return $this->connection->rollBack();
	}
}

class CIS extends DatabaseConnector
{
	/**
	 * Returns all users.
	 *
	 * @return array|false
	 */
	public function getAllUsers(): array|false
	{
		return $this->select('SELECT * FROM users');
	}

	/**
	 * Sets a users active status based on their employee ID.
	 *
	 * @param int employeeID
	 * @param boolean $status
	 * @return int|false Count of changed rows.
	 */
	public function setUserActiveState(int $employeeID, int $status): int|false
	{
		return $this->update('UPDATE users SET Active = ? WHERE EMPID = ?', [$status, $employeeID]);
	}

	/**
	 * Retrieves all information for a given user based on their employee ID.
	 *
	 * @param integer $employeeID
	 * @return array|false
	 */
	public function getUserInformation(int $employeeID): array|false
	{
		return $this->select('SELECT * FROM users WHERE EMPID = ?', [$employeeID]);
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

	public function listColumns(bool $fullyQualifiedName = false, bool $includeSpecialColumns = true)
	{
		return $this->rows->listColumns($fullyQualifiedName, $includeSpecialColumns);
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

	public function listColumns(bool $fullyQualifiedName = false, bool $includeSpecialColumns = true)
	{
		$columnsNames = array();

		foreach ($this->getColumns() as $column)
		{
			$columnsNames[] = (isset($column->specialColumn) && $column->specialColumn !== null && $includeSpecialColumns ? $column->specialColumn . ' as ' . $column->getFullColumnName(false) : $column->getFullColumnName($fullyQualifiedName));
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

	/**
	 * Undocumented function
	 *
	 * @param string $columnName
	 * @return Column
	 */
	public function getColumn(string $columnName)
	{
		if (isset($this->columns[$columnName]))
			return $this->columns[$columnName];
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


	public function listColumns(bool $fullyQualifiedName = false, bool $includeSpecialColumns = true)
	{
		return $this->columns->listColumns($fullyQualifiedName, $includeSpecialColumns);
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

/**
 * Contains information for creating an HTML input.
 */
class Column
{
	public string $name;
	/**
	 * Valid types: bool | email | int | json | phone | string
	 *
	 * @var string
	 */
	public string $type;
	public string $table;
	public ?array $labelStyles;
	public ?string $inputType;
	public ?array $inputStyles;
	public array $inputSelectOptions;
	public ?string $specialColumn;

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
			elseif ($key === 'Special Column')
				$this->specialColumn = $option;
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

/**
 * Simple row class.
 */
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
