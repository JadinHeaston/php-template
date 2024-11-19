<?php

function isHTMX()
{
	if (function_exists('getallheaders') === false)
		return false;
	$headers = getallheaders();
	return ($headers !== false && isset($headers['Hx-Request']) && boolval($headers['Hx-Request']) === true);
}

/**
 * Computes the hash of provided file paths
 * 
 * Provides 3 keys:
 * - `hash`: Binary hash generated by the file.
 * - `integrity`: Formatted integrity value with base64 encoded hash prepended by the algorithm.
 * - `version`: The last section of the base64 encoded hash (without the trailing equal signs) that is URL encoded for appending to URLs.
 *
 * @param array $filePaths
 * @param string $hash - The `integrity` attribute only supports `sha256`, `sha384`, and `sha512`
 * @return void
 */
function versionedFiles(array $filePaths, int $versionLength = 6, string $hash = 'sha512')
{
	$output = [];
	foreach ($filePaths as $filePath)
	{
		$output[$filePath]['hash'] = hash($hash, file_get_contents($filePath), true); //Computing hash.
		$output[$filePath]['integrity'] = $hash . '-' . base64_encode($output[$filePath]['hash']); //Creating integrity value.
		$output[$filePath]['version'] = urlencode(substr($output[$filePath]['integrity'], -$versionLength - 2, $versionLength)); //Getting last few characters as a version.
	}
	return $output;
}