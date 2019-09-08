<?php 

/**
  * This file use articles.sql table and punjabi articles extracted from dump as input
  * After reading an article of punjabi from input file, we fetch article-ID, with this fetched id,
  * we fetch JSON object of article from articles.sql.
  * Then we removed everything except Punjabi Alphabet and Numbers From Punjabi article
  * (which we read from input file) and comprise article in single line.
  * After all that, we save this clean Punjabi article and Fetched clean english article to 
  * two seprate files (now the line numbers of articles are same in both files)
  */

echo '<pre>';

$db = mysqli_connect('localhost', 'root', '', 'phd-test-01-03-17');
if(!$db) {
	die("Connection failed");
}
$db->query("SET NAMES 'utf8'");

$output_pa = new SplFileObject("output-pa/align.data", "a");
$output_en = new SplFileObject("output-en/align.data", "a");

$overall_counter = 0;

for($i = 1; $i <= 9; $i++) {
	$input = new SplFileObject("input/$i");

	if(!$input) {
		die('Unable to read input file!');
	} else {
		$input->setFlags(SplFileObject::DROP_NEW_LINE);
		$en_article = null;
		$pa_article = null;
		$write_flag = $reset = false;
		$index = 0;
		
		while (!$input->eof()) {
			ini_set('max_execution_time', 300);
			$line_in = $input->fgets();
			
			if(strpos($line_in, '<doc') !== false) {
				$line_in = explode('"', $line_in);
				$id = $line_in[1];
				$title = substr(end($line_in), 1).' ';
				$pa_article = ''; //$title;
				unset($line_in);
				$record = $db->query("SELECT `ll_title`, `content` FROM `articles` WHERE `ll_from` = {$id}");
				//AND `status` = 1
				if($record->num_rows > 0) {
					$write_flag = true;
					$record = $record->fetch_array(MYSQLI_ASSOC);
					$en_article = ''; //$record['ll_title'].' ';
					$content = current(json_decode($record['content'])->query->pages);
					if(isset($content->missing) || empty($content->extract)) {
						$write_flag = false;
						$pa_article = null;
						$en_article = null;
						$title = null;
						$id = null;
						continue;
					}
					$en_article .= 	preg_replace("/[^a-zA-Z0-9\.\,\'\"\?\!\-\s]/", "",
										preg_replace('/\s+/', ' ',
											$content->extract
										)
									);
					//$db->query("UPDATE `pa-en-langlinks` SET `status` = 1 WHERE `ll_from` = {$id}");
				}
			} elseif(strpos($line_in, '</doc') !== false) {
				if($write_flag && !empty($pa_article)) {
					$overall_counter++;
					$index++;
					echo "$overall_counter : Processing file $i : Saved : $index - $id - $title<br/>";
					$output_pa->fwrite($pa_article."\n\n");
					$output_en->fwrite($en_article."\n\n");
				}
				$pa_article = null;
				$en_article = null;
				$title = null;
				$id = null;
				$write_flag = $reset;
			} else {
				$pa_article .= preg_replace("/[^\x{0A05}\x{0A06}\x{0A07}\x{0A08}\x{0A09}\x{0A0A}\x{0A0F}\x{0A10}\x{0A13}\x{0A14}\x{0A15}\x{0A16}\x{0A17}\x{0A18}\x{0A19}\x{0A1A}\x{0A1B}\x{0A1C}\x{0A1D}\x{0A1E}\x{0A1F}\x{0A20}\x{0A21}\x{0A22}\x{0A23}\x{0A24}\x{0A25}\x{0A26}\x{0A27}\x{0A28}\x{0A2A}\x{0A2B}\x{0A2C}\x{0A2D}\x{0A2E}\x{0A2F}\x{0A30}\x{0A32}\x{0A33}\x{0A35}\x{0A36}\x{0A38}\x{0A39}\x{0A59}\x{0A5A}\x{0A5B}\x{0A5C}\x{0A5E}\x{0A72}\x{0A73}\x{0A02}\x{0A3C}\x{0A3E}\x{0A3F}\x{0A40}\x{0A41}\x{0A42}\x{0A47}\x{0A48}\x{0A4B}\x{0A4C}\x{0A4D}\x{0A70}\x{0A71}\x{0A66}\x{0A67}\x{0A68}\x{0A69}\x{0A6A}\x{0A6B}\x{0A6C}\x{0A6D}\x{0A6E}\x{0A6F}\x{0964}[:space:]\?\,\'\"\?\!\-0-9]/u", "", str_replace(['\n\r', '\n', '\r'], [' '], $line_in));
			}
		}
	}
}
echo 'Done!';