<?php
/*
**命令行调用
*/
namespace app\index\controller;

use think\console\Command;
use think\console\Input;
use think\console\Input\Argument;
use think\console\input\Option;
use think\console\Output;


class Server extends Command
{
	protected function configure()
    {
        $this->setName('server')
        	// ->addArgument('name', Argument::OPTIONAL, "your name")
         //    ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
        	->setDescription('websocket server');
    }

	protected function execute(Input $input, Output $output)
    {
    	// $this->setCache();
        $output->writeln("TestCommand:");
    }
}
?>