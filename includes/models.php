<?php

class DatabaseConnector
{
	protected \PDO $connection;
	protected string $type;

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

	public function __construct(string $type, string $hostPath, ?int $port = null, string $db = '', string $user = '', string $pass = '', string $charset = 'utf8mb4', ?bool $trustCertificate = null)
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

			if ($this->type === 'mysql')
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

	public function executeStatement($query = '', $params = [], $skipPrepare = false)
	{
		try
		{
			if ($skipPrepare !== true)
			{
				$stmt = $this->connection->prepare($query);

				if ($stmt === false)
					throw new \Exception('Unable to do prepared statement: ' . $query);

				$stmt->execute($params);
				return $stmt;
			}
			else
				return $this->connection->exec($query);
		}
		catch (\Exception $e)
		{
			throw new \Exception($e->getMessage());
		}
	}

	public function select($query = '', $params = [])
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

	public function update($query = '', $params = [])
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

	public function getLastInsertID(): string
	{
		return $this->connection->lastInsertId();
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

	public function importCSVtoSQLite(string $csvPath, string $delimiter = ',', ?string $tableName = null, ?array $fields = null): array
	{
		if (($csv_handle = fopen($csvPath, 'r')) === FALSE)
			throw new Exception('Failed to open CSV file.');

		if ($tableName === null)
			$tableName = preg_replace('/[^a-zA-Z0-9_]/i', '', basename(str_replace(' ', '_', $csvPath), '.csv'));

		if ($fields === null)
		{
			$fields = array_map(function ($field)
			{
				return strtolower(preg_replace('/[^a-zA-Z0-9_]/i', '', str_replace(' ', '_', $field)));
			}, fgetcsv($csv_handle, 0, $delimiter));

			$fields = renameRepeatingValues($fields);
		}

		fclose($csv_handle);

		$create_fields_str = join(', ', array_map(function ($field)
		{
			return '`' . $field . '` TEXT NULL';
		}, $fields));

		$this->connection->beginTransaction();

		$create_table_sql = "CREATE TABLE IF NOT EXISTS {$tableName} ({$create_fields_str})";
		$this->connection->exec($create_table_sql);

		$insert_fields_str = join(', ', $fields);
		if ($insert_fields_str === '')
		{
			$this->connection->rollBack();
			return [];
		}
		$insert_values_str = join(', ', array_fill(0, count($fields),  '?'));
		$insertSQL = "INSERT INTO `{$tableName}` ({$insert_fields_str}) VALUES ({$insert_values_str})";
		$insert_sth = $this->connection->prepare($insertSQL);

		$inserted_rows = 0;
		foreach (readCSVFile($csvPath, null, $delimiter) as $csvRow)
		{
			if ($csvRow === false)
				break;

			// var_dump($fields, $insertSQL, $csvRow);

			$insert_sth->execute(array_values($csvRow));
			++$inserted_rows;
		}

		$this->connection->commit();

		return array(
			'table_name' => $tableName,
			'fields' => $fields,
			'insert' => $insert_sth,
			'inserted_rows' => $inserted_rows
		);
	}
}

class Mailer
{
	public function __construct(public string $senderEmail)
	{
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

class LDAPWrapper
{
	private $ldapServer;
	private $ldapUsername;
	protected $ldapPassword;
	private $ldapConnection;

	public array $applicationGroups = array();

	public function __construct($server = LDAP_SERVER, $username = LDAP_USERNAME, $password = LDAP_PASSWORD)
	{
		$this->ldapServer = $server;
		$this->ldapUsername = $username;
		$this->ldapPassword = $password;
		$this->connect();
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	private function connect()
	{
		$this->ldapConnection = ldap_connect($this->ldapServer);
		if (!$this->ldapConnection)
			throw new Exception('Failed to connect to LDAP server');

		ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);

		$bindResult = ldap_bind($this->ldapConnection, $this->ldapUsername, $this->ldapPassword);
		if (!$bindResult)
			throw new Exception('Failed to bind to LDAP server');
	}

	public function search(string $filter, array $attributes = [], string $baseDn = LDAP_BASE_DN)
	{
		if (!$this->ldapConnection)
			throw new Exception('LDAP connection not established. Call connect() first.');

		$searchResult = ldap_search($this->ldapConnection, $baseDn, $filter, $attributes);
		if (!$searchResult)
			throw new Exception('LDAP search failed');

		$entries = ldap_get_entries($this->ldapConnection, $searchResult);
		return $entries;
	}

	private function add(array $attributes = [], string $dn = LDAP_BASE_DN)
	{
		if (!$this->ldapConnection)
			throw new Exception('LDAP connection not established. Call connect() first.');

		$result = ldap_mod_add($this->ldapConnection, $dn, $attributes);
		if (!$result)
			throw new Exception('LDAP group addition failed');

		return $result;
	}

	private function remove(array $attributes = [], string $dn = LDAP_BASE_DN)
	{
		if (!$this->ldapConnection)
		{
			throw new Exception('LDAP connection not established. Call connect() first.');
		}

		$result = ldap_mod_del($this->ldapConnection, $dn, $attributes);
		if (!$result)
		{
			throw new Exception('LDAP group removal failed');
		}

		return $result;
	}

	public function add_member_to_group(string $networkID, string $groupDN)
	{
		$member = $this->get_member($networkID);
		if ($member === false)
			return false;
		elseif ($this->group_exists($groupDN) === false) //Group not found.
			return false;
		elseif ($this->is_member_in_group($networkID, $groupDN) === true) //Member in group.
			return true;

		$attributes = array(
			'member' => $member['Distinguished Name']
		);

		$this->add($attributes, $groupDN);

		return true;
	}

	public function remove_member_from_group(string $networkID, string $groupDN)
	{
		$member = $this->get_member($networkID);
		if ($member === false)
			return false;
		elseif ($this->group_exists($groupDN) === false) //Group not found.
			return false;
		elseif ($this->is_member_in_group($networkID, $groupDN) === false) //Member not in group.
			return true;

		$attributes = array(
			'member' => $member['Distinguished Name']
		);

		$this->remove($attributes, $groupDN);

		return true;
	}

	private function group_exists(string $groupDN)
	{
		//Verify the group is found.
		$groupEntry = $this->search('(distinguishedName=' . $groupDN . ')');
		if ($groupEntry['count'] === 1)
			return true;
		else
			return false;
	}

	public function is_member_in_group(string $networkID, string $groupDN)
	{
		//Verify the group is found.
		$members = $this->search('(&(objectCategory=person)(objectClass=user)(sAMAccountName=' . $networkID . ')(memberOf=' . $groupDN . '))');

		if ($members['count'] > 0)
			return true;
		else
			return false;
	}

	public function parse_system_role(array $groups, string $system)
	{
		$match = array();
		foreach ($groups as $group)
		{
			if (preg_match('/CN=(.*),.*' . $system  . '.*/u', $group, $match))
				return $match[1];
		}

		return false;
	}


	public function update_attribute(string $dn, array $entry)
	{
		if (!$this->ldapConnection)
			throw new Exception('LDAP connection not established. Call connect() first.');

		$result = ldap_mod_replace_ext($this->ldapConnection, $dn, $entry);
		if (!$result)
			throw new Exception('LDAP attribute replacement failed');

		$entries = ldap_get_entries($this->ldapConnection, $result);
		return $entries;
	}

	public function get_member(string $networkID)
	{
		$attributes = array('cn', 'givenname', 'initials', 'Employee', 'info', 'memberof', 'department', 'employeenumber', 'badpwdcount', 'samaccountname', 'sn', 'mail', 'mobile', 'lockouttime', 'whencreated', 'whenchanged');
		$value = $this->search('(&(objectCategory=person)(objectClass=user)(sAMAccountName=' . $networkID . '))', $attributes);
		$this->recursive_unset_key($value, 'count');
		foreach ($attributes as $attribute)
		{
			$this->recursive_unset_value($value, $attribute);
			if (isset($value[$attribute]) && is_array($value[$attribute]) && count($value[$attribute]) === 1)
				$value[$attribute] = $value[$attribute][0];
		}
		$value = $this->recursive_change_key($value, array('cn' => 'Common Name', 'department' => 'Division Code', 'displayname' => 'Full Name', 'dn' => 'Distinguished Name', 'employeenumber' => 'Employee ID', 'givenname' => 'First Name', 'initials' => 'Initials', 'mail' => 'Email', 'member' => 'Members', 'memberof' => 'Membership', 'mobile' => 'Mobile Phone', 'samaccountname' => 'Network ID', 'sn' => 'Last Name'));
		$this->recursive_flatten_single_entry_array($value);
		if (is_array($value) && count($value) > 0)
			return $value[0];
		else
			return false;
	}


	private function recursive_flatten_single_entry_array(array &$array)
	{
		foreach ($array as &$value)
		{
			if (!is_array($value) || count($value) === 0)
				continue;
			elseif (count($value) > 1)
				$this->recursive_flatten_single_entry_array($value);
			else
				$value = $value[0];
		}

		return $array;
	}

	public function get_application_members()
	{
		$attributes = array('member');
		$result = $this->search('(&(objectclass=group)(cn=SecTrack_*))', $attributes);
		$this->recursive_unset_key($result, 'count');
		foreach ($attributes as $attribute)
		{
			$this->recursive_unset_value($result, $attribute);
		}
		$this->get_member_information($result);
		$result = $this->recursive_change_key($result, array('department' => 'Division Code', 'displayname' => 'Full Name', 'dn' => 'Distinguished Name', 'employeenumber' => 'Employee ID',  'mail' => 'Email', 'member' => 'Members', 'memberof' => 'Membership', 'mobile' => 'Mobile Phone', 'samaccountname' => 'Network ID'));
		set_role_group_as_key($result);
		return $result;
	}

	private function get_member_information(array &$array)
	{
		function set_role_group_as_key(array &$array)
		{
			foreach ($array as $key => $value)
			{
				if (!isset($value['dn']))
					continue;

				$array[ldap_explode_dn($value['dn'], 1)[0]] = (isset($value['Members']) ? $value['Members'] : array());
				unset($array[$key]);
			}
		}

		foreach ($array as &$entry)
		{
			if (!isset($entry['member']))
				continue;

			foreach ($entry['member'] as &$value)
			{
				$value = $this->get_member($value);
				$this->recursive_flatten_single_entry_array($value);
			}
		}
	}

	public function assign_user_to_group(string $networkID, string $group)
	{
	}

	private function recursive_unset_key(array &$array, mixed $unwantedKey)
	{
		unset($array[$unwantedKey]);
		foreach ($array as &$value)
		{
			if (is_array($value))
				$this->recursive_unset_key($value, $unwantedKey);
		}
	}

	private function recursive_unset_value(array &$array, mixed $unwantedValue)
	{
		foreach ($array as $key => &$value)
		{
			if (is_array($value))
				$this->recursive_unset_value($value, $unwantedValue);
			else if ($value === $unwantedValue)
				unset($array[$key]);
		}
	}

	//$arr => original array
	//$set => array containing old keys as keys and new keys as values
	private function recursive_change_key(array $arr, array $set)
	{
		if (is_array($arr) && is_array($set))
		{
			$newArr = array();
			foreach ($arr as $k => $v)
			{
				$key = array_key_exists($k, $set) ? $set[$k] : $k;
				$newArr[$key] = is_array($v) ? $this->recursive_change_key($v, $set) : $v;
			}
			return $newArr;
		}
		return $arr;
	}

	// getAllDisabledUsers()
	// {
	// 	// !(userAccountControl:1.2.840.113556.1.4.803:=2)
	// }

	private function disconnect()
	{
		if ($this->ldapConnection)
		{
			ldap_unbind($this->ldapConnection);
			$this->ldapConnection = null;
		}
	}
}

class ScopeTimer
{
	public string $name;
	public string|float $startTime;
	public bool $showOnDestruct;

	public function __construct(string $name = 'Timer', bool $showOnDestruct = true)
	{
		$this->startTime = microtime(true);
		$this->name = $name;
		$this->showOnDestruct = $showOnDestruct;
	}

	public function __destruct()
	{
		if ($this->showOnDestruct === false)
			return;

		echo $this->getDisplayTime();
	}

	public function getDisplayTime(): string
	{
		return $this->name . ': ' . $this->getElapsedTime() . ' Sec';
	}

	public function getElapsedTime(): string|float
	{
		return microtime(true) - $this->startTime;
	}

	//$timer = new ScopeTimer(__FILE__);
}
