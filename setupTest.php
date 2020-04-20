<?php

require __DIR__ . '/vendor/autoload.php';

$payload = [
    'sku_id' => '123123',
    'sku' => 'TESTESDK',
    'shop_name' => 'testeantonio',
    'enterprise_id' => 1,
    'old' => '123',
    'new' => '122',


];

$log = (new Vendala\Logs\SDKLog(true));

$log->setKey('adsasd');
$log->setSecret('bbbb');

$log->setLevel('log');
$log->setEnvironment('local');
$log->setApp('simplifique');
$log->setUid('3333');
$log->setTable('skus');
$log->setDatabase('simplifique');

$log->addMessage('começou a execução do processo de estoque')
    ->addMessage('estoque antigo: ' . $payload['old'])
    ->addMessage('cliente alterou estoque para: ' . $payload['new'])
    ->addMethod('splitEstoque();', ['sku_id' => 123, 'old' => 1, 'new' => 2], ['maoe' => 'vempraca', 'asdfadf' => 'ggdfgfdg'])
    ->addMethod('auditEstoque();', ['teste' => 'testado'], ['mas testou mesmo?' => 'sim'])
    ->addMethod('blablabla();', ['foo' => 'bar'], ['passou por aqui'])
    ->addProp($payload);

$log->setWellExecuted(true);

$log->sendLog();
