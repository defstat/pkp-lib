
<?php

use Illuminate\Contracts\Debug\ExceptionHandler;

class PKPLaravelExceptionHandler implements ExceptionHandler
{
	public function shouldReport(Throwable $e)
	{
		var_dump($e->getMessage());
		return true;
	}

	public function report(Throwable $e)
	{
		var_dump($e->getMessage());
	}

	public function render($request, Throwable $e)
	{
		var_dump($e->getMessage());
		return null;
	}

	public function renderForConsole($output, Throwable $e)
	{
		var_dump($e->getMessage());
	}
}