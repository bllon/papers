<?php
namespace app\admin\controller;

use app\admin\common\model\Lunwen;

use think\console\Command;
use think\console\Input;
use think\console\Output;



class Cache extends Command
{
	protected function configure()
    {
        $this->setName('cache')->setDescription('Command Test');
    }
 
    protected function execute(Input $input, Output $output)
    {
    	$this->setCache();
        // $output->writeln("TestCommand:");
    }

	//设置缓存
	public function setCache()
	{
		//1.实例化对象
		$redis = new \Redis();
		//2.定义主机和端口
		$host = '127.0.0.1';
		$port = 6379;
		//3.连接redis服务器
		$redis->connect($host , $port);
//			halt($redis->get('windows'));
		$paperList = Lunwen::all();
//		halt($paperList);
		$data = [];
		foreach($paperList as $paper){
			if($paper['lunwen_file']!==null){
				$data[]=array('id'=>$paper['id'],'lunwen_file'=>$paper['lunwen_file']);
			}
		}
		foreach($data as $paper){
			$redis->set("paper:id:".$paper['id'].":content",serialize($this->parserPdf(substr($paper['lunwen_file'],1))),86400);
		}
		
		echo "缓存成功";
	}

	//读取pdf给前台显示
	public function parserPdf($path)
	{
		include_once './extend/pdfparser/vendor/autoload.php';
		$parser = new \Smalot\PdfParser\Parser();       
		// 调用解析方法，参数为pdf文件路径，返回结果为Document类对象
		$path = str_replace("\\", "/", $path);
		$path = './public/'.$path;
		$document = $parser->parseFile($path);
		// 获取所有的页
		$pages = $document->getPages();
		//$pages[0]->getText();  //提取第一页的内容，想提取多页，可以按照下面的方法，用$key来控制要获取的页数
		// 逐页提取文本
	//		$pattern = '/[^,.;\s]+[,.;\s]/';
	//		$pat = "/[\x80-\xff]+/";
		$pat = "/([^\n]+)\n/";
		$content = []; 
		foreach($pages as $key=>$page){
			$content[$key] = '';
	//		halt(iconv_set_encoding('utf8', 'gbk',$pages[$key]->getText()));
			preg_match_all($pat, $pages[$key]->getText(),$matches);
			
			
			foreach($matches[1] as $match){
				$content[$key] .= preg_replace("/\s+|[<br>]+/", ' ',$match)."<br>";
			}	
		}  
		return $content; 
	}
}
?>