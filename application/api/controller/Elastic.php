<?php
namespace app\api\controller;
use Elasticsearch\ClientBuilder;

class Elastic
{
	//建立搜索索引
	public function createSearchIndex()
	{
		require_once '../extend/vendor/autoload.php';
		
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		
		//建索引
		$indexParams = array();
		print "index exists?\n";
		$indexParams['index'] = 'paper_search';//索引名称
		
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
				'lunwen_title' => array(
					'type' => 'text',
					'analyzer' => 'ik_max_word',
          			'search_analyzer' => 'ik_max_word'
				),
				'writer' => array(
					'type' => 'text'
				),
				'rank_type' => array(
					'type' => 'text',
					'analyzer' => 'ik_max_word',
          			'search_analyzer' => 'ik_max_word'
				),
				'lunwen_rank' => array(
					'type' => 'text',
					'analyzer' => 'ik_max_word',
          			'search_analyzer' => 'ik_max_word'
				)
			)
		);
		
		//doc
		$indexParams['body']['mappings']['paper_id'] = $typeMapping;//索引类型
		$client->indices()->create($indexParams);
		
		//开始减索引
		//读取数据	建立索引,	就是文档放到索引里面，在这里 文档就是一个精选集
		print "indexing..\n";
		$conn = mysqli_connect('localhost', 'root', '', 'papers');
		mysqli_query($conn, 'set names utf8');
		$result=mysqli_query($conn, 'select id,lunwen_title,writer,rank_type,lunwen_rank from paper_lunwen');
		while($paperlist = mysqli_fetch_array($result)){
			//文档
			$params = array();
			$params['body'] = array(
				'lunwen_title' => $paperlist['lunwen_title'],
				'writer' => $paperlist['writer'],
				'rank_type' => $paperlist['rank_type'],
				'lunwen_rank' => $paperlist['lunwen_rank'],
				'paper_id' => $paperlist['id']
			);
			$params['index'] = 'paper_search';
			$params['type'] = 'paper_id';
			$params['id'] = $paperlist['id'];
			print_r($params);
			// http://localhost:9200/paper_index/paper_type/1
			$client->index($params);
		}
		
		mysqli_close($conn);
	}

	//搜索
	public function paperSearch($keyword)
	{
		require_once '../extend/vendor/autoload.php';
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		$params = [
		    'index' => 'paper_search',
		    'type' => 'paper_id',
		    'body' => [
		        'query' => [
		            'multi_match' => [
		                'query' => $keyword,
		                'fields'=>['lunwen_title','writer','rank_type','lunwen_rank']
		            ]
		        ],
		        'size'=>200
		    ]
		];


		$results = @$client->search($params);
		// halt($results);
		$conn = mysqli_connect("localhost","root","","papers");
		mysqli_query($conn, 'set names utf8');
		$hits = $results['hits']['hits'];
		// halt($hits);
		$data = [];
		foreach($hits as $hit){
//			$paper_id = $hit['_source']['paper_id'];
			// halt($hit);
			$data[] = $hit['_source']['paper_id'];
		}
		return $data;
	}

	//建立论文句子索引
	public function createWordIndex()
	{
		require_once '../extend/vendor/autoload.php';
		
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		
		//建索引
		$indexParams = array();
		print "index exists?\n";
		$indexParams['index'] = 'check_word';
		
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
		$indexParams['body']['mappings']['paper_word'] = $typeMapping;
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
			$params['index'] = 'check_word';
			$params['type'] = 'paper_word';
			$params['id'] = $paperlist['id'];
			print_r($params);
			// http://localhost:9200/paper_index/paper_type/1
			$client->index($params);
		}
		
		mysqli_close($conn);
	}

	public static function search($query)
	{
		require_once '../extend/vendor/autoload.php';
		$client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
		$searchParams = array();
		$searchParams['index'] = 'check_word';
		$searchParams['type'] = 'paper_word';
		$searchParams['body']['query']['match']['content'] = $query;
		$results = @$client->search($searchParams);
//		halt($results);
		$conn = mysqli_connect("localhost","root","","papers");
		mysqli_query($conn, 'set names utf8');
		$hits = $results['hits']['hits'];
		// halt($hits);
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