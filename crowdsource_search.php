<?php
# @name: crowdsource_search.php
# @version: 0.2
# @creation_date: 2019-05-29
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>SOAS Library Bengali cataloguing</title>
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

	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form p-l-55 p-r-55 p-t-150">
					<span class="login100-form-title">
						Help us learn Bengali
					</span>

					<div class="content100">
<?php
	$search = urlencode($_POST["search"]);

	$solrurl = 'http://james.lis.soas.ac.uk:8983/solr/bib/select?fl=bibIdentifier&fq=DocType:bibliographic&fq=Language_search:Bengali&indent=on&q=Title_search:' . $search . '%20OR%20Author_search:' . $search . '%20OR%20Publisher_search:' . $search . '%20OR%20PublicationDate_search:' . $search . '%20OR%20PublicationPlace_search:' . $search . '&rows=5000&wt=xml';
	#$solrurl = 'http://james.lis.soas.ac.uk:8983/solr/bib/select?fl=bibIdentifier,Title_display,Author_display,Publisher_display,PublicationPlace_display,PublicationDate_display&fq=DocType:item&fq=Language_search:Bengali&indent=on&q=Title_search:' . $_POST["search"] . '%20OR%20Author_search:' . $_POST["search"] . '%20OR%20Publisher_search:' . $_POST["search"] . '%20OR%20PublicationDate_search:' . $_POST["search"] . '%20OR%20PublicationPlace_search:' . $_POST["search"] . '&rows=5000&wt=xml';

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
	
	if ($xml->result->attributes()->numFound == '0'){
		echo 'No results found';
	}
	else {
		// foreach ($xml->result->doc as $result) {
			// foreach ($result->arr as $result_array) {
				// print_r($result_array->str);
			// }
		// }
		#print_r($xml->result->doc);
		foreach ($xml->result->doc as $result){
			foreach ($result->arr->str as $id){
																
				$bib_id = ltrim($id, "wbm-");
				$baseurl = 'https://james.lis.soas.ac.uk:8443/oledocstore/documentrest/';
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
				<div class="wrap-result100">
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Title:</strong>
<?php
							foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>

<?php
				if ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Alternative title:</strong>
<?php
							foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>

<?php
				if ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Main author:</strong>
<?php
							foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>

<?php
				if ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']")):
?>
					<div class="wrap-header100">
						<div class="wrap-content100 p-t-05 p-b-10">
							<strong>Publication details:</strong>
<?php
							foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $subfield) {
								echo (string) $subfield . " ";
							}
?>
						</div>
					</div>
<?php
					endif;
?>
				
			<form class="p-l-55 p-r-55 p-b-75" action="crowdsource_edit.php" method="POST">

				<input type="hidden" value="
			<?php 
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
			</div>

</body>
</html>