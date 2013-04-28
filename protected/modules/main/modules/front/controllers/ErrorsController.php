<?php
class ErrorsController extends Controller
{

	public function actionError()
	{
		$this->render('error');
	}

	public function actionUndefined()
	{
	}
}