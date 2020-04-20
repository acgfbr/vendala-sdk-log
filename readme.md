# Venda.la LOGS

#### SDK para o micro serviço de logs.

Este pacote visa padronizar o envio para o micro serviço de logs.


`Documentação`
## ![image info](mslog.png)

Explicando:
```
Nossos sistemas precisam guardar logs diariamente.
Como não temos um lugar centralizado, este projeto visa centralizar e guardar tudo.

Nossas aplicações vão utilizar este SDK para enviar ao elasticsearch com o kinesis de intermediário.

Lifecycle de envio resumido:
1 - sistema venda.la tem necessidade de log
2 - sistema venda.la chama sdk
3 - sistema venda.la envia log a partir do sdk
4 - sdk se comunica com o kinesis da aws enviando o payload e dados da aplicação
5 - kinesis abre a stream e envia ao elasticsearch em lotes
6 - em paralelo, é guardado num s3 o dado bruto para data mining futuro.
7 - fim do ciclo de vida

Lifecycle de consumo resumido:

1 - aplicação necessita de log(usuário clicou num histórico de estoque)
2 - aplicação chama um api gateway (http://apivendala.amazon-us-east1.blablabla/logs/log/estoque/123/123/123)
3 - o api gateway invoka um lambda que consulta no elasticsearch buscando dados a partir do query string informado no api gateway e retorna os dados para quem invocou.
4 - com a posse dos dados, o api gateway retorna a resposta do lambda para a aplicação consumir e mostrar ao usuário.
```

Observações:
```
Existem dois tipos de log level:
1 - history | logs que o usuário final vê, como alteração de estoque e preço.
2 - log | logs que auditam as rotinas como envio de estoque, sincronia diária, alteração de preço, split de estoque.

Tempo de expiração do log por level:
------------------------------------
Level      |    TTL
history    |    n/a
log        |    90d
------------------------------------

Funções disponíveis no SDK:
------------------------------------
Nome            |    Desc
history     |    n/a
log        |    90d
------------------------------------

Padrão de nomenclatura:
lowerCamelCase
```