<?php
# @name: crowdsource_about.php
# @version: 0.1
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: 'About' page which is configurable by the library through editing the Google Sheets spreadsheet identified in the config.env file
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
					<!-- The language of the application is determined by a variable set in the Google Sheets spreadsheet identified in config.env -->
					<span class="title-content100">
						Help us learn <?php echo $language; ?>
					</span>
					<span class="subtitle-content100">
						You can help us make <?php echo $language; ?> books easier to find by submitting titles and authors in the original script
					</span>
				</div>
				<div class="login100-form p-l-55 p-r-55 p-t-50 p-b-50">
					<!-- Retrieve copy for the 'about' page from the 'config' sheet of the Google Sheets spreadsheet. This allows the library to easily write their own copy and make changes without involving I&T staff -->
					<div class="content100">
						<div class="wrap-content100">
<?php
							$range = 'config!A7';

							$result = $sheets->spreadsheets_values->get($spreadsheetId, $range);
							
							echo $result['values'][0][0];
?>
						</div>
						<div class="wrap-content100 p-t-30">
							Developed by SOAS I&T Directorate. Source code is © SOAS, University of London and is licensed under an MIT License: the source code is available at <a href="https://github.com/soas-library/crowdsourced_cataloguing_app">https://github.com/soas-library/crowdsourced_cataloguing_app</a>.
						</div>
					</div>
				</div>
				<span class="flex-col-c p-b-20">
					<a href="crowdsource_feedback.php">Send feedback</a>
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