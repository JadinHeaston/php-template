<?PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('php-adfs/include/include.php');

if (session_status() !== PHP_SESSION_ACTIVE)
	session_start();

//Check authentication!
if (verifyLogin() === false)
	adfs_action('signin'); //Send to ADFS, if not.

if (!isset($_GET['id']) || $_GET['id'] === '')
{
	header('Location: /');
	exit(1);
}
else
	$personID = strtolower(filter_var($_GET['id'], FILTER_SANITIZE_EMAIL));

require_once('includes/loader.php');

if (!isset($_SESSION['Department']))
{
	$_SESSION['Department'] = callAPI('GET', 'https://phpapps.edge.gocolumbiamo.com/api/employee-directory/v1/divisions/', array('code' => 2320)); //intval($_SESSION['AdfsUserDetails']->attributes['DivisionID'][0])
	if ($_SESSION['Department'] === false)
		exit(1);
	else
		$_SESSION['Department'] = json_decode($_SESSION['Department'])->department_name;
}

$table = new jbh\RaveTable;
$table->connection = new jbh\RaveConnector(DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_TYPE, DB_CHARSET, DB_TRUST_CERT);
$currentLists = $table->connection->getLists($_GET['id']);

$table->newTable(
	new jbh\TableColumns(
		new jbh\Column('Unique_LoaderID', 'string', 'Rave_People'),
		new jbh\Column('Rave_Handle', 'string', 'People_Lists'),
		new jbh\Column('FirstName', 'string', 'People_Lists'),
		new jbh\Column('Last_Name', 'string', 'People_Lists'),
		new jbh\Column('role', 'int', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => $table->connection->getAvailableRoles($_SESSION['Department'])))),
		new jbh\Column('suspended', 'bool', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => array('No' => 'FALSE', 'Yes' => 'TRUE')))),
		new jbh\Column('email_1', 'string', 'People_Lists'),
		new jbh\Column('email_2', 'string', 'People_Lists'),
		new jbh\Column('mobile_phone_1', 'phone', 'People_Lists'),
		new jbh\Column('mobile_carrier_1', 'string', 'People_Lists'),
		new jbh\Column('mobile_1_voice_preference', 'bool', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => array('UNSET' => '', 'No' => 'FALSE', 'Yes' => 'TRUE')))),
		new jbh\Column('mobile_phone_2', 'phone', 'People_Lists'),
		new jbh\Column('mobile_carrier_2', 'string', 'People_Lists'),
		new jbh\Column('mobile_2_voice_preference', 'bool', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => array('UNSET' => '', 'No' => 'FALSE', 'Yes' => 'TRUE')))),
		new jbh\Column('mobile_phone_3', 'phone', 'People_Lists'),
		new jbh\Column('mobile_carrier_3', 'string', 'People_Lists'),
		new jbh\Column('mobile_3_voice_preference', 'bool', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => array('UNSET' => '', 'No' => 'FALSE', 'Yes' => 'TRUE')))),
		new jbh\Column('landline_phone_1', 'phone', 'People_Lists'),
		new jbh\Column('landline_phone_1_ext', 'int', 'People_Lists'),
		new jbh\Column('landline_phone_2', 'phone', 'People_Lists'),
		new jbh\Column('landline_phone_2_ext', 'int', 'People_Lists'),
		new jbh\Column('landline_phone_3', 'phone', 'People_Lists'),
		new jbh\Column('landline_phone_3_ext', 'int', 'People_Lists'),
		new jbh\Column('building', 'string', 'People_Lists'),
		new jbh\Column('class', 'string', 'People_Lists'),
		new jbh\Column('on_campus', 'bool', 'People_Lists', array('Input Type' => 'select', 'Select Options' => array('Possible Values' => array('UNSET' => '', 'Off' => 'OFF', 'On' => 'ON')))),
		// new jbh\Column('ListID', 'int', 'People_Lists', array('Input Styles' => array('width' => '90%'), 'Label Styles' => array('grid-column' => 'span 2'), 'Input Type' => 'select', 'Select Options' => array('Selected Values' => $currentLists, 'Possible Values' => $table->connection->getAvailableLists($_SESSION['Department']), 'Multiple' => true, 'Allow New' => true))),
	)
);

if ($table->importData($table->connection->select('SELECT ' . implode(', ', $table->listColumns(true)) . ' FROM People_Lists LEFT JOIN Rave_People ON People_Lists.Unique_LoaderID = Rave_People.Unique_LoaderID LEFT JOIN Lists ON Lists.ListID = People_Lists.ListID WHERE People_Lists.Unique_LoaderID = ? GROUP BY People_Lists.Unique_LoaderID', array($personID))) === false)
	die();

if (isset($_POST['row_edit_form']))
{
	unset($_POST['row_edit_form']);
	updateInfo($table, $_GET['id'], $_POST, $currentLists);
	if ($table->importData($table->connection->select('SELECT ' . implode(', ', $table->listColumns(true)) . ' FROM People_Lists LEFT JOIN Rave_People ON People_Lists.Unique_LoaderID = Rave_People.Unique_LoaderID LEFT JOIN Lists ON Lists.ListID = People_Lists.ListID WHERE People_Lists.Unique_LoaderID = ? GROUP BY People_Lists.Unique_LoaderID', array($personID)), true) === false)
		die();
}

if (sizeof($table->getRows()) === 0)
{
	header('Location: ?id=' . $_POST['Unique_LoaderID']);
	exit();
}
else
	$data = $table->getRows()[0];

require_once('includes/header.php');
?>

<header>
	<a href="index.php">home</a>
	<h1 class="no-text-select">RAVE</h1>
	<h2 class="no-text-select"><?PHP echo $data->values['FirstName'] . ' ' . $data->values['Last_Name'] ?></h2>
</header>
<main>

	<form id="row-edit" method="post">
		<?PHP
		echo $table->displayInputs();
		?>
		<input type="submit" name="row_edit_form" value="submit">
	</form>

	<?PHP
	require_once('includes/footer.php');
	?>

	<!-- Preventing POST resubmission -->
	<script>
		if (window.history.replaceState)
			window.history.replaceState(null, null, window.location.href);
	</script>