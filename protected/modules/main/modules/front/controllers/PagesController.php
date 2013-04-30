<?php
class PagesController extends Controller
{
	public function actionTextFiles()
	{
		$this->render('textFiles');
	}

	public function actionImages()
	{
		$this->render('images');
	}
}