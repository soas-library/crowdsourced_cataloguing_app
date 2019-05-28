<?php
# @name: crowdsource_edit.php
# @version: 0.1
# @creation_date: 2019-05-16
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>
<html>
<body>

<?php

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
		$solrurl = 'http://james.lis.soas.ac.uk:8983/solr/bib/select?fl=controlfield_001&fq=DocType:bibliographic&fq=Language_search:Bengali&indent=on&q=*&rows=5000&wt=xml';
	
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

		$random = rand(0,4061);
	
		$bib_id = $xml->result->doc[$random]->arr->str;
	}
	
	$baseurl = 'https://james.lis.soas.ac.uk:8443/oledocstore/documentrest';
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
	<form action="crowdsource_submit.php" method="POST">
		<fieldset>
			<legend><strong>Fix Me</strong></legend><br/>
			<strong>Title: </strong>
<?php
				
				#echo($content->xpath("///a:datafield[@tag='245']/a:subfield[@code='a']")[0]);

				foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code='a']") as $subfield) {
					echo (string) $subfield . " ";
				}

/* 				foreach ($content->record->datafield as $datafield) {
					if ((string) $datafield['tag'] == '245') {
						foreach ($datafield->subfield as $subfield) {
							echo (string) $subfield . " ";
						}
					}
				} */
				
				foreach ($content->record->datafield as $datafield) {
					if ((string) $datafield['tag'] == '880') {
						foreach ($datafield->subfield as $subfield) {
							#print_r($subfield);

							print_r(array_values($subfield));
							
							if (array_search('245-01',$subfield)) {
								print_r ($subfield);
							}
						}
					}
				}

?>
			<br/>
			<input type="text" name="title"><br/><br/>
			
			<strong>Alternative title: </strong>
<?php

				foreach ($content->record->datafield as $datafield) {
					if ((string) $datafield['tag'] == '246') {
						foreach ($datafield->subfield as $subfield) {
							echo (string) $subfield . " ";
						}
					}
				}

?>
			<br/>
			<input type="text" name="alt_title"><br/><br/>
			
			<strong>Main author: </strong>
<?php

				foreach ($content->record->datafield as $datafield) {
					if ((string) $datafield['tag'] == '100') {
						foreach ($datafield->subfield as $subfield) {
							echo (string) $subfield . " ";
						}
					}
				}

?>
			<br/>
			<input type="text" name="author"><br/><br/>
			
			<strong>Publication details: </strong>
<?php	

				foreach ($content->record->datafield as $datafield) {
					if ((string) $datafield['tag'] == '260' || (string) $datafield['tag'] == '264') {
						foreach ($datafield->subfield as $subfield) {
							echo (string) $subfield . " ";
						}
					}
				}

?>
			<br/>
			<input type="text" name="publication"><br/><br/>
			
			<strong>Comment: </strong>
			<br/>
			<input type="text" name="comment"><br/><br/>
			
			<input type="hidden" value="
			<?php 
				foreach ($content->record->controlfield as $controlfield) {
					if ((string) $controlfield['tag'] == '001') {
						echo (string) $controlfield;
					}
				}
			?>
			" name="id" />
			
		</fieldset>
		<div>
			<input type="submit" name="submit" value="Submit">
		</div>
	</form>
</body>
</html>