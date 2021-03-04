<?php

date_default_timezone_set('UTC');

require_once("./vendor/autoload.php");
require_once("./database.php");

use Devristo\Phpws\Framing\WebSocketFrame;
use Devristo\Phpws\Framing\WebSocketOpcode;
use Devristo\Phpws\Messaging\WebSocketMessageInterface;
use Devristo\Phpws\Protocol\WebSocketTransportInterface;
use Devristo\Phpws\Server\IWebSocketServerObserver;
use Devristo\Phpws\Server\UriHandler\WebSocketUriHandler;
use Devristo\Phpws\Server\WebSocketServer;


class PersonHandler extends WebSocketUriHandler {
    public $db;

    public function __construct($logger,$db)
    {
        parent::__construct($logger);
        $this->db = $db;
    }

    public function onConnect(WebSocketTransportInterface $person){
        foreach($this->getConnections() as $client){
            $client->sendString("Person {$person->getId()} joined the chat: ");
        }
    }

    public function onMessage(WebSocketTransportInterface $person, WebSocketMessageInterface $msg) {
        $personCode = $msg->getData();
        $this->db->sql('SELECT * FROM person WHERE code="'.$personCode.'"');
        $person = $this->db->getResult();
        foreach($this->getConnections() as $client){
            $result = json_encode($person);
            $client->sendString("result is: {$result}");
        }
    }
}

$loop = \React\EventLoop\Factory::create();
$db = new Database();
$db->connect();

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

$server = new WebSocketServer("tcp://0.0.0.0:12345", $loop, $logger);


$router = new \Devristo\Phpws\Server\UriHandler\ClientRouter($server, $logger);
$router->addRoute('#^/server$#i', new PersonHandler($logger,$db));

$server->bind();

$loop->run();

?>
