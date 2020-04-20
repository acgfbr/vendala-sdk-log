<?php

namespace Vendala\Logs;

use Aws\Firehose\FirehoseClient;
use Exception;
use stdClass;
use Throwable;

final class SDKLog implements SDKLogInterface
{
    /**
     * determina se está mockado ou não para testes
     * @var boolean
     */
    private $mocked;

    /**
     * access key da aws
     * @var string
     */
    private $key;

    /**
     * secret key da aws
     * @var string
     */
    private $secret;

    /**
     * versão da api da aws
     * @var string
     */
    private $version = '2015-08-04';

    /**
     * região da aws
     * @var string
     */
    private $region = 'us-east-1';

    /**
     * json final enviado ao kinesis
     * @var json
     */
    private $payload;

    public function __construct($mock = null)
    {
        if ($mock) {
            $this->mocked = true;
        }

        $this->payload = new stdClass;

        $this->payload->messages = [];
        $this->payload->methods = [];
        $this->payload->props = [];
    }

    /**
     * Seta a access key da aws
     * @param string $key
     * @return void
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }

    /**
     * Seta a secret key da aws
     * @param string $secret
     * @return void
     */
    public function setSecret($secret): void
    {
        $this->key = $secret;
    }

    /**
     * Seta se a execução do processo foi sucesso ou erro ( true | false )
     * @param bool $val
     * @return void
     */
    public function setWellExecuted($val): void
    {
        $this->payload->wellExecuted = $val;
    }

    /**
     * Seta o tipo de log ( history | log )
     * @param string $val
     * @return void
     */
    public function setLevel($val): void
    {
        $this->payload->level = $val;
    }

    /**
     * Seta o environment ( local, dev, prod )
     * @param string $env
     * @return void
     */
    public function setEnvironment($env): void
    {
        $this->payload->env = $env;
    }

    /**
     * Seta a aplicação ( vendala, simplifique, questions, lambdared )
     * @param string $app
     * @return void
     */
    public function setApp($app): void
    {
        $this->payload->app = $app;
    }

    /**
     * Seta a universal_id ( primary key )
     * @param string $uid
     * @return void
     */
    public function setUid($uid): void
    {
        $this->payload->uid = $uid;
    }

    /**
     * Seta a tabela referencia
     * @param string $table
     * @return void
     */
    public function setTable($table): void
    {
        $this->payload->table = $table;
    }

    /**
     * Seta o banco referencia
     * @param string $database
     * @return void
     */
    public function setDatabase($database): void
    {
        $this->payload->database = $database;
    }

    /**
     * Seta o trace de mensagens do processo
     * @param string|array $message
     * @return $this
     */
    public function addMessage($message, ...$args): SDKLog
    {
        if (is_array($message)) {
            foreach ($message as $item) {
                $this->addMessage($item);
            }
            return $this;
        }

        $this->payload->messages[] = isset($args) ? sprintf($message, ...$args) : $message;

        return $this;
    }

    /**
     * Seta erro(s) de execução
     * @param \Exception|\Error $e
     * @return void
     */
    public function addException($e): void
    {
        if ($e instanceof Exception || $e instanceof Throwable) {
            $struct = [
                'file' => $e->getFile(),
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ];

            $this->addProp('exception', $struct);
        }
    }

    /**
     * Seta o trace de propriedades do processo
     * @param string|array $prop
     * @param mixed $value
     * @param bool $group
     * @return $this
     */
    public function addProp($prop, $value = null, bool $group = false): SDKLog
    {
        if (is_array($prop) && !$group) {
            foreach ($prop as $itemProp => $itemValue) {
                $this->addProp($itemProp, $itemValue);
            }
            return $this;
        }
        $this->payload->props[$prop] = $value;
        return $this;
    }

    /**
     * Adiciona trace de métodos do log
     * @param string $name
     * @param array $arguments
     * @param array $logs
     * @return $this
     */
    public function addMethod($name, $arguments = [], $logs = []): SDKLog
    {
        $this->payload->methods[$name] = [
            'arguments' => $arguments,
            'logs' => $logs
        ];
        return $this;
    }

    /**
     * Envia ao firehose os dados pré inseridos
     * @return void
     */
    public function sendLog(): void
    {
        // se estiver mockado não envia pra aws
        if ($this->mocked) {
            print_r(json_encode($this->payload));
            return;
        }

        try {
            $firehoseClient = FirehoseClient::factory(
                [
                    'credentials' => array(
                        'key'    => $this->key,
                        'secret' => $this->secret,

                    ),
                    'version' => $this->version,
                    'region' => $this->region
                ]
            );

            $firehoseClient->putRecord([
                'DeliveryStreamName' => $this->level == "log" ? "vendala-logs" : "vendala-history",
                'Record' => [
                    'Data' => json_encode($this->payload),
                ],
            ]);
        } catch (Throwable $ex) {
        }
    }
}
