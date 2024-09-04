<?PHP

/**
 * Converts a number of seconds into a human readable format.
 *
 * @param integer $seconds
 * @return string
 */
function secondsToHumanTime(int $seconds): string
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


function rotate(array $array): array
{
	array_unshift($array, null);
	$array = call_user_func_array('array_map', $array);
	$array = array_map('array_reverse', $array);
	return $array;
}

function flattenSingleArrays(array $array): array
{
	foreach ($array as $key => &$value)
	{
		if (!is_array($value))
			continue;
		elseif (count($value) === 1)
			$array[$key] = $value[0];
		elseif (count($value) > 1)
			$array[$key] = flattenSingleArrays($value);
	}
	return $array;
}

function flatten(array $array): array
{
	$return = array();
	array_walk_recursive($array, function ($a) use (&$return)
	{
		$return[] = $a;
	});
	return $return;
}

/**
 * Caches a function result to a $GLOBALS['cache'] array.
 * This is useful for queries that are run frequently in a single script and often request the same data.  
 * The cache is stored in a global array and is keyed by the function and the parameters passed to the function.  
 * It's important to note that the cache is only valid for the current script and changed data will not be reflected.
 *
 * @param callable $function
 * @param mixed ...$params
 * @return mixed
 */
function cachedFunction(callable $function, mixed ...$params): mixed
{
	if (!isset($GLOBALS['cache'][$function][implode('', $params)]))
		$GLOBALS['cache'][$function][implode('', $params)] = call_user_func($function, ...$params);

	return $GLOBALS['cache'][$function][implode('', $params)];
}

function readCSVFile(string $filePath, int $length = null, string $separator = ',', string $enclosure = '"', string $escape = '\\'): \Generator | false
{
	$header = null;
	$file = fopen($filePath, 'r');
	if ($file === false)
		return false;

	while (($row = fgetcsv($file, $length, $separator, $enclosure, $escape)) !== false)
	{
		if ($header === null) //First row is the header
			$header = $row;
		else //Subsequent rows are data
		{
			$rowData = array_combine($header, $row);
			yield $rowData;
		}
	}

	fclose($file);
}
