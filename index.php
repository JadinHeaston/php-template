<?PHP

use jbh\ScopeTimer;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/php-adfs/include/include.php');

if (session_status() !== PHP_SESSION_ACTIVE)
	session_start();

adfsActionListener();

//Check authentication!
if (verifyLogin() === false)
	adfs_action('signin'); //Send to ADFS, if not.

require_once('includes/loader.php');
$timer = new ScopeTimer();
$table = new jbh\RaveTable;
$table->newTable(
	new jbh\TableColumns(
		new jbh\Column('Unique_LoaderID', 'string', 'Rave_People'),
		new jbh\Column('Rave_Handle', 'string', 'People_Lists'),
		new jbh\Column('FirstName', 'string', 'People_Lists'),
		new jbh\Column('Last_Name', 'string', 'People_Lists'),
		new jbh\Column('email_1', 'string', 'People_Lists'),
		new jbh\Column('mobile_phone_1', 'int', 'People_Lists'),
		new jbh\Column('Role', 'int', 'Roles')
	)
);
$table->connection = new jbh\RaveConnector(DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_TYPE, DB_CHARSET, DB_TRUST_CERT);

if (!isset($_SESSION['Department']))
{
	$_SESSION['Department'] = callAPI('GET', API_ROOT . '/division/', ['code' => 2320]);
	// $_SESSION['Department'] = callAPI('GET', API_ROOT . '/division/', array('code' => intval($_SESSION['AdfsUserDetails']->attributes['DivisionID'][0])));
	if ($_SESSION['Department'] === false)
		exit(1);
	else
		$_SESSION['Department'] = json_decode($_SESSION['Department'])->department_name;
}

if ($table->importData($table->connection->select('SELECT ' . implode(', ', $table->listColumns(true)) . ' FROM People_Lists LEFT JOIN Rave_People ON People_Lists.Unique_LoaderID = Rave_People.Unique_LoaderID LEFT JOIN Roles ON People_Lists.Role = Roles.ID WHERE Department = ? GROUP BY People_Lists.Unique_LoaderID', array($_SESSION['Department']))) === false)
	die();

require_once(__DIR__ . '/includes/header.php');
?>
<header>
	<h1 class="no-text-select">RAVE</h1>
	<h2>User List</h2>
</header>
<main>
	<?PHP

	if (sizeof($table->getRows()) !== 0)
	{
	?>
		<table>
			<thead>
				<tr>
					<?PHP
					foreach ($table->listColumns() as $key)
					{
						if ($key === 'Unique_LoaderID')
							continue;
						echo '<th>' . ucwords(str_replace('_', ' ', $key)) . '</th>';
					}
					?>
					<th>
						Actions
					</th>
				</tr>
			</thead>
			<tbody>
				<?PHP
				foreach ($table->getRows() as $user)
				{
					echo '<tr>';
					foreach ($user->values as $key => $value)
					{
						if ($key === 'Unique_LoaderID')
							continue;
						echo '<td>' . $value . '</td>';
					}
					echo '<td><a href="row_edit.php?id=' . $user->values['Unique_LoaderID'] . '">Edit</a><!-- |  Disable/Enable --></td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
	<?PHP
	}
	else
		echo 'Your department has no RAVE Alerts users. If this is an error please contact <a href="mailto:webmaster@como.gov">webmaster</a>';
	?>
	<!-- Preventing POST resubmission -->
	<script>
		if (window.history.replaceState)
			window.history.replaceState(null, null, window.location.href);
	</script>
	<?PHP
	require_once(__DIR__ . '/includes/footer.php');
