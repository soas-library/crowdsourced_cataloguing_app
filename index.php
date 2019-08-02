<?php
# @name: index.php
# @version: 0.2
# @creation_date: 2019-05-23
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: This is the homepage of the application. It presents two choices for users: search for a specific bib record or pick a random bib record.
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
				<!-- This form submits search parameters and redirects to crowdsource_search.php -->
				<form class="login100-form validate-form p-l-55 p-r-55 p-t-175" action="crowdsource_search.php" method="POST">
					<!-- The language of the application is determined by a variable set in the Google Sheets spreadsheet identified in config.env -->
					<span class="login100-form-title">
						Help us learn <?php echo $language; ?>
					</span>

					<div class="wrap-input100 validate-input m-b-16" data-validate="Search for a book">
						<input class="input100" type="text" name="search" placeholder="Search for a book">
						<span class="focus-input100"></span>
					</div>

					<div class="container-login100-form-btn">
						<button class="login100-form-btn">
							Search
						</button>
					</div>
				</form>
				
				<div class="wrap-input100 p-b-30 p-l-55 p-r-55">
					<div class="flex-col-c p-t-50 p-b-20">
						<span class="txt3">
							OR
						</span>
					</div>
					
					<!-- Link to crowdsource_edit.php. Without search parameters, crowdsource_edit will display a random book -->
					<div class="container-login100-form-btn">
						<a href="crowdsource_edit.php" class="button">
							pick a random book
						</a>
					</div>
				</div>
				
				<!-- Display how many contributions have been received so far by counting how many rows there are in the 'submissions' sheet in the Google Sheets spreadsheet (and subtracting 1 for the header row) -->
				<div class="flex-col-c p-t-40 p-b-20">
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
				<span class="flex-col-c p-b-20">
					<a href="crowdsource_about.php">About the project</a>
				</span>
				<span class="flex-col-c p-b-40">
					<a href="mailto:library.systems@soas.ac.uk">Send feedback</a>
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