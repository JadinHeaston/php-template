<?PHP

use jbh\SFTPConnection;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('php-adfs/include/include.php');

if (session_status() !== PHP_SESSION_ACTIVE)
	session_start();

//Check authentication!
if (verifyLogin() === false)
	adfs_action('signin'); //Send to ADFS, if not.

require_once('includes/loader.php');
// $timer = new jbh\ScopeTimer();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="smartloader.csv"');
$table = new jbh\RaveTable;
$table->connection = new jbh\RaveConnector(DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_TYPE, DB_CHARSET, DB_TRUST_CERT);

$output = $table->connection->smartloaderExport();
$outputStream = fopen('php://output', 'w');
fputcsv($outputStream, array_keys($output[0])); //Adding headers.
foreach ($output as $fields)
{
	fputcsv($outputStream, $fields);
}

// $sftp = new SFTPConnection(RAVE_SFTP_HOST, RAVE_SFTP_PORT);
// $sftp->login(RAVE_SFTP_USERNAME);
