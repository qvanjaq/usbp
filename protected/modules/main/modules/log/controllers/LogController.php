<?php
class LogController extends Controller
{
	const LOG_CATEGORY_JS = 'javascript';
	public function actionWriteJs()
	{
		if(!isset($_POST['message'])) {
			Yii::log('Empty post data "message", for write in log', 'error', self::LOG_CATEGORY_JS);
			Yii::app()->end();
		}

		$browser = $_SERVER['HTTP_USER_AGENT'];
		Yii::log('Report javascript error in browser ' . $browser . " {\n" . $_POST['message'] . '}', 'error', self::LOG_CATEGORY_JS);
	}
}