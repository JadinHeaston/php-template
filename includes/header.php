<?PHP
//Create version hashes based on last modified time.
$versionedFiles = array(
	'js/scripts.js' => '',
	'css/styles.css' => '',
);

foreach ($versionedFiles as $fileName => $hash)
{
	$versionedFiles[$fileName] = substr(md5(filemtime($fileName)), 0, 6);
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>RAVE</title>
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" type="image/svg+xml" href="favicon.svg">
	<link rel="preload" as="style" href="css/styles.css?v=<?PHP echo $versionedFiles['css/styles.css']; ?>">
	<link rel="stylesheet" href="css/styles.css?v=<?PHP echo $versionedFiles['css/styles.css']; ?>">
	<script src="https://code.jquery.com/jquery-3.7.0.slim.min.js" integrity="sha256-tG5mcZUtJsZvyKAxYLVXrmjKBVLd6VpVccqz/r4ypFE=" crossorigin="anonymous"></script>
	<!-- <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script> -->
	<!-- <script src="https://unpkg.com/react-table@5.5.3/react-table.js"></script> -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script src="js/scripts.js?v=<?PHP echo $versionedFiles['js/scripts.js']; ?>" type="module"></script>
</head>

<body>