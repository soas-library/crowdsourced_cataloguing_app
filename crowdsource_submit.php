<?php
# @name: crowdsource_submit.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>
<?php

	// clean up all inputted data
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
	
	// define variables and set to empty values
	$id = $title = $alt_title = $author = $publication = $comment = "";
	
	$id = $_POST["id"];
	$title = test_input($_POST["title"]);
	$alt_title = test_input($_POST["alt_title"]);
	$author = test_input($_POST["author"]);
	$publication = test_input($_POST["publication"]);
	$email = test_input($_POST["email"]);
	$comment = test_input($_POST["comment"]);
	
?>

<?php
require __DIR__ . '/vendor/autoload.php';

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
 *
 * The second option is as an array. For this example I'll pull the JSON from an environment variable, decode it, and
 * pass along.
 */
#$jsonAuth = getenv('JSON_AUTH');
#$client->setAuthConfig(json_decode($jsonAuth, true));
$client->setAuthConfig(__DIR__ . '/crowdsource-ecca04407a4e.json');

/*
 * With the Google_Client we can get a Google_Service_Sheets service object to interact with sheets
 */
$sheets = new \Google_Service_Sheets($client);

$spreadsheetId = '1dM-YM2-qxIy_CJ95SknO02X330j91PP5d3lFlYiCd_Q';
$range = 'Sheet1';
$valueInputOption = 'USER_ENTERED';

$values = [
		[
			$id,
			$title,
			$alt_title,
			$author,
			$publication,
			$email,
			$comment,
			date('c')
		]
];

$body = new \Google_Service_Sheets_ValueRange([
    'values' => $values
]);
$params = [
    'valueInputOption' => $valueInputOption
];
$result = $sheets->spreadsheets_values->append($spreadsheetId, $range, $body, $params);

printf("%d cells appended.", $result->getUpdates()->getUpdatedCells());

?>