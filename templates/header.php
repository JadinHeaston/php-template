<?php
//Create version hashes based on last modified time.
$versionedFiles = versionedFiles(
	[
		__DIR__ . '/../css/styles.css',
		__DIR__ . '/../js/scripts.js',
		__DIR__ . '/../vendor/htmx.min.js',
	]
);


echo <<<HTML
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="UTF-8">
		<title>PAGE TITLE</title>
		<meta name="viewport" content="width=device-width,initial-scale=1">
		<link rel="icon" type="image/svg+xml" href="{$GLOBALS['constants']['APP_ROOT']}assets/favicon.svg">
		<link rel="preload" as="style" href="{$GLOBALS['constants']['APP_ROOT']}css/styles.css?v={$versionedFiles[__DIR__ . '/../css/styles.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../css/styles.css']['integrity']}">
		<link rel="stylesheet" href="{$GLOBALS['constants']['APP_ROOT']}css/styles.css?v={$versionedFiles[__DIR__ . '/../css/styles.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../css/styles.css']['integrity']}">
		<script src="{$GLOBALS['constants']['APP_ROOT']}js/scripts.js.js?v={$versionedFiles[__DIR__ . '/../js/scripts.js']['version']}" type="module" integrity="{$versionedFiles[__DIR__ . '/../js/scripts.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/htmx.min.js?v={$versionedFiles[__DIR__ . '/../vendor/htmx.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/htmx.min.js']['integrity']}"></script>
	</head>

	<body>

		<header>
			Header Content!
	HTML;

require_once(__DIR__ . '/nav.php');

echo <<<HTML
	</header>
	HTML;
