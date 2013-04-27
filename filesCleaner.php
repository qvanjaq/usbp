#!C:/xampp/php/php.exe
<?php
	echo 'Hellow!';
	$log = fopen('logphp.txt', 'a+');
	fwrite($log, 'files cleaner');
	fclose($log);

	echo 'finish!';
?>