<?php
# @name: crowdsource_feedback_submit.php
# @version: 0.1
# @creation_date: 2019-08-13
# @license: The MIT License <https://opensource.org/licenses/MIT>
# @author: Simon Bowie <sb174@soas.ac.uk>
# @purpose: A prototype of a web application to crowdsource cataloguing for SOAS' bibliographic records
# @description: Submit data to an email address from crowdsource_feedback.php
?>

<?php
require __DIR__ . '/vendor/autoload.php';

// Retrieve configuration variables from the config.env file
$dotenv = Dotenv\Dotenv::create(__DIR__, 'config.env');
$dotenv->load();

// This function 'cleans up' inputted data by removing extraneous whitespaces or special characters
function test_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}

// Verify the reCAPTCHA response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recaptcha_response'])){
	
	// Build POST request to verify reCAPTCHA response via Google's API
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret = $_ENV['recaptcha_secret'];
    $recaptcha_response = $_POST['recaptcha_response'];

    // Send and decode POST request
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned
    if ($recaptcha->score >= 0.5) {
        // Verified
		
		// Define variables and set to empty values
		$name = $from_email = $comment = "";
	
		// Set variables to values from form POST
		$name = test_input($_POST["name"]);
		$from_email = test_input($_POST["email"]);
		$comment = test_input($_POST["comment"]);

        // Send an email to the email address
        $to = $_ENV['email']; // this is your email address
        $from = $from_email; // this is the sender's email address
        $subject = "Feedback from crowdsourced cataloguing application";
        $message = "Feedback from " . $name . " follows:" . "<br /><br />" .  $comment;
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= "From:" . $from;
        mail($to,$subject,$message,$headers);
		
		// Redirect user to crowdsource_thanks.php. This prevents them from refreshing the submit page to make multiple requests.
		header('Location: crowdsource_thanks.php');
		
    } else {
        // Not verified
		// Redirect user to crowdsource_error.php to display error message
		header('Location: crowdsource_error.php');
    }
}

?>
