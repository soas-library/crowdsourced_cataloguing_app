<?php
# @name: crowdsource_submit.php
# @version: 0.1
# @creation_date: 2019-03-08
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
	
?>