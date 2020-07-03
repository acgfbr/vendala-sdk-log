<?php
if (! function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if ( !array_key_exists($columnKey, $value)) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists($indexKey, $value)) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if ( ! is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}
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
     * nome da stream no kinesis firehose
     * @var array
     */
    private $streamName = array('log' => 'vendala-logs', 'history' => 'vendala-history');

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

    /**
     * Verifica se um json é valido
     * @param string $str
     * @return void
     */
    public function isJsonValid($str){
        if(!is_string($str)){
            return false;
        }
        try {
            $json = json_decode($str);
            return $json && $str != $json;
        } catch (Exception $e) {
            return false;
        }
    }

    public function initialize($mock = null)
    {
        if ($mock) {
            $this->mocked = true;
        } else {
            $this->mocked = false;
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
     * Retorna se execução do processo foi marcada com sucesso ou erro ( true | false )
     * @param bool $val
     * @return void
     */
    public function getWellExecuted()
    {
        return $this->payload->wellExecuted;
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
                $this->addMessage($item, $args);
            }
            return $this;
        }

        if(is_object($message)){
            $message = $this->raw_json_encode($message);
        }

        $this->payload->messages[] = isset($args) ? sprintf($message, $args) : $message;

        return $this;
    }

    /**
     * Adiciona trace de métodos do log
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function addMethod($name, $arguments = array())
    {
        $this->payload->methods[$name] = array(
            'arguments' => $this->raw_json_encode($arguments),
        );
        return $this;
    }

    public function array_depth(array $array)
    {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
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
        

        if($this->isJsonValid($value)){
            $this->payload->props[$prop] = $value;
        }else{
            if(!is_object($value) && !is_array($value)){
                $this->payload->props[$prop] = strval($value);
            }else{
                $this->payload->props[$prop] = $this->raw_json_encode($value);    
            }
            
        }

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
            'message' => $e->getMessage(),
        );

        $this->addProp('exception', $struct);
    }

    public function validateSendLog($k, $st)
    {
        if (!isset($k)) {
            throw new Exception($st . ' not configured.');
        }
    }

    function raw_json_encode($input, $flags = 0) {
        $fails = implode('|', array_filter(array(
            '\\\\',
            $flags & JSON_HEX_TAG ? 'u003[CE]' : '',
            $flags & JSON_HEX_AMP ? 'u0026' : '',
            $flags & JSON_HEX_APOS ? 'u0027' : '',
            $flags & JSON_HEX_QUOT ? 'u0022' : '',
        )));
        $pattern = "/\\\\(?:(?:$fails)(*SKIP)(*FAIL)|u([0-9a-fA-F]{4}))/";
        $callback = function ($m) {
            return html_entity_decode("&#x$m[1];", ENT_QUOTES, 'UTF-8');
        };
        return preg_replace_callback($pattern, $callback, json_encode($input, $flags));
    }

    /**
     * Envia ao firehose os dados pré inseridos
     * @return void
     */
    public function sendLog()
    {
        $this->validateSendLog($this->payload->logType, 'log type');
        $this->validateSendLog($this->url, 'url');
        $this->validateSendLog($this->payload->env, 'env');
        $this->validateSendLog($this->payload->level, 'level');

        $key = array_search(__FUNCTION__, array_column(debug_backtrace(), 'function'));
        $back = debug_backtrace();
        $fileCalled = $back[$key]['file'];

        $this->addProp('file',$fileCalled);

        // se estiver mockado não envia pra aws
        if ($this->mocked) {
            print_r($this->raw_json_encode($this->payload));
            return true;
        }

        try {

            $this->payload->created_at = date('Y-m-d H:i:s');

            $ch = curl_init($this->url . '/logs');

            $payload = array(
                'payload' => $this->raw_json_encode($this->payload),
                'index' => $this->streamName[$this->payload->level],
            );

            $payload = $this->raw_json_encode($payload);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            #var_dump($response);

            return true;
        } catch (Exception $ex) {
            print_r($ex->getTraceAsString());
        }

        return false;
    }
}
