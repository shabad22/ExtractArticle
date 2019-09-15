<?php 

session_start();
$start = microtime(true);
include_once "Curl.php";

$FETCH_FAILED = 0;
$FETCH_SUCCESS = 1;

$db = mysqli_connect('localhost', 'root', '', 'phd-test-01-03-17');
if(!$db) {
	die("Connection failed");
}

$db->query("SET NAMES 'utf8'");

$_SESSION['counter'] = (isset($_SESSION['counter'])) ? $_SESSION['counter'] : (isset($_GET['custom_start']) ? $_GET['custom_start'] : 0);
$_SESSION['time'] = (isset($_SESSION['time'])) ? $_SESSION['time'] : 0;
$failed = (isset($_SESSION['failed'])) ? $_SESSION['failed'] : '';
$params = ['format' => 'json', 
			'action' => 'query', 
			'prop' => 'extracts', 
			'explaintext' => 'true',
			'titles' => ''];
$url = 'https://en.wikipedia.org/w/api.php';
$curl = new Curl();
$curl->setUserAgent('Mozilla/5.0 (Windows NT 6.1; rv:51.0) Gecko/20100101 Firefox/51.0');
$curl->setReferer('http://google.com');
$curl->setOpt(CURLOPT_SSL_VERIFYPEER, true);
$curl->setOpt(CURLOPT_CAPATH, __DIR__);
$curl->setOpt(CURLOPT_CAINFO, __DIR__ . '\cacert.pem');

$record = $db->query("SELECT * FROM `langlinks` ORDER BY `ll_from` LIMIT {$_SESSION['counter']}, 1")->fetch_object();
$params['titles'] = $record->ll_title;
if($curl->get($url, $params)) {
	$article = $db->escape_string($curl->response);
	$db->query("INSERT INTO `articles`(`ll_from`, `ll_lang`, `ll_title`, `url`, `content`, `status`, `error_number`, `error_message`,`curl_error`) VALUES ($record->ll_from, '$record->ll_lang', '$record->ll_title', '{$url}', '{$article}', {$FETCH_FAILED}, '{$curl->error}', '{$curl->error_message}', '{$curl->curl_error}')");
	$msg = 'FFailed';
} else {
	$article = $db->escape_string($curl->response);
	if(!$db->query("INSERT INTO `articles`(`ll_from`, `ll_lang`, `ll_title`, `url`, `content`, `status`, `error_number`, `error_message`,`curl_error`) VALUES ($record->ll_from, '$record->ll_lang', '$record->ll_title', '{$url}', '{$article}', {$FETCH_SUCCESS}, '{$curl->error}', '{$curl->error_message}', '{$curl->curl_error}')")) {
		$failed .= $record->ll_from.'-';
		$msg = 'Failed';
	} else {
		$msg = 'Success';
	}
}

$_SESSION['time'] = $_SESSION['time'] + (microtime(true) - $start) + 3.3;
?>

<!doctype html>
<html lang="en-US">

<head>
	<meta charset="utf-8" />
	<title>Wiki Extract</title>
	<style>
		body {
			margin: 10px 25px;
		}
	</style>
</head>
<body>
	<h1>Wiki Extractor - Test one</h1>
	<h3>ID : <strong><?php echo $record->ll_from;?></strong></h3>
	<h3>Title : <strong><?php echo $record->ll_title;?></strong></h3>
	<h3>Processed : <strong><?php echo ++$_SESSION['counter'];?></strong></h3>
	<h3>Total time : <strong><?php echo ($_SESSION['time']/60);?></strong> minutes</h3>
	<h3>Message : <strong><?php echo $msg;?></strong></h3>
	<h3>Error : <strong><?php echo $curl->error;?></strong></h3>
	<h3>Error-message : <strong><?php echo $curl->error_message;?></strong></h3>
	<h3>cURL-error : <strong><?php echo $curl->curl_error;?></strong></h3>
	<script src="assets/jquery.js"></script>
	<script>
		$(document).ready(function() {
			setTimeout(function() {
				window.location.reload();
			}, <?php if($_SESSION['counter']%250 == 0) echo '15000'; else echo '3000';?>);
		});
	</script>
</body>
</html>