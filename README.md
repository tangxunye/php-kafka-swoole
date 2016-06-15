# PHP-Rdkafka Demo#

###环境依赖###
- zookeeper+kafka(>=0.09)
- php>=5.4
- [librdkafka](https://github.com/edenhill/librdkafka "librdkafka")
- [php-rdkafka](https://github.com/arnaud-lb/php-rdkafka "php-rdkafka")
- [swoole](https://github.com/swoole/swoole-src "swoole")

###Kafka高级别消费者(kafkaHighConsumer)使用###
   ```
    include 'kafkaHighConsumer.php';

	$kafkaobj = new kafkaHighConsumer();
	$kafkaobj->Main(function () {
	$this->topics = ['lowtest'];
	$this->group_id = 'lowtest';
	}, function ($message) {
	echo 'offset:' . $message->offset . 'partition' . $message->partition . "\n";
	});
    ```
- $this->topics   是你的要订阅的topic
- $this->group_id 是你的分组名称
- Main方法的第一个参数是用的闭包设置参数，第二个参数是获取到的消息进行处理的方法

###多线程高级消费者(taskKafkaConsumer)使用###
支持一次性开启多个单一分组的consumer，当然最好consumer的个数不能大于producers的个数
   ```
    include 'taskKafkaConsumer.php';

	$kafkaobj = new taskKafkaConsumer(function () {
	$this->topics = ['lowtest'];
	$this->group_id = 'lowtest';
	$this->task_worker_num = 2;
	}, function ($message) {
	echo 'offset:' . $message->offset . 'partition' . $message->partition . "\n";
	});

    ```
- $this->topics   是你的要订阅的topic
- $this->group_id 是你的分组名称
- $this->task_worker_num 启动consumer的个数
- Main方法的第一个参数是用的闭包设置参数，第二个参数是获取到的消息进行处理的方法
