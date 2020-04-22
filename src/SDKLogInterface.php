<?php

namespace Vendala\Logs;

interface SDKLogInterface
{
    /**
     * Seta a access key da aws
     * @param string $key
     * @return void
     */
    public function setKey($key): void;

    /**
     * Seta a secret key da aws
     * @param string $secret
     * @return void
     */
    public function setSecret($secret): void;


    /**
     * Seta se a execução do processo foi sucesso ou erro ( true | false )
     * @param bool $val
     * @return void
     */
    public function setWellExecuted(int $id): void;

    /**
     * Seta o tipo de log ( history | log )
     * @param string $val
     * @return void
     */
    public function setLevel($val): void;

    /**
     * Seta o environment ( local, dev, prod )
     * @param string $env
     * @return void
     */
    public function setEnvironment($env): void;

    /**
     * Seta a aplicação ( vendala, simplifique, questions, lambdared )
     * @param string $app
     * @return void
     */
    public function setApp($app): void;

    /**
     * Seta a universal_id ( primary key )
     * @param string $uid
     * @return void
     */
    public function setUid($uid): void;

    /**
     * Seta a tabela referencia
     * @param string $table
     * @return void
     */
    public function setTable($table): void;

    /**
     * Seta o banco referencia
     * @param string $database
     * @return void
     */
    public function setDatabase($database): void;

    /**
     * Seta o trace de mensagens do processo
     * @param string|array $message
     * @return $this
     */
    public function addMessage($message, ...$args): SDKLog;

    /**
     * Seta erro(s) de execução
     * @param \Exception|\Error $e
     * @return void
     */
    public function addException($e): void;

    /**
     * Seta o trace de propriedades do processo
     * @param string|array $prop
     * @param mixed $value
     * @param bool $group
     * @return $this
     */
    public function addProp($prop, $value = null, bool $group = false): SDKLog;

    /**
     * Adiciona trace de métodos do log
     * @param string $name
     * @param array $arguments
     * @param array $logs
     * @return $this
     */
    public function addMethod($name, $arguments = [], $logs = []): SDKLog;

    /**
     * Valida os dados mínimos pra enviar ao firehose
     * @param object $k
     * @param string $st
     * @return $this
     */
    public function validateSendLog($k, $st): void;

    /**
     * Envia ao firehose os dados pré inseridos
     * @return void
     */
    public function sendLog(): bool;
}
