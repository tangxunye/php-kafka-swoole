<?php
/**
 * Kafka高级别消费者(多线程版)
 *
 * @author CHAIYUE
 * @version 2016-06-14
 */
include 'kafkaConfig.php';
class taskKafkaConsumer
{
	protected $serv;
	protected $task_worker_num = 20;
	protected $topics = null;
	protected $group_id = null;
	protected $acks = 0;
	protected $operationfunction = null;

	public function __construct($setupCallback, $operationfunction)
	{
		$this->operationfunction = $operationfunction;
		$setup = $setupCallback->bindTo($this, __CLASS__);
		$setup();
		$this->serv = new swoole_server("0.0.0.0", 9566);
		$this->serv->set([
				'worker_num'=>8, 
				'daemonize'=>false, 
				'max_request'=>10000, 
				'dispatch_mode'=>2, 
				'debug_mode'=>1, 
				'task_worker_num'=>$this->task_worker_num]);
		$this->serv->on('WorkerStart', [$this, 'onWorkerStart']);
		$this->serv->on('Start', [$this, 'onStart']);
		$this->serv->on('Receive', [$this, 'onReceive']);
		$this->serv->on('Task', [$this, 'onTask']);
		$this->serv->on('Finish', [$this, 'onFinish']);
		$this->serv->start();
	}

	public function onWorkerStart($serv, $worker_id)
	{
		// 判定是否为Task Worker进程
		if($worker_id >= $serv->setting['worker_num'])
		{
			if(empty($this->topics) || empty($this->group_id))
			{
				return false;
			}
			$conf = new RdKafka\Conf();
			$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
				switch($err)
				{
					case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
						echo "Assign: ";
						$kafka->assign($partitions);
						break;
					case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
						echo "Revoke: ";
						$kafka->assign(NULL);
						break;
					default:
						throw new \Exception($err);
				}
			});
			$conf->set('group.id', $this->group_id);
			$conf->set('group.id', 'lowtest');
			$conf->set('metadata.broker.list', KAFKA_BORCKERS);
			$topicConf = new RdKafka\TopicConf();
			$topicConf->set('auto.commit.enable', 'true');
			$topicConf->set('auto.commit.interval.ms', 100);
			// 'smallest'从上一次偏移开始读
			// 'largest'最新读（默认）
			$topicConf->set('auto.offset.reset', 'smallest');
			$conf->setDefaultTopicConf($topicConf);
			$consumer = new RdKafka\KafkaConsumer($conf);
			$consumer->subscribe($this->topics);
			echo "Waiting for partition assignment... (make take some time when\n";
			echo "quickly re-joinging the group after leaving it.)\n";
			while(true)
			{
				$message = $consumer->consume(120 * 1000);
				switch($message->err)
				{
					case RD_KAFKA_RESP_ERR_NO_ERROR:
						$operationfunction = $this->operationfunction;
						$operationfunction($message);
						break;
					case RD_KAFKA_RESP_ERR__PARTITION_EOF:
						echo "No more messages; will wait for more\n";
						break;
					case RD_KAFKA_RESP_ERR__TIMED_OUT:
						echo "Timed out\n";
						break;
					default:
						throw new \Exception($message->errstr(), $message->err);
						break;
				}
			}
		}
	}

	public function onStart(swoole_server $serv)
	{
		echo "Start\n";
	}

	public function onTask($serv, $task_id, $from_id, $data)
	{
		echo "This Task {$task_id} from Worker {$from_id}\n";
	}

	public function onFinish($serv, $task_id, $data)
	{
		echo "Task {$task_id} finish\n";
	}

	public function onReceive(swoole_server $serv, $fd, $from_id, $data)
	{
		echo "Receive";
	}
}
