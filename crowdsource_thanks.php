<?php
# @name: crowdsource_thanks.php
# @version: 0.1
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: Ending page for the submissions process. The user is directed to here from crowdsource_submit.php once they have submitted cataloguing suggestions.
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>SOAS Library crowdsourced cataloguing</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/soas-favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/soas.css">
<!--===============================================================================================-->
</head>
<body>

<?php
require __DIR__ . '/vendor/autoload.php';

// Retrieve configuration variables from the config.env file
$dotenv = Dotenv\Dotenv::create(__DIR__, 'config.env');
$dotenv->load();

// Connect to Google Sheets API
/*
 * We need to get a Google_Client object first to handle auth and api calls, etc.
 */
$client = new \Google_Client();
$client->setApplicationName('crowdsource');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');

/*
 * The JSON auth file can be provided to the Google Client in two ways, one is as a string which is assumed to be the
 * path to the json file. This is a nice way to keep the creds out of the environment.
 */
#$jsonAuth = getenv('JSON_AUTH');
#$client->setAuthConfig(json_decode($jsonAuth, true));
$client->setAuthConfig(__DIR__ . '/crowdsource-ecca04407a4e.json');

/*
 * With the Google_Client we can get a Google_Service_Sheets service object to interact with sheets
 */
$sheets = new \Google_Service_Sheets($client);

// Retrieve the language for the application to work on from the Google Sheets spreadsheet identified in config.env
$spreadsheetId = $_ENV['spreadsheet_id'];
$range = 'config!A3';

#$language = $_ENV['language'];
$language_array = $sheets->spreadsheets_values->get($spreadsheetId, $range);
$language = $language_array['values'][0][0];

?>

	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="logo-div">
					<a href="index.php"><img src="images/soas-logo-transparent.png" alt="SOAS Library" class="logo"></a>
				</div>
				<div class="login100-form p-l-55 p-r-55 p-t-150 p-b-50">
					<!-- The language of the application is determined by a variable set in the Google Sheets spreadsheet identified in config.env -->
					<span class="login100-form-title">
						Help us learn <?php echo $language; ?>
					</span>

					<div class="content100">
						<div class="wrap-content100">
							Thank you for contributing to SOAS Library's cataloguing enhancement project.
						</div>
						
						<!-- Display how many contributions have been received so far by counting how many rows there are in the 'submissions' sheet in the Google Sheets spreadsheet (and subtracting 1 for the header row) -->
						<div class="flex-col-c p-t-50">
							<span class="txt1 p-b-9">
								We have received
<?php
						$range = 'submissions';

						$result = $sheets->spreadsheets_values->get($spreadsheetId, $range);
						$numRows = ($result->getValues() != null ? count($result->getValues()) : 0) - 1;
						printf("%d", $numRows);
?>
								 contributions so far.
							</span>
						</div>
					</div>
				</div>
				<span class="flex-col-c p-b-20">
					<a href="crowdsource_about.php">About the project</a>
				</span>
				<span class="flex-col-c p-b-20">
					<a href="mailto:library.systems@soas.ac.uk">Send feedback</a>
				</span>
				<span class="flex-col-c p-b-40">
					<a href="index.php">Home</a>
				</span>
			</div>
		</div>
	</div>

<!--===============================================================================================-->
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>
<!--===============================================================================================-->

</body>
</html>