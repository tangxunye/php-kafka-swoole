<?php
/**
 * Kafka生产者
 *
 * @author CHAIYUE
 * @version 2016-06-14
 */
include 'kafkaConfig.php';
class kafkaProducer
{
	protected $topic = null;
	protected $acks = 0;

	public function Main($msg, $setupCallback)
	{
		$setup = $setupCallback->bindTo($this, __CLASS__);
		$setup();
		if(empty($this->topic)|| empty($msg))
		{
			return false;
		}
		$rk = new RdKafka\Producer();
		$rk->setLogLevel(LOG_DEBUG);
		$rk->addBrokers(KAFKA_BORCKERS);
		$topicConf = new \RdKafka\TopicConf();
		// 开启提交失败重复提交
		$topicConf->set("request.required.acks", $this->acks);
		$topic = $rk->newTopic($this->topic, $topicConf);
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);
	}
}