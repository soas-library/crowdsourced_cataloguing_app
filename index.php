<?php
# @name: index.php
# @version: 0.1
# @creation_date: 2019-03-08
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
?>

<html>
<body>
Help us learn Bengali<br><br>

<a href='crowdsource_edit.php'>Give me a random book</a><br><br>

<form action="crowdsource_search.php" method="POST"> 
	<input type="text" name="search" placeholder="Search for a book"> 
	<input type="submit" name="submit" value="Search"> 
</form> 

</body>
</html>