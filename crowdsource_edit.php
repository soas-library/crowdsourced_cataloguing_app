<?php
# @name: crowdsource_edit.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: This is the editing page to submit suggestions for edits to bibliographic records. If the user has come from the search page, it displays the bib record that they selected from crowdsource_search.php. If the user has come from index.php by asking for a random book, it displays a random bib record. 
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
	<!-- This page uses a Google reCAPTCHA to ensure security for the submission form -->
	<script src="https://www.google.com/recaptcha/api.js?render=6LcP5qkUAAAAAOoixJM9kyWR2lLtug2FclBt1ueo"></script>
    <script>
        grecaptcha.ready(function () {
            grecaptcha.execute('6LcP5qkUAAAAAOoixJM9kyWR2lLtug2FclBt1ueo', { action: 'edit' }).then(function (token) {
                var recaptchaResponse = document.getElementById('recaptchaResponse');
                recaptchaResponse.value = token;
            });
        });
    </script>
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

	// This function 'cleans up' inputted data by removing extraneous whitespaces or special characters
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

	// If the user comes from crowdsource_search.php, that page's form will POST a bib ID. In that case, the ID for the edit record comes from the POST.
	if (isset($_POST) && !empty($_POST)) {	
		$bib_id = test_input($_POST["id"]);
	}
	// Otherwise, retrieve a random bib record
	else {
		// Assemble a query string to send to Solr. This uses the Solr hostname from config.env. Solr's query syntax can be found at many sites including https://lucene.apache.org/solr/guide/6_6/the-standard-query-parser.html
		// This query retrieves a list of 001 bib identifier fields based on the language set in Google Sheets
		$solrurl = $_ENV['solr_hostname'] . '/solr/bib/select?fl=controlfield_001&fq=DocType:bibliographic&fq=Language_search:' . $language . '&indent=on&q=*&rows=5000&wt=xml';
	
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

		// Check how many results are found
		$num_of_records = intval($xml->result['numFound']);
		
		// Generate a random number between 0 and the number of records retrieved
		$random = rand(0,$num_of_records);

		// Retrieve a bib ID based on that random number
		$bib_id = $xml->result->doc[$random]->arr->str;
	}
	
	// Use the bib ID to retrieve full Marc bibliographic records from the OLE Docstore API and display relevant fields
	$baseurl = $_ENV['docstore_hostname'] . '/oledocstore/documentrest';
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
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="logo-div">
					<a href="index.php"><img src="images/soas-logo-transparent.png" alt="SOAS Library" class="logo"></a>
				</div>
				<!-- This form submits suggested edits to Marc records to crowdsource_submit.php -->
				<form class="login100-form validate-form p-l-55 p-r-55 p-t-150" action="crowdsource_submit.php" method="POST">
					<!-- The language of the application is determined by a variable set in the Google Sheets spreadsheet identified in config.env -->
					<span class="login100-form-title">
						Help us learn <?php echo $language; ?>
					</span>

<?php
					// If we've retrieved a random record rather than a searched-for record, display a button to retrieve a different record
					if (empty($_POST)):
?>
						<div class="container-login100-form-btn p-b-50">
							<a href="crowdsource_edit.php" class="button">
								pick another book
							</a>
						</div>
<?php
					endif;
?>					

					<div class="content100">
						<!-- Display the title from the bib record -->
						<div class="wrap-header100">
							<strong>Title</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
							// Display each subfield (that is not a $6 subfield) for each 245 field
							foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							// Display each subfield (that is not a $6 subfield) for each 880 field linked to a 245 field
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'245')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<!-- Users enter title suggestions here -->
						<div class="wrap-input100 m-b-16" data-validate="Title">
							<input class="input100" type="text" name="title_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
					
					<!-- If there is an alternative title field, display it -->
<?php
					if ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Alternative title</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
							// Display each subfield (that is not a $6 subfield) for each 246 field
							foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							// Display each subfield (that is not a $6 subfield) for each 880 field linked to a 246 field
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'246')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
						
						<!-- Users enter alternative title suggestions here -->
						<div class="wrap-input100 m-b-16" data-validate="Alternative title">
							<input class="input100" type="text" name="alt_title_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

					<!-- If there is a main author field, display it -->
<?php
					if ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Main author</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
							// Display each subfield (that is not a $6 subfield) for each 100 field
							foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							// Display each subfield (that is not a $6 subfield) for each 880 field linked to a 100 field
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'100')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<!-- Users enter main author suggestions here -->
						<div class="wrap-input100 m-b-16" data-validate="Author">
							<input class="input100" type="text" name="author_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

					<!-- If there is a publication details field, display it. Note that publication details may be in either the 260 or the 264 field -->
<?php
					if ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Publication details</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
							// Display each subfield (that is not a $6 subfield) for each 260 field or 264 field
							foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							// Display each subfield (that is not a $6 subfield) for each 880 field linked to a 260 field or 264 field
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'260')]/a:subfield[@code!='6']|///a:datafield[@tag='880'][contains(.,'264')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
						
						<!-- Users enter publication details suggestions here -->
						<div class="wrap-input100 m-b-16" data-validate="Publication details">
							<input class="input100" type="text" name="publication_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

					<!-- Users enter email address here -->
					<div class="content100 p-t-60">
						<div class="wrap-header100">
							<strong>Email address (required)</strong>
						</div>
						<div class="wrap-input100 validate-input m-b-16" data-validate="Email address">
							<input class="input100" type="text" name="email" placeholder="Enter your email address here">
							<span class="focus-input100"></span>
						</div>
					</div>

					<!-- Users enter comments here -->
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Comment</strong>
						</div>
						<div class="wrap-input100 m-b-16" data-validate="Comments">
							<textarea class='textarea100' type="text" name="comment" ></textarea>
							<span class="focus-input100"></span>
						</div>
					</div>

					<!-- We need to send Marc fields to the Google Sheets spreadsheet too. Assemble hidden input values from Marc fields -->
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:controlfield[@tag='001']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="id" />
			
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="title_original" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'245')]/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="title_vernacular" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="alt_title_original" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'246')]/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="alt_title_vernacular" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="author_original" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'100')]/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="author_vernacular" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="publication_original" />
					
					<input type="hidden" value="
<?php 
						foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'260')]/a:subfield[@code!='6']|///a:datafield[@tag='880'][contains(.,'264')]/a:subfield[@code!='6']") as $controlfield) {
							echo (string) $controlfield;
						}
?>
					" name="publication_vernacular" />
			
					<!-- Hidden input value for reCAPTCHA response -->
					<input type="hidden" name="recaptcha_response" id="recaptchaResponse">
 
					<!-- This form submits suggested edits to Marc records to crowdsource_submit.php -->
					<div class="container-login100-form-btn p-b-50">
						<button class="login100-form-btn">
							Submit
						</button>
					</div>
					
				</form>
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