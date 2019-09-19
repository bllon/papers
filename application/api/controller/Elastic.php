<?php
namespace app\api\controller;
use Elasticsearch\ClientBuilder;

class Elastic
{
	public function createIndex()
	{
		require_once '../extend/vendor/autoload.php';
		
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		
		//建索引
		$indexParams = array();
		print "index exists?\n";
		$indexParams['index'] = 'paper_index';
		
		if($client->indices()->exists($indexParams)){
			//删除旧的索引
			print "start delete index ...\n";
			$client->indices()->delete($indexParams);
			print "finish delete index ...";
		}
		
		$typeMapping = array(
			'_source' => array(
				'enabled' => true
			),
			'properties' => array(
				'content' => array(
					'type' => 'text',
					'analyzer' => 'ik_max_word',
          			'search_analyzer' => 'ik_max_word'
				)
			)
		);
		
		//doc
		$indexParams['body']['mappings']['paper_type'] = $typeMapping;
		$client->indices()->create($indexParams);
		
		//开始减索引
		//读取数据	建立索引,	就是文档放到索引里面，在这里 文档就是一个精选集
		print "indexing..\n";
		$conn = mysqli_connect('localhost', 'root', '', 'papers');
		mysqli_query($conn, 'set names utf8');
		$result=mysqli_query($conn, 'select id,content from paper_word');
		while($paperlist = mysqli_fetch_array($result)){
			//文档
			$params = array();
			$params['body'] = array(
				'content' => $paperlist['content'],
				'paper_id' => $paperlist['id']
			);
			$params['index'] = 'paper_index';
			$params['type'] = 'paper_type';
			$params['id'] = $paperlist['id'];
			print_r($params);
			$client->index($params);
		}
		
		mysqli_close($conn);
	}

	public static function search($query)
	{
		require_once '../extend/vendor/autoload.php';
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		$searchParams = array();
		$searchParams['index'] = 'paper_index';
		$searchParams['type'] = 'paper_type';
		$searchParams['body']['query']['match']['content'] = $query;
		$results = @$client->search($searchParams);
//		halt($results);
		$conn = mysqli_connect("localhost","root","","papers");
		mysqli_query($conn, 'set names utf8');
		$hits = $results['hits']['hits'];
//		halt($hits);
		$data = [];
		foreach($hits as $hit){
//			$paper_id = $hit['_source']['paper_id'];
//			halt($hit);
			$data[] = array('id'=>$hit['_source']['paper_id'],'content'=>$hit['_source']['content']);
		}
		
		return $data;
	}
}
?>