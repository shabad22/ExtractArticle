<?php 

function remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}

echo '<pre>';

$input = new SplFileObject('./testpa');
$output = new SplFileObject('./testpa', "a");
$input->setFlags(SplFileObject::DROP_NEW_LINE);

ini_set("memory_limit", "512M");
$counter = $empty_counter = $error_counter = 0;
while(!$input->eof()) {
	ini_set("max_execution_time", 300);
	$out_line = '';
	$in_line = $input->fgets();
	$in_line = remove_utf8_bom($in_line);
	if(empty($in_line)) {
		$empty_counter++;
		echo $in_line;
		continue;
	}
	$in_line = json_decode($in_line);
	if(json_last_error()) {
		$error_counter++;
		echo json_last_error_msg()."\n<pre>";
		var_dump($in_line);
		echo"</pre>";
		continue;
	}
	$counter++;
	echo "Processing : $counter";
	foreach($in_line as $word) {
		if(in_array($word[1], ['NN', 'NNS', 'NNP', 'NNPS'])) { 
		//if(in_array($word[1], ['NN', 'NNS'])) {
			$out_line .= "$word[0] ";
		}
	}
	/**
	  * Didn't checked for empty lines because other article is not
	  * equally accesed and aligned after this process for both articles,
	  * they need to realign or this file needs to modify
	  * and proces both language articles at same time
	  */
	$output->fwrite("$out_line\n\n");
	echo ' - Done<br/>';
}

echo "Done!\nErrors : {$error_counter}\nEmpty : {$empty_counter}";
