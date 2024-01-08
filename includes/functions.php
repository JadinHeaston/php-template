<?PHP

use jbh\RaveTable;

/**
 * Converts a number of seconds into a human readable format.
 *
 * @param integer $seconds
 * @return string
 */
function secondsToHumanTime(int $seconds)
{
	if ($seconds >= 86400)
		$format[] = '%a day' . ($seconds > 86400 * 2 ? 's' : '');
	if ($seconds >= 3600)
		$format[] = '%h hour' . ($seconds > 3600 * 2 ? 's' : '');
	if ($seconds >= 60)
		$format[] = '%i minute' . ($seconds > 60 * 2 ? 's' : '');
	$format[] = '%s ' . ($seconds !== 1 ? 'seconds' : 'second');

	$dateHandle = new DateTime('@0');
	return str_replace(' 1 seconds', ' 1 second', $dateHandle->diff(new DateTime("@$seconds"))->format(implode(', ', $format)));
}

function callAPI(string $type, string $url, array $parameters = array())
{
	$type = strtoupper($type);
	if ($type === 'GET')
		$url = $url . '?' . http_build_query($parameters);

	$curlHandle = curl_init($url);

	if ($type === 'POST')
	{
		curl_setopt($curlHandle, CURLOPT_POST, 1);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($parameters));
	}

	curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
	curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curlHandle);
	curl_close($curlHandle);
	return $response;
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
