<?php
use System\View;

class Home
{
	public function Init() 
	{
		$View = new View('index', 'Home');
		$View->render();
	}
}
?>