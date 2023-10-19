<?PHP

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

function rotate(array $array)
{
	array_unshift($array, null);
	$array = call_user_func_array('array_map', $array);
	$array = array_map('array_reverse', $array);
	return $array;
}