<?php
/**
 * Kafka高级别消费者
 *
 * @author CHAIYUE
 * @version 2016-06-14
 */
include 'kafkaConfig.php';
class kafkaHighConsumer
{
	protected $topics = null;
	protected $group_id = null;
	protected $acks = 0;

	public function Main($setupCallback,$operationfunction)
	{
		$setup = $setupCallback->bindTo($this, __CLASS__);
		$setup();
		if(empty($this->topics) || empty($this->group_id))
		{
			return false;
		}
		$conf = new RdKafka\Conf();
		// 设置平衡机制
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

