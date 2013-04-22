<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link rel="stylesheet" type="text/css" href="css/reset.css">
        <link rel="stylesheet" type="text/css" href="css/main.css">
        <link rel="stylesheet" type="text/css" href="css/front.css">
        <link rel="stylesheet" type="text/css" href="css/smoothness/jquery-ui-1.10.2.custom.min.css">
        <script type="text/javascript" src="js/jquery-1.9.1.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.10.2.custom.min.js"></script>
	<title></title>
</head>

<body>
	<?php
		//session_destroy();
		echo 'Upload dir: ' . Yii::app()->params['uploadPath'];
		echo '<pre id="sessionInfo" style="overflow: hidden; width: 550px; background-color: #fff; position: absolute;">Session information:';
		var_dump(Yii::app()->session['filesInfo']);
		echo '</pre>';
	?>
    <?php echo $content; ?>
</body>
</html>
