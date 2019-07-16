<?php
# @name: crowdsource_search.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: This page retrieves and displays search results for bibliographic records by querying OLE's Apache Solr API. It then directs the user to an edit page for the record they select.
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
				<div class="login100-form p-l-55 p-r-55 p-t-150">
					<!-- The language of the application is determined by a variable set in the Google Sheets spreadsheet identified in config.env -->
					<span class="login100-form-title">
						Help us learn <?php echo $language; ?>
					</span>

					<div class="content100">
<?php

	// Retrieve the search parameter inputted by the user on index.php
	$search = urlencode($_POST["search"]);

	// Assemble a query string to send to Solr. This uses the Solr hostname from config.env. Solr's query syntax can be found at many sites including https://lucene.apache.org/solr/guide/6_6/the-standard-query-parser.html
	// This query retrieves only the bib identifier field for records which satisfy the search query
	$solrurl = $_ENV['solr_hostname'] . '/solr/bib/select?fl=bibIdentifier&fq=DocType:bibliographic&fq=Language_search:' . $language . '&indent=on&q=Title_search:' . $search . '%20OR%20Author_search:' . $search . '%20OR%20Publisher_search:' . $search . '%20OR%20PublicationDate_search:' . $search . '%20OR%20PublicationPlace_search:' . $search . '%20OR%20LocalId_display:' . $search . '%20OR%20ItemBarcode_search:' . $search . '%20OR%20ISBN_search:' . $search . '&rows=5000&wt=xml';

	// Perform Curl request on the Solr API
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $solrurl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	curl_close($ch);
	
	// Turn the API response into useful XML
	$xml = new SimpleXMLElement($response); 
	
	// If no results are found, display a message
	if ($xml->result->attributes()->numFound == '0'){
?>
					<div class="wrap-result100">
						<div class="wrap-header100">
							<div class="wrap-content100 p-t-05 p-b-50">
							No results found
							</div>
						</div>
					</div>
<?php
	}
	// Otherwise, for each result found, retrieve full Marc bibliographic records from the OLE Docstore API and display relevant fields
	else {
		foreach ($xml->result->doc as $result){
			foreach ($result->arr->str as $id){
				
				// Remove the wbm- prefix from the bib identifier
				$bib_id = ltrim($id, "wbm-");
				// Assemble a URL for the Docstore API. This uses the OLE Docstore hostname from config.ev
				$baseurl = $_ENV['docstore_hostname'] . '/oledocstore/documentrest/';
				$retrieve_bib = '/bib/doc?bibId=';
	
				// Perform Curl request on the OLE Docstore API
				$ch = curl_init();
				$queryParams = $bib_id;
				curl_setopt($ch, CURLOPT_URL, $baseurl . $retrieve_bib . $queryParams);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
				$response = curl_exec($ch);
				curl_close($ch);
	
				// Turn the API response into useful XML
				$xml = new SimpleXMLElement($response); 
	
				// XML from the Docstore contains embedded MarcXML in the content field. Take this field and turn it into useful XML
				$content = $xml->content;
				$content = new SimpleXMLElement($content);
	
				// XML namespaces are improperly set in MarcXML so we have to assign a namespace in order to use xpath to perform advanced XML retrieval below
				foreach($content->getDocNamespaces() as $strPrefix => $strNamespace) {
					if(strlen($strPrefix)==0) {
						// Assign an arbitrary namespace prefix
						$strPrefix="a";
					}
					$content->registerXPathNamespace($strPrefix,$strNamespace);
				}		
?>			
				<div class="wrap-result100">
					<!-- Each result displays the title from the bib record -->
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Title:</strong>
<?php
							// Display each subfield (that is not a $6 subfield) for each 245 field
							foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>

				<!-- If there is an alternative title field, display it -->
<?php
				if ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Alternative title:</strong>
<?php
							// Display each subfield (that is not a $6 subfield) for each 246 field
							foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>

				<!-- If there is a main author field, display it -->
<?php
				if ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Main author:</strong>
<?php
							// Display each subfield (that is not a $6 subfield) for each 100 field
							foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>

				<!-- If there is a publication details field, display it. Note that publication details may be in either the 260 or the 264 field -->
<?php
				if ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Publication details:</strong>
<?php
							// Display each subfield (that is not a $6 subfield) for each 260 field or 264 field
							foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>

			<!-- This form sends the 001 bib identifier to crowdsource_edit.php as a hidden parameter. We will use it in crowdsource_edit to retrieve bib details from the Marc record via the OLE Docstore API -->
			<form class="p-l-55 p-r-55 p-b-75" action="crowdsource_edit.php" method="POST">

				<input type="hidden" value="
			<?php 
				// Create a hidden input value for each 001 field (a record should only ever have one 001 field
				foreach ($content->xpath("///a:controlfield[@tag='001']") as $controlfield) {
					echo (string) $controlfield;
				}
			?>
				" name="id" />
			
				<div class="container-login100-form-btn">
					<button class="login100-form-btn">
						Edit
					</button>
				</div>
			</form>
		</div>
<?php
			}
		}
	}
?>
					<span class="flex-col-c p-b-20">
						<a href="crowdsource_about.php">About the project</a>
					</span>
					<span class="flex-col-c p-b-20">
						<a href="mailto:libenquiry@soas.ac.uk">Send feedback</a>
					</span>
					<span class="flex-col-c p-b-40">
						<a href="index.php">Home</a>
					</span>
				</div>
			</div>
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