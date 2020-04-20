# Venda.la LOGS

#### SDK em PHP para o micro serviço de logs.

Este pacote visa padronizar o envio para o micro serviço de logs.

`Documentação`

## ![image info](mslog.png)

Explicando:

```
Nossos sistemas precisam guardar logs diariamente.
Como não temos um lugar centralizado, este projeto visa centralizar e guardar tudo.

Nossas aplicações vão utilizar este SDK para enviar ao elasticsearch com o kinesis de intermediário.

Lifecycle de envio resumido:

1 - sistema venda.la tem necessidade de log.
2 - sistema venda.la chama sdk.
3 - sistema venda.la envia log a partir do sdk.
4 - sdk se comunica com o kinesis da aws enviando o payload e dados da aplicação.
5 - kinesis abre a stream e envia ao elasticsearch em lotes.
6 - em paralelo, é guardado num s3 o dado bruto para data mining futuro.
7 - fim do ciclo de vida.

Lifecycle de consumo resumido:

1 - aplicação necessita de log(usuário clicou num histórico de estoque).
2 - aplicação chama um api gateway (http://apivendala.amazon-us-east1.blablabla/logs/log/estoque/123/123/123)
3 - o api gateway invoca um lambda que consulta no elasticsearch buscando dados a partir do query string informado no api gateway e retorna os dados para quem invocou.
4 - com a posse dos dados, o api gateway retorna a resposta do lambda para a aplicação consumir e mostrar ao usuário.
5 - fim do ciclo de vida.
```

Observações:

```
Existem dois tipos de log level:

1 - history | logs que o usuário final vê, como alteração de estoque e preço.
2 - log | logs que auditam as rotinas como envio de estoque, sincronia diária, alteração de preço, split de estoque.

Tempo de expiração do log por level:
------------------------------------
Level      |    TTL     |   Desc
history    |    n/a     |   logs que o usuário final vê, como alteração de estoque e preço.
log        |    90d     |   logs que auditam as rotinas como envio de estoque, sincronia diária, alteração de preço, split de estoque.
------------------------------------

Funções disponíveis no SDK:
------------------------------------
Nome            |   Desc            |   Exemplo
setKey          |   set access key  |   ASDASDASDASD
setSecret       |   set secret key  |   DSADHASUDHAU
setWellExecuted |   sucesso na exec |   true | false
setLevel        |   tipo de log     |   history | log
setEnvironment  |   tipo de env     |   local | dev | prod
setApp          |   aplicação       |   vendala | simplifique | lambdared | pickingpack | questions
setUid          |   coluna ref      |   universal_id (primary key)
setTable        |   tabela logada   |   skus
setDatabase     |   db logado       |   vendala2020
addMessage      |   adiciona msgs   |   "passou aqui 123"
addException    |   adiciona excep  |   throw new Exception('EXCP')
addProp         |   adiciona props  |   $prop1 = []; $prop2 = stdClass;
addMethod       |   adiciona funcs  |   func1(); func2(); func3();
sendLog         |   função de envio |   envia o log ao kinesis
------------------------------------

Padrão de nomenclatura:
lowerCamelCase.
```

Testes disponíveis:

```
Em construção.
( testes unitários e de integração ).
```

Exemplo de json salvo no elasticsearch:

```javascript
{
  "messages": [
    "começou a execução do processo de estoque",
    "estoque antigo: 123",
    "cliente alterou estoque para: 122"
  ],
  "methods": {
    "splitEstoque();": {
      "arguments": {
        "sku_id": 123,
        "old": 1,
        "new": 2
      },
      "logs": {
        "maoe": "vempraca",
        "asdfadf": "ggdfgfdg"
      }
    },
    "auditEstoque();": {
      "arguments": {
        "teste": "testado"
      },
      "logs": {
        "mas testou mesmo?": "sim"
      }
    },
    "blablabla();": {
      "arguments": {
        "foo": "bar"
      },
      "logs": [
        "passou por aqui"
      ]
    }
  },
  "props": {
    "sku_id": "123123",
    "sku": "TESTESDK",
    "shop_name": "testeantonio",
    "enterprise_id": 1,
    "old": "123",
    "new": "122"
  },
  "level": "log",
  "env": "local",
  "app": "simplifique",
  "uid": "3333",
  "table": "skus",
  "database": "simplifique",
  "wellExecuted": true
}
```
