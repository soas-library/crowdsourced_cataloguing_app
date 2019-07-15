<?php
# @name: crowdsource_edit.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
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

	#Retrieve configuration variables from the config.env file
	$dotenv = Dotenv\Dotenv::create(__DIR__, 'config.env');
	$dotenv->load();

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

	$spreadsheetId = $_ENV['spreadsheet_id'];
	$range = 'config!A3';

	#$language = $_ENV['language'];
	$language_array = $sheets->spreadsheets_values->get($spreadsheetId, $range);
	$language = $language_array['values'][0][0];

	// clean up all inputted data
    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

	if (isset($_POST) && !empty($_POST)) {	
		$bib_id = test_input($_POST["id"]);
	}
	else {
		$solrurl = $_ENV['solr_hostname'] . '/solr/bib/select?fl=controlfield_001&fq=DocType:bibliographic&fq=Language_search:' . $language . '&indent=on&q=*&rows=5000&wt=xml';
	
		# Perform Curl request on the Solr API
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $solrurl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		$response = curl_exec($ch);
		curl_close($ch);
	
		# Turn the API response into useful XML
		$xml = new SimpleXMLElement($response); 

		$num_of_records = intval($xml->result['numFound']);
		
		$random = rand(0,$num_of_records);
	
		$bib_id = $xml->result->doc[$random]->arr->str;
	}
	
	$baseurl = $_ENV['docstore_hostname'] . '/oledocstore/documentrest';
	$retrieve_bib = '/bib/doc?bibId=';
	
	# Perform Curl request on the OLE API
	$ch = curl_init();
	$queryParams = $bib_id;
	curl_setopt($ch, CURLOPT_URL, $baseurl . $retrieve_bib . $queryParams);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	$response = curl_exec($ch);
	curl_close($ch);
	
	# Turn the API response into useful XML
	$xml = new SimpleXMLElement($response); 
	
	$content = $xml->content;
	$content = new SimpleXMLElement($content);
	
	foreach($content->getDocNamespaces() as $strPrefix => $strNamespace) {
		if(strlen($strPrefix)==0) {
			$strPrefix="a"; //Assign an arbitrary namespace prefix.
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
				<form class="login100-form validate-form p-l-55 p-r-55 p-t-150" action="crowdsource_submit.php" method="POST">
					<span class="login100-form-title">
						Help us learn <?php echo $language; ?>
					</span>

<?php
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
						<div class="wrap-header100">
							<strong>Title</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
				
							foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'245')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<div class="wrap-input100 m-b-16" data-validate="Title">
							<input class="input100" type="text" name="title_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
					
<?php
				
					if ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Alternative title</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
				
							foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'246')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<div class="wrap-input100 m-b-16" data-validate="Alternative title">
							<input class="input100" type="text" name="alt_title_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

<?php
				
					if ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Main author</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
				
							foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'100')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<div class="wrap-input100 m-b-16" data-validate="Author">
							<input class="input100" type="text" name="author_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

<?php
				
					if ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']")):
?>
					
					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Publication details</strong>
						</div>
						<div class="wrap-content100 p-t-05 p-b-10">
<?php
				
							foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
				
							echo '<br/>';
				
							foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'260')]/a:subfield[@code!='6']|///a:datafield[@tag='880'][contains(.,'264')]/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}

?>
						</div>
					
						<div class="wrap-input100 m-b-16" data-validate="Publication details">
							<input class="input100" type="text" name="publication_submission" placeholder="Enter your suggestion here">
							<span class="focus-input100"></span>
						</div>
					</div>
<?php
					endif;
?>

					<div class="content100 p-t-60">
						<div class="wrap-header100">
							<strong>Email address (required)</strong>
						</div>
						<div class="wrap-input100 validate-input m-b-16" data-validate="Email address">
							<input class="input100" type="text" name="email" placeholder="Enter your email address here">
							<span class="focus-input100"></span>
						</div>
					</div>

					<div class="content100 p-t-15">
						<div class="wrap-header100">
							<strong>Comment</strong>
						</div>
						<div class="wrap-input100 m-b-16" data-validate="Comments">
							<textarea class='textarea100' type="text" name="comment" ></textarea>
							<span class="focus-input100"></span>
						</div>
					</div>

<!-- Send hidden inputs such as original Marc fields -->

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
			
					<input type="hidden" name="recaptcha_response" id="recaptchaResponse">
 
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
					<a href="mailto:libenquiry@soas.ac.uk">Send feedback</a>
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