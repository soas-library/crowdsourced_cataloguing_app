<?php
# @name: crowdsource_search.php
# @version: 0.1
# @creation_date: 2019-03-08
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>
<html>
<body>

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
			
				echo "<h3>Record</h3>";
			
				foreach ($content->xpath("///a:controlfield[@tag='001']") as $controlfield) {
					echo (string) $controlfield;
				}
				
				echo "<br/>";
			
				foreach ($content->xpath("///a:datafield[@tag='245']/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo '<br/>';
				
				foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'245')]/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo "<br/>";
				
				foreach ($content->xpath("///a:datafield[@tag='246']/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo '<br/>';
				
				foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'246')]/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo "<br/>";
				
				foreach ($content->xpath("///a:datafield[@tag='100']/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo '<br/>';
				
				foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'100')]/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo "<br/>";
				
				foreach ($content->xpath("///a:datafield[@tag='260']/a:subfield[@code!='6']|///a:datafield[@tag='264']/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo '<br/>';
				
				foreach ($content->xpath("///a:datafield[@tag='880'][contains(.,'160')]/a:subfield[@code!='6']|///a:datafield[@tag='880'][contains(.,'264')]/a:subfield[@code!='6']") as $subfield) {
					echo (string) $subfield . " ";
				}
				
				echo "<br/>";
?>
				
			<form action="crowdsource_edit.php" method="POST">

				<input type="hidden" value="
			<?php 
				foreach ($content->xpath("///a:controlfield[@tag='001']") as $controlfield) {
					echo (string) $controlfield;
				}
			?>
				" name="id" />
			
				<div>
					<input type="submit" name="submit" value="Edit">
				</div>
			</form>
<?php
			}
		}
	}
?>

</body>
</html>