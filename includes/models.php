<?PHP

class DatabaseConnector
{
	protected \PDO $connection;
	protected $type;
	public \PDOStatement $stmt; //\PDOStatement

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
			$this->connection = new PDO($dsn, $user, $pass);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
		{
			exit($e->getMessage());
		}

		return $this->connection;
	}

	public function executeStatement($query = '', $params = [])
	{
		try
		{
			$this->stmt = $this->connection->prepare($query);

			if ($this->stmt === false)
				throw new Exception('Unable to do prepared statement: ' . $query);

			$this->stmt->execute($params);
			return $this->stmt;
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	public function select($query = '', $params = [])
	{
		try
		{
			$this->stmt = $this->executeStatement($query, $params);
			return $this->stmt->fetchAll();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
		return false;
	}

	public function update($query = '', $params = [])
	{
		try
		{
			$this->stmt = $this->executeStatement($query, $params);
			return $this->stmt->rowCount();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
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
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
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
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
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
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
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
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}
		return false;
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
