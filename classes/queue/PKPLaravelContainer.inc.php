<?php
/**
 * @file classes/core/APIResponse.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class APIResponse
 * @ingroup core
 *
 * @brief Extends the Response class in the Slim microframework.
 */

use Illuminate\Container\Container; 
// use Illuminate\Queue\Queue;
// use Illuminate\Queue\Events\JobProcessed;
// use Illuminate\Queue\Events\JobProcessing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Console\Application as LaravelConsoleApplication;
use Illuminate\Events\Dispatcher as LaravelDispacher;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Factory;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\EventMutex;

class PKPLaravelContainer extends Container
{
	public function __construct()
    {
        $this->registerBaseBindings();
        $this->registerScheduleBindings();
    }

    public function isDownForMaintenance()
    {
        return false;
	}
	
	protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);
		$this->instance(Container::class, $this);
		
		$this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            PKPLaravelExceptionHandler::class
		);
		
		$this->app->bind('exception.handler', function () {
			return new PKPLaravelExceptionHandler();
		});
	}
	
	protected function registerScheduleBindings()
    {
        $this->bind(
            Factory::class,
            function ($app) {
                return new CacheManager($app);
            }
        );

        $this->bind(EventMutex::class, CacheEventMutex::class);
        // $this->bind(SchedulingMutex::class, CacheSchedulingMutex::class);
    }
}

class PKPLaravelKernel implements Kernel
{
	protected $app;
	protected $events;
	protected $artisan;

	/**
	 * Handle an incoming console command.
	 *
	 * @param Symfony\Component\Console\Input\InputInterface $input 
	 * @param Symfony\Component\Console\Output\OutputInterface|null $output 
	 *
	 * @return int
	 */
	public function __construct(PKPLaravelContainer $app, LaravelConsoleApplication $artisan, LaravelDispacher $events)
    {
        $this->app = $app;
		$this->events = $events;
		$this->artisan = $artisan;
    }

	function handle($input, $output = null) {
		try {
            return $this->getArtisan()->run($input, $output);
        } catch (Exception $e) {

            return 1;
        } 
	}
	
	/**
	 * Run an Artisan console command by name.
	 *
	 * @param string $command 
	 * @param array $parameters 
	 * @param Symfony\Component\Console\Output\OutputInterface|null $outputBuffer 
	 *
	 * @return int
	 */
	function call($command, array $parameters = array(), $outputBuffer = null) {
		return $this->getArtisan()->call($command, $parameters, $outputBuffer);
	}
	
	/**
	 * Queue an Artisan console command by name.
	 *
	 * @param string $command 
	 * @param array $parameters 
	 *
	 * @return Illuminate\Foundation\Bus\PendingDispatch
	 */
	function queue($command, array $parameters = array()) {
		return null;
	}
	
	/**
	 * Get all of the commands registered with the console.
	 *
	 * @return array
	 */
	function all() {
		return $this->getArtisan()->all();
	}
	
	/**
	 * Get the output for the last run command.
	 *
	 * @return string
	 */
	function output() {
		return $this->getArtisan()->output();
	}
	
	/**
	 * Terminate the application.
	 *
	 * @param Symfony\Component\Console\Input\InputInterface $input 
	 * @param int $status 
	 *
	 * @return void
	 */
	function terminate($input, $status) {
		exit($status);
	}

	public function setArtisan($artisan)
    {
        $this->artisan = $artisan;
	}
	
	protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            return $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                                ->resolveCommands($this->commands);
        }

        return $this->artisan;
	}
	
	/**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->app->instance(
            Schedule::class, $schedule = new Schedule($this->app[Cache::class])
        );

        $this->schedule($schedule);
	}
	
	/**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:restart')->hourly();
        $schedule->command('queue:work --sleep=3 --timeout=900 --queue=high,default,low')->runInBackground()->withoutOverlapping()->everyMinute();
    }
}

class PKPLaravelExceptionHandler implements \Illuminate\Contracts\Debug\ExceptionHandler
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
