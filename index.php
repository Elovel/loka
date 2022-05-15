<?php

error_reporting(E_ALL);
ini_set('display_errors','1');
ini_set('memory_limit' , '-1');
ini_set('max_execution_time','0');
ini_set('display_startup_errors','1');

if (\file_exists(__DIR__.'/vendor/autoload.php')) {
    include __DIR__.'/vendor/autoload.php';
} else {
    if (!\file_exists(__DIR__.'/madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php',__DIR__.'/madeline.php');
    }
    /**
     * @psalm-suppress MissingFile
     */
    include __DIR__.'/madeline.php';
}


use \danog\MadelineProto\API;
use \danog\Loop\Generic\GenericLoop;
use \danog\MadelineProto\EventHandler;

class XHandler extends EventHandler
{
const Admins = [740910481 ];
const Report = 'hrlot';

public function getReportPeers()
{
return [self::Report];
}

public function genLoop()
{
yield $this->account->updateStatus([
'offline' => false
]);
/*yield $this->messages->sendMessage([
'peer'=> self::Admins[0],
'message' => 'Generic Loop Start At : ' . date('H:i:s')
]);*/
return 60000;
}

public function onStart()
{
$genLoop = new GenericLoop([$this, 'genLoop'], 'update Status');
$genLoop->start();
}

public function onUpdateNewChannelMessage($update)
{
yield $this->onUpdateNewMessage($update);
}

public function onUpdateNewMessage($update)
{
if (time() - $update['message']['date'] > 2) {
return;
}


try {
$msgOrig = $update['message']['message']?? null;
$messageId = $update['message']['id']?? 0;
$fromId= $update['message']['from_id']['user_id']?? 0;
$replyToId = $update['message']['reply_to']['reply_to_msg_id']?? 0;
$peer= yield $this->getID($update);

if((in_array($fromId, self::Admins))) {
if(preg_match('/^[\/\#\!\.]?(ping|ربات)$/si', $msgOrig)) {
yield $this->messages->sendMessage([
'peer'=> $peer,
'message' => 'Pong !',
'reply_to_msg_id' => $messageId
]);
}
elseif (preg_match('/^[\/\#\!]?(restart|ریستارت)$/si',$msgOrig)){
yield $this->messages->sendMessage([
'peer'=> $peer,
'message' => 'Restarted ...',
'reply_to_msg_id' => $messageId
]);
$this->restart();
}
elseif(preg_match('/^[\/\#\!\.]?(status|وضعیت|وضع|مصرف|usage)$/si', $msgOrig)){
$answer = 'Memory Usage : ' . round(memory_get_peak_usage(true) / 1021 / 1024, 2) . ' MB';
yield $this->messages->sendMessage([
'peer'=> $peer,
'message' => $answer,
'reply_to_msg_id' => $messageId
]);
}
}
} catch (\Throwable $e){
$this->report("Surfaced: $e");
}
}
}
$settings = [
'serialization' => [
'cleanup_before_serialization' => true,
],
'logger' => [
'max_size' => 1*1024*1024,
],
'peer' => [
'full_fetch' => false,
'cache_all_peers_on_startup' => false,
]
];

$bot = new \danog\MadelineProto\API('X.session', $settings);
$bot->startAndLoop(XHandler::class);
?>
