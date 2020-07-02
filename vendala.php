<?php

require_once('./src/sdk_log_legacy.php');


$payload = array(
    'sku_id' => '123123423',
    'sku' => 'TESTESDK',
    'shop_name' => 'testeantonio',
    'enterprise_id' => array('a' => 'á á á á á á'),
    'old' => '123',
    'new' => '122',
  
  
);

$log = new SdkLogComponent();
$log->initialize();
$log->setUrl('asdasdasd');
$log->setAction('manual');
$log->setLogType('VRAU');
$log->setLevel('log');
$log->setEnvironment('local');
$log->setApp('venda-la-2017');
$log->setUid(123123);
$log->setTable('TABELA DO CLIENTE');
$log->setDatabase('DB DO CLIENTE');

$log->addMessage('começou a execução do processo de estoque')
->addMessage('weeeeeeeeeeeeeeeeeeeeeee')    
->addMessage('estoque antigo: ' . 123)
    ->addMessage('cliente alterou estoque para: ' . 456)
    ->addMethod('splitEstoque();', array('sku_id' => 123, 'old' => 123, 'new' => 456), array('maoe' => 'vempraca', 'asdfadf' => 'ggdfgfdg'))
    ->addMethod('auditEstoque();', array('teste' => 'testado'), array('mas testou mesmo?' => 'sim'))
    ->addMethod('blablabla();', array('foo' => 'bar'), array('passou por aqui'))
    ->addProp($payload);

$log->setWellExecuted(true);

$response = $log->sendLog();


var_dump($response);