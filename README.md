# PHP JSON Server

PHP JSON Server é uma biblioteca simples para fornecer uma API REST em poucos minutos para ser utilizada em testes de _front-end_ por exemplo. 

Ela pode rodar através do servidor _build-in_ do PHP ou ser integrada a um _framework_ com bastante facilidade. Também possui um CLI para iniciar um servidor de maneira rápida e gerar os dados da API.

__* NÃO DEVE SER UTILIZADO EM PRODUÇÃO__

Inpirada na biblioteca [Zlob/php-json-server](https://github.com/Zlob/php-json-server)

## Instalação

Via composer `composer require rodrigoaramburu/php-json-server`.

Criamos um arquivo `index.php` com o seguinte código.

```php
$server = new Server([
    'database-file' => __DIR__.'/database.json',
]);

$path = $_SERVER['REQUEST_URI'];
$body = file_get_contents('php://input');
$headers = getallheaders();
$response = $server->handle($_SERVER['REQUEST_METHOD'], $path, $body, $headers);

$server->send($response);
```

Ao criar o `Server` passamos o caminho para o _json_ de dados da API. Nele definimos os dados iniciais e quais coleções a API vai ter. Veja um exemplo de `database.json`

```
{
    "embed-resources": {
        "posts" : ["comments"]
    },
    "posts": [
        {
            "id": 1,
            "title": "Lorem ipsum dolor sit amet",
            "author": "Rodrigo",
            "content": "Nunc volutpat ipsum eget sapien ornare..."
        },
        {
            "id": 2,
            "title": "Duis quis arcu mi",
            "author": "Rodrigo",
            "content": "Suspendisse auctor dolor risus, vel posuere libero..."
        }
    ],
    "comments": [
        {
            "id": 1,
            "post_id": 1,
            "comment": "Pellentesque id orci sodales, dignissim massa vel"
        },
        {
            "id": 2,
            "post_id": 2,
            "comment": "Maecenas elit dui, venenatis ut erat vitae"
        },
        {
            "id": 3,
            "post_id": 1,
            "comment": "Quisque velit tellus, tempus vitae condimentum nec"
        }
    ]
}
```

Cada propriedade do _JSON_ representa uma coleção sendo seu valor um _array_ de objetos contidos na coleção. Podemos ligar um objeto de uma coleção com de outra coleção com uma "chave estrangeira" com o formato `<coleção no singular>_id`, isto irá fazer com que ao ser recuperada o campo de "chave estrangeira" será substituido pelo resource com o id especificado. 

Para carregar todos um resource juntamente com todos os outros que tem  que tem uma chave estrangeira para ele adicionamos esta relação a entrada _embed-resources_ do arquivo de dados.

Com o _JSON_ acima a API irá nos fornecer as seguintes rotas.

Method | Url
-------|------------
GET    | /posts
GET    | /posts/1
GET    | /posts/comments
GET    | /posts/1/comments
POST   | /posts
POST   | /posts/1/comments
PUT    | /posts/1
PUT    | /posts/1/comments/3
DELETE | /posts/1
DELETE | /posts/1/comments/3


Com o `database.json` e o `index.php` podemos rodar a API com o servidor _build-in_ do _PHP_.

```shell
php -S localhost:8000 index.php
```

Também podemos integra-lo facilmente com outros _frameworks_. Veja um exemplo utilizando o _**Slim**_.

```php
use DI\Container;
use JsonServer\Server;
use Slim\Factory\AppFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$server = new Server([
    'database-file' => __DIR__.'/database.json',
]);

$app->any('/api/{path:.*}' , function(RequestInterface $request, ResponseInterface $response, $args) use($server){
     
    $path = '/'.$args['path'];
    $body = (string) $request->getBody();
    $headers = $request->getHeaders();
    $response = $server->handle($request->getMethod(), $path, $body, $headers);
    
    return $response;
});
 
$app->run();
```

* Obs.: neste caso as rotas da api serão precedidas por `/api`

Como o retorno do método `handle` é um objeto da interface `Psr\Http\Message\ResponseInterface` basta retornar para o _Slim_ construir a resposta.

## Middlewares

O servidor permite a utilização de _middlewares_, para isso basta estender a classe abstrata `JsonServer\Middlewares\Middleware` implementando o método `public function process(RequestInterface $request, Handler $handler): ResponseInterface;`

```php 
use JsonServer\Middlewares\Handler;
use JsonServer\Middlewares\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExampleMiddleware extends Middleware
{
    public function process(ServerRequestInterface $request, Handler $handler): ResponseInterface
    {
        //antes de ser processado
        $authToken = $request->getHeader("Authorization");
        
        $response = $handler->handle($request);
        
        // após ser processado 
        $response = $response->withHeader('Content-Type','application/json');
        return $response;
    }
}
```

E depois adicionar ao `$server`:

```php
$server->addMiddleware(new ExampleMiddleware());
````

### Middleware Rota Estática

Podemos criar rotas estáticas utilizando o middleware `StaticMiddleware`, ele recebe no construtor um _array_ ou o caminho para um arquivo _json_ com as rotas.

```php
$staticRoutes = new StaticMiddleware([
    "/static/route" => [
        "GET" => [
            "body" => "{\"message\": \"Uma resposta GET para o cliente \"}",
            "statusCode" => 200,
            "headers" => [
                "Content-Type" => "application/json"
            ]
        ],
        "POST" => [
            "body" => "{\"message\": \"Uma resposta POST para o cliente\"}",
            "statusCode" => 201,
            "headers" => [
                "Content-Type" => "application/json"
            ]
        ],
    ]
]);
$server->addMiddleware($staticRoutes);
```

Em vez de passar o array podemos passar um json com as configurações das rotas em um arquivo _json_ como `static.json`.

```json
{
    "/static/route": {
        "GET": {
            "body": "{\"message\": \"Uma resposta GET para o cliente \"}",
            "statusCode": 200,
            "headers": {
                "Content-Type": "application/json"
            }
        },
        "POST": {
            "body": "{\"message\": \"Uma resposta POST para o cliente\"}",
            "statusCode": 201,
            "headers": {
                "Content-Type": "application/json"
            }
        }
    }
}
```

E adicionamos passamos seu caminho ao _middleware_ 
```php
$staticRoutes = new StaticMiddleware(__DIR__.'/static.json');
$server->addMiddleware($staticRoutes);
```

## Filtros e Ordenação

Podemos filtrar um recurso por campo passando o campo e o valor como _query param_:

```
http://localhost:8000/posts?author=rodrigo
```

Podemos ordenar o resultado por um campo passado por _query param_ os parâmetros de *_sort* para o nome do campo e *_order* para o sentido (asc, desc);

```
localhost:8000/posts?_sort=author&_order=desc
```
## CLI

### Iniciando o servidor

Também é possível iniciar um servidor de forma mais simples através de comando CLI, para isso basta ter os arquivos JSON na pasta e rodar

```shell
vendor/bin/json-server start
```

Podemos passar os seguintes parâmetros:

Parâmetros         | Descrição
-------------------|------------
data-dir=PATH      | especifica o diretório contento os arquivos json como `database.json` 
port=PORT          | especifica a porta que o servidor irá rodar
--use-static-route | habilita o middelware de rotas estáticas, as rotas devem ser especificadas no arquivos `static.json`

### Gerando o database.json

Podemos gerar o arquivo de dados utilizando o seguinte comando

```shell
vendor/bin/json-server generate database resource1 resource2 ... 
```

Podemos passar os seguinte parâmentros

Parâmetros        | Descrição
------------------| --------------------
filename=FILENAME | especifica o nome do arquivo que será gravado os dados
embed=RELATIONS   | especifica as relações dos _resources_. Deve ser passado no formato: 'resourcePai[resourceFilho1,resourceFilho2]; ... '

### Gerando dados dos _Resources_

Podemos gerar os dados de um _resource_ utilizando o seguinte comando:

```shell
vendor/bin/json-server generate resources resource_name [filename=FILENAME] [fields=FIELDS_LIST]
```

Podemos passar os seguinte parâmentros

Parâmetros           | Descrição
---------------------| --------------------
filename=FILENAME    | especifica o nome do arquivo que será gravado os dados
num=NUM_OF_RESOURCES | especifica o número de _resources_ a serem criados             
fields=FIELDS_LIST   | lista de campos a serem criados no _resource_. Deve ser informado no formato. _'field.type;field.type; ...'_. O _type_ deve ser um método do lib [Faker](https://fakerphp.github.io/), com seus parâmetros(se houver) passados separados por ponto após o nome do método. Ex.: `idade.numberBetween.20.70`

### Gerando rotas estáticas

Podemos gerar o arquivo de rotas estaticas para o middleware `StaticMiddleware` com o seguinte comando:

```shell
vendor/bin/json-server generate database static [filename=FILENAME] [path=PATH] [method=METHOD] [body=BODY] [statusCode=STATUS_CODE] [headers=HEADER-LIST]
```

Parâmetros             | Descrição
-----------------------| --------------------
filename=FILENAME      | especifica o nome do arquivo que será gravado os dados
path=PATH              | path da rota
method=METHOD          | método da rota
body=BODY              | body da resposta
statusCode=STATUS_CODE | código de esta http da resposta
headers=HEADER-LIST    | lista de header da resposta. Informado no formato headers="header1=valor-header1&header2=valor-header2"