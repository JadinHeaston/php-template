<?PHP

use jbh\RaveTable;

function call_API(string $type, string $url, array $parameters = array())
{
	// Initialize a CURL session.
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //Allowing self-signed certificates.

	$query = http_build_query($parameters);

	if ($type === 'GET')
		$url = $url . '?' . $query;
	elseif ($type === 'POST')
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			$query
		);
	}
	else
		return false;

	// Return Page contents.
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	//grab URL and pass it to the variable.
	curl_setopt($ch, CURLOPT_URL, $url);

	var_dump($url);
	return curl_exec($ch);
}

//Fill this function out with the process for updating the row.
function updateInfo(RaveTable $table, string $ID, array $data, array $currentLists)
{
	if (filter_var($ID, FILTER_VALIDATE_EMAIL) === false)
		return false;

	if (isset($data['ListID']) && sizeof($data['ListID']) > 0)
	{
		foreach ($data['ListID'] as $listID)
		{
			if (in_array($listID, $currentLists))
				continue;
			else
				$table->connection->createNewLists($data['ListID']); //Creating any new lists that may not exist.
		}
		$table->connection->removeUnassignedLists($ID, $data['ListID']);
		$table->connection->createNewUserLists($ID, $data['ListID'], $currentLists);
		unset($data['ListID']);
	}

	//Removing unchanged values.
	$originalData = $table->getRows()[0];
	foreach ($originalData->values as $key => $value)
	{
		if (!isset($data[$key]))
			return false;
		else if ($data[$key] === $value)
			unset($data[$key]);
	}

	$table->connection->updateUserInformation($table, $ID, $data);
}

function flatten(array $array)
{
	$return = array();
	array_walk_recursive($array, function ($a) use (&$return)
	{
		$return[] = $a;
	});
	return $return;
}

function rotate(array $array)
{
	array_unshift($array, null);
	$array = call_user_func_array('array_map', $array);
	$array = array_map('array_reverse', $array);
	return $array;
}
