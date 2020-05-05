<?php

namespace Vendala\Logs;

use Exception;
use stdClass;
use Throwable;

class SDKLog implements SDKLogInterface
{
    private $url;

    /**
     * determina quem fez a açao (manual, callback, job)
     * @var string
     */
    private $action;

    /**
     * nome da stream no kinesis firehose
     * @var array
     */
    private $streamName = ['log' => 'vendala-logs', 'history' => 'vendala-history'];

    /**
     * tipo do log (alteraçao de preço, alteraçao de estoque, historico de pedidos)
     * @var string
     */
    private $logType;

    /**
     * determina se está mockado ou não para testes
     * @var boolean
     */
    private $mocked;

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
     * Seta a url do api gateway
     * @param string $url
     * @return void
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * Seta a action
     * @param string $lType
     * @return void
     */
    public function setAction($action): void
    {
        $this->payload->action = $action;
    }

    /**
     * Seta o tipo de log
     * @param string $lType
     * @return void
     */
    public function setLogType($lType): void
    {
        $this->payload->logType = $lType;
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
     * Valida os dados mínimos pra enviar ao firehose
     * @param object $k
     * @param string $st
     * @return $this
     */
    public function validateSendLog($k, $st): void
    {
        if (!isset($k)) {
            throw new Exception($st . ' not configured.');
        }
    }
    /**
     * Envia ao firehose os dados pré inseridos
     * @return void
     */
    public function sendLog(): bool
    {
        $this->validateSendLog($this->payload->logType, 'log type');
        $this->validateSendLog($this->key, 'aws access key');
        $this->validateSendLog($this->secret, 'aws secret key');
        $this->validateSendLog($this->payload->env, 'env');
        $this->validateSendLog($this->payload->level, 'level');

        // se estiver mockado não envia pra aws
        if ($this->mocked) {
            #print_r(json_encode($this->payload));
            return true;
        }

        try {

            $this->payload->created_at = date('Y-m-h H:i:s');

            $ch = curl_init($this->url . '/logs');

            $payload = [
                'payload' => json_encode($this->payload),
                'index' => $this->streamName[$this->payload->level]
            ];

            $payload = json_encode($this->payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);

            return true;
        } catch (Exception $ex) {
            print_r($ex->getTraceAsString());
        }

        return false;
    }
}
