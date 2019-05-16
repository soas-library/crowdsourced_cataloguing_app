<?php
# @name: crowdsource_submit.php
# @version: 0.1
# @creation_date: 2019-03-08
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>

<?php
/*
 * BEFORE RUNNING:
 * ---------------
 * 1. If not already done, enable the Google Sheets API
 *    and check the quota for your project at
 *    https://console.developers.google.com/apis/api/sheets
 * 2. Install the PHP client library with Composer. Check installation
 *    instructions at https://github.com/google/google-api-php-client.
 */

/* // Autoload Composer.
require_once __DIR__ . '/vendor/autoload.php';

$client = getClient();
$client->setApplicationName("crowdsource");
$client->setDeveloperKey("AIzaSyAidxzZS8FUCqe8Tlg5tARaLTzvASOV1i8");

$service = new Google_Service_Sheets($client);

// The spreadsheet to request.
$spreadsheetId = '1-8b1wgeEiL29NTtvXLPYFdOOs-DV89VcKiskNuY8DMs';  // TODO: Update placeholder value.

$response = $service->spreadsheets->get($spreadsheetId);

// TODO: Change code below to process the `response` object:
echo '<pre>', var_export($response, true), '</pre>', "\n";
echo 'cheese';

function getClient() {
  // TODO: Change placeholder below to generate authentication credentials. See
  // https://developers.google.com/sheets/quickstart/php#step_3_set_up_the_sample
  //
  // Authorize using one of the following scopes:
  //   'https://www.googleapis.com/auth/drive'
  //   'https://www.googleapis.com/auth/drive.file'
  //   'https://www.googleapis.com/auth/drive.readonly'
  //   'https://www.googleapis.com/auth/spreadsheets'
  //   'https://www.googleapis.com/auth/spreadsheets.readonly'
  return null;
} */

/* 	$spreadsheetId = '1B4dEHAatCoLOQ-VTx1eTpMRfFlAsRItKgQ4A3tv0_90';
	$apikey = 'AIzaSyAidxzZS8FUCqe8Tlg5tARaLTzvASOV1i8';
	$url = 'https://sheets.googleapis.com' . '/v4/spreadsheets/' . $spreadsheetId . '/values/Sheet1!A1:C1';
	#$queryParams = '?' . 'includeGridData=true' . '&' . 'key' . '=' . $apikey;
	$queryParams = '?' . 'key' . '=' . $apikey;
    $fullurl = $url . $queryParams;
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($ch);
    curl_close($ch);
	
	echo $fullurl;
	echo $response;  */


?>

<?
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

/*
 * To read data from a sheet we need the spreadsheet ID and the range of data we want to retrieve.
 * Range is defined using A1 notation, see https://developers.google.com/sheets/api/guides/concepts#a1_notation
 */
$data = [];

// The first row contains the column titles, so lets start pulling data from row 2
$currentRow = 2;

// The range of A2:H will get columns A through H and all rows starting from row 2
#$spreadsheetId = getenv('SPREADSHEET_ID');
$spreadsheetId = '1dM-YM2-qxIy_CJ95SknO02X330j91PP5d3lFlYiCd_Q';
$range = 'A2:H';
$rows = $sheets->spreadsheets_values->get($spreadsheetId, $range, ['majorDimension' => 'ROWS']);
if (isset($rows['values'])) {
    foreach ($rows['values'] as $row) {
        /*
         * If first column is empty, consider it an empty row and skip (this is just for example)
         */
        if (empty($row[0])) {
            break;
        }

        $data[] = [
            'col-a' => $row[0],
            'col-b' => $row[1],
            'col-c' => $row[2],
            'col-d' => $row[3],
            'col-e' => $row[4],
            'col-f' => $row[5],
            'col-g' => $row[6],
            'col-h' => $row[7],
        ];

        /*
         * Now for each row we've seen, lets update the I column with the current date
         */
        $updateRange = 'B'.$currentRow;
        $updateBody = new \Google_Service_Sheets_ValueRange([
            'range' => $updateRange,
            'majorDimension' => 'ROWS',
            'values' => ['values' => date('c')],
        ]);
        $sheets->spreadsheets_values->update(
            $spreadsheetId,
            $updateRange,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );

        $currentRow++;
    }
}

print_r($data);
/* Output:
Array
(
    [0] => Array
        (
            [col-a] => 123
            [col-b] => test
            [col-c] => user
            [col-d] => test user
            [col-e] => usertest
            [col-f] => email@domain.com
            [col-g] => yes
            [col-h] => no
        )

    [1] => Array
        (
            [col-a] => 1234
            [col-b] => another
            [col-c] => user
            [col-d] =>
            [col-e] => another
            [col-f] => another@eom.com
            [col-g] => no
            [col-h] => yes
        )

)
 */
?>

<?
/* 	// clean up all inputted data
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
	
	// define variables and set to empty values
	$title = $alt_title = $author = $publication = $comment = $marcxml = "";
	
	$title = test_input($_POST["title"]);
	$alt_title = test_input($_POST["alt_title"]);
	$author = test_input($_POST["author"]);
	$publication = test_input($_POST["publication"]);
	$comment = test_input($_POST["comment"]);
	$id = $_POST["id"];
	
	echo $title;
	echo $alt_title;
	echo $author;
	echo $publication;
	echo $comment;
	echo $id;
	
	$baseurl = 'https://james.lis.soas.ac.uk:8443/oledocstore/documentrest/';
	$retrieve_bib = '/bib/doc?bibId=';
	$bib_id = $_POST["id"];
	
	// perform Curl request on the OLE API
	$ch = curl_init();
	$queryParams = trim($bib_id);
	curl_setopt($ch, CURLOPT_URL, $baseurl . $retrieve_bib . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	curl_close($ch);
	
 	// turn the API response into useful XML
	$xml = new SimpleXMLElement($response); 
	
	#header('Content-Type: application/xml; charset=utf-8');
	header('Content-Type:text/plain');
	
	$content = $xml->content;
	$content = simplexml_load_string($content);

	echo $content->asXML();
	
	// foreach ($content->record->datafield as $datafield) {
		// if ((string) $datafield['tag'] == '245') {
			// foreach ($datafield->subfield as $subfield) {
				// echo (string) $subfield . " ";
			// }
		// }
	// }

	$content = strval($content);
	
	var_dump($content);
	
	$title_pattern = '/<datafield tag="245".+?<\/datafield>/s';
	$title_replacement = '<datafield tag="245" ind1="1" ind2="0">\r\n<subfield>' . $title . '</subfield>\r\n</datafield>';
	$content = preg_replace($title_pattern, $title_replacement, $content);
	
	echo $content->asXML();
	
	$client = new Google_Client();
	$client->setApplicationName("crowdsource");
	$client->setDeveloperKey("AIzaSyAidxzZS8FUCqe8Tlg5tARaLTzvASOV1i8");
	
	$service = new Google_Service_Sheets($client);
	
	$spreadsheetId = '1-8b1wgeEiL29NTtvXLPYFdOOs-DV89VcKiskNuY8DMs';
	$url = 'https://sheets.googleapis.com' . '/v4/spreadsheets/' . $spreadsheetId;
    $fullurl = $url;
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($ch);
    curl_close($ch);
	}
	
	echo $response; */
  
?>