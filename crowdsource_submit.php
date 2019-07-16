<?php
# @name: crowdsource_submit.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: Submit data from crowdsource_edit.php to a Google Sheets spreadsheet and then direct user to crowdsource_thanks.php
?>

<?php
require __DIR__ . '/vendor/autoload.php';

// Retrieve configuration variables from the config.env file
$dotenv = Dotenv\Dotenv::create(__DIR__, 'config.env');
$dotenv->load();

// This function 'cleans up' inputted data by removing extraneous whitespaces or special characters
function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

// Verify the reCAPTCHA response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recaptcha_response'])){
	
	// Build POST request to verify reCAPTCHA response via Google's API
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = $_ENV['recaptcha_secret'];
    $recaptcha_response = $_POST['recaptcha_response'];

    // Send and decode POST request
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned
    if ($recaptcha->score >= 0.5) {
        // Verified
		
		// Define variables and set to empty values
		$id = $title_submission = $alt_title_submission = $author_submission = $publication_submission = $title_original = $title_vernacular = $alt_title_original = $alt_title_vernacular = $author_original = $author_vernacular = $publication_original = $publication_vernacular = $email = $comment = "";
	
		// Set variables to values from form POST
		$id = $_POST["id"];
		$title_submission = test_input($_POST["title_submission"]);
		$alt_title_submission = test_input($_POST["alt_title_submission"]);
		$author_submission = test_input($_POST["author_submission"]);
		$publication_submission = test_input($_POST["publication_submission"]);
		$title_original = test_input($_POST["title_original"]);
		$title_vernacular = test_input($_POST["title_vernacular"]);
		$alt_title_original = test_input($_POST["alt_title_original"]);
		$alt_title_vernacular = test_input($_POST["alt_title_vernacular"]);
		$author_original = test_input($_POST["author_original"]);
		$author_vernacular = test_input($_POST["author_vernacular"]);
		$publication_original = test_input($_POST["publication_original"]);
		$publication_vernacular = test_input($_POST["publication_vernacular"]);
		$email = test_input($_POST["email"]);
		$comment = test_input($_POST["comment"]);

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

		// Submit data to Google Sheets spreadsheet
		$spreadsheetId = $_ENV['spreadsheet_id'];
		$range = 'submissions';
		$valueInputOption = 'USER_ENTERED';

		$values = [
				[
					date('c'),
					$id,
					$title_original,
					$title_vernacular,
					$title_submission,
					$alt_title_original,
					$alt_title_vernacular,
					$alt_title_submission,
					$author_original,
					$author_vernacular,
					$author_submission,
					$publication_original,
					$publication_vernacular,
					$publication_submission,
					$email,
					$comment
				]
		];

		$body = new \Google_Service_Sheets_ValueRange([
			'values' => $values
		]);
		$params = [
			'valueInputOption' => $valueInputOption
		];
		$result = $sheets->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

		// Redirect user to crowdsource_thanks.php. This prevents them from refreshing the submit page to make multiple requests.
		header('Location: crowdsource_thanks.php');
		
    } else {
        // Not verified
		// Redirect user to crowdsource_error.php to display error message
		header('Location: crowdsource_error.php');
    }
}

?>
