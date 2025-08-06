<?php
//Create version hashes based on last modified time.
$versionedFiles = versionedFiles(
	[
		__DIR__ . '/../css/styles.css',
		__DIR__ . '/../vendor/select2/select2.min.css',
		__DIR__ . '/../js/scripts.js',
		__DIR__ . '/../vendor/htmx.min.js',
		__DIR__ . '/../vendor/jquery/jquery.slim.min.js',
		__DIR__ . '/../vendor/select2/select2.min.js',
	]
);

$jsDiv = '';
if (REQUIRE_JAVASCRIPT === true)
	$jsDiv = <<<HTML
		<div id="no-js">JavaScript is required for this site to function properly.</div>
		HTML;

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
		<link rel="stylesheet" href="{$GLOBALS['constants']['APP_ROOT']}vendor/select2/select2.min.css?v={$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.css']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.css']['integrity']}">
		<script src="{$GLOBALS['constants']['APP_ROOT']}js/scripts.js?v={$versionedFiles[__DIR__ . '/../js/scripts.js']['version']}" type="module" integrity="{$versionedFiles[__DIR__ . '/../js/scripts.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/htmx.min.js?v={$versionedFiles[__DIR__ . '/../vendor/htmx.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/htmx.min.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/jquery/jquery.slim.min.js?v={$versionedFiles[__DIR__ . '/../vendor/jquery/jquery.slim.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/jquery/jquery.slim.min.js']['integrity']}"></script>
		<script src="{$GLOBALS['constants']['APP_ROOT']}vendor/select2/select2.min.js?v={$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.js']['version']}" integrity="{$versionedFiles[__DIR__ . '/../vendor/select2/select2.min.js']['integrity']}"></script>
		
		<style>
			#no-js {
				display: none !important;
			}
		</style>
		<noscript>
			<style>
				#no-js {
					display: revert !important;
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					background: white;
					z-index: 1000;
					font-size: 1.5rem;
					font-weight: bold;
					text-align: center;
					color: black;
				}
			</style>
		</noscript>
	</head>

	<body>
		{$jsDiv}
		
		<header>
			Header Content!
	HTML;

require_once(__DIR__ . '/nav.php');

echo <<<HTML
	</header>
	HTML;
