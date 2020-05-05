<?php

class SdkLogComponent extends Object
{
  /**
   * Nome do componente.
   *
   * @var string
   */
  public $name = 'SdkLog';

  public $components = array();

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
  private $streamName = array('log' => 'vendala-logs', 'history' => 'vendala-history');

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

  public function initialize($mock = null)
  {
    if ($mock) {
      $this->mocked = true;
    }

    $this->payload = new stdClass;

    $this->payload->messages = array();
    $this->payload->methods = array();
    $this->payload->props = array();
  }


  /**
   * Seta a url do api gateway
   * @param string $url
   * @return void
   */
  public function setUrl($url)
  {
    $this->url = $url;
  }

  /**
   * Seta a action
   * @param string $lType
   * @return void
   */
  public function setAction($action)
  {
    $this->payload->action = $action;
  }

  /**
   * Seta o tipo de log
   * @param string $lType
   * @return void
   */
  public function setLogType($lType)
  {
    $this->payload->logType = $lType;
  }

  /**
   * Seta se a execução do processo foi sucesso ou erro ( true | false )
   * @param bool $val
   * @return void
   */
  public function setWellExecuted($val)
  {
    $this->payload->wellExecuted = $val;
  }


  /**
   * Seta o tipo de log ( history | log )
   * @param string $val
   * @return void
   */
  public function setLevel($val)
  {
    $this->payload->level = $val;
  }

  /**
   * Seta o environment ( local, dev, prod )
   * @param string $env
   * @return void
   */
  public function setEnvironment($env)
  {
    $this->payload->env = $env;
  }

  /**
   * Seta a aplicação ( vendala, simplifique, questions, lambdared )
   * @param string $app
   * @return void
   */
  public function setApp($app)
  {
    $this->payload->app = $app;
  }

  /**
   * Seta a universal_id ( primary key )
   * @param string $uid
   * @return void
   */
  public function setUid($uid)
  {
    $this->payload->uid = $uid;
  }

  /**
   * Seta a tabela referencia
   * @param string $table
   * @return void
   */
  public function setTable($table)
  {
    $this->payload->table = $table;
  }


  /**
   * Seta o banco referencia
   * @param string $database
   * @return void
   */
  public function setDatabase($database)
  {
    $this->payload->database = $database;
  }

  /**
   * Seta o trace de mensagens do processo
   * @param string|array $message
   * @return $this
   */
  public function addMessage($message, $args = array())
  {
    if (is_array($message)) {
      foreach ($message as $item) {
        $this->addMessage($item,$args);
      }
      return $this;
    }

    $this->payload->messages[] = isset($args) ? sprintf($message, $args) : $message;

    return $this;
  }

  
  /**
   * Adiciona trace de métodos do log
   * @param string $name
   * @param array $arguments
   * @param array $logs
   * @return $this
   */
  public function addMethod($name, $arguments = array(), $logs = array())
  {
    $this->payload->methods[$name] = array(
      'arguments' => $arguments,
      'logs' => $logs
    );
    return $this;
    
  }


  /**
   * Seta o trace de propriedades do processo
   * @param string|array $prop
   * @param mixed $value
   * @param bool $group
   * @return $this
   */
  public function addProp($prop, $value = null, $group = false)
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
   * Seta erro(s) de execução
   * @param \Exception|\Error $e
   * @return void
   */
  public function addException($e)
  {
      $struct = array(
        'file' => $e->getFile(),
        'code' => $e->getCode(),
        'message' => $e->getMessage()
      );

      $this->addProp('exception', $struct);
  }

  public function validateSendLog($k, $st)
    {
        if (!isset($k)) {
            throw new Exception($st . ' not configured.');
        }
    }

  /**
   * Envia ao firehose os dados pré inseridos
   * @return void
   */
  public function sendLog()
  {
    $this->validateSendLog($this->payload->logType, 'log type');
    $this->validateSendLog($this->key, 'aws access key');
    $this->validateSendLog($this->secret, 'aws secret key');
    $this->validateSendLog($this->payload->env, 'env');
    $this->validateSendLog($this->payload->level, 'level');

    // se estiver mockado não envia pra aws
    if ($this->mocked) {
      print_r(json_encode($this->payload));
      return true;
    }

    try {

      $this->payload->created_at = date('Y-m-h H:i:s');

      $ch = curl_init($this->url . '/logs');

      $payload = array(
        'payload' => json_encode($this->payload),
        'index' => $this->streamName[$this->payload->level]
      );

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
