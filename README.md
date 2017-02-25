##本工具类主要针对阿里队列服务MQ的操作，采用HTTP方式接入MQ

###1 初始化队列类
$mq=new \Aliware\mqQueue();

###2 发送队列内容
$return=$mq->sendmsg('{code:200,"msg":"asda"}');
var_dump(json_decode($return,true));

###3 接收队列内容
$return=$mq->Responsemsg();
var_dump(json_decode($return,true));
