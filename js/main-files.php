var URL_UPLOAD_FILE = '<?php echo $this->createUrl("/upload/upload/file"); ?>';
var URL_PROCESS_TEXT_FILE = '<?php echo $this->createUrl("/upload/upload/processTextFiles"); ?>';
var URL_DEL_ARCHIVE = '<?php echo $this->createUrl("/upload/upload/delArchive"); ?>';
var packetSize = <?php echo Yii::app()->params['packetSize'] ?>;
var reconnectionTimeout = 5000;