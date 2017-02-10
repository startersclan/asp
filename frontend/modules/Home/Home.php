<?php
use System\View;

class Home
{
	public function index()
	{
		$View = new View('index', 'home');
		$View->render();
	}
}