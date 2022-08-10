# PHP JSON Server

PHP JSON Server é uma biblioteca simples para fornecer uma API REST em poucos minutos para ser utilizada em testes de _front-end_ por exemplo. 

Ela pode rodar através do servidor _build-in_ do PHP ou ser integrada a um _framework_ com bastante facilidade. Também possui um CLI para iniciar um servidor de maneira rápida.

__* NÃO DEVE SER UTILIZADO EM PRODUÇÃO__

Inpirado na biblioteca [Zlob/php-json-server](https://github.com/Zlob/php-json-server)

## Instalação

Via composer `composer require rodrigoaramburu/php-json-server`.

Criamos um arquivo para `index.php` com o seguinte código.

```php
$server = new Server(__DIR__.'/db.json');

$path = $_SERVER['REQUEST_URI'];
$body = file_get_contents('php://input');
$headers = getallheaders();
$response = $server->handle($_SERVER['REQUEST_METHOD'], $path, $body, $headers);

$server->send($response);
```

Ao criar o `Server` passamos o caminho para o _json_ de dados da API. Nele definimos os dados iniciais e quais coleções a API vai ter. Veja um exemplo de `db.json`

```
{
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

Cada propriedade do _JSON_ representa uma coleção sendo seu valor um _array_ de objetos contidos na coleção. Podemos ligar um objeto de uma coleção com de outra coleção com "chave estrangeira" com o formato `<coleção no singular>_id`.

Com o _JSON_ acima a API irá nos fornecer as seguintes rotas.

Method | Url
-------|------------
GET    | /posts
GET    | /posts/1
GET    | /posts/comments
POST   | /posts
PUT    | /posts/1
DELETE | /posts/1


Com o `db.json` e o `index.php` podemos rodar a API com o servidor _build-in_ do _PHP_.

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

$server = new Server(__DIR__. '/db.json');

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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ExampleMiddlewere extends Middleware
{
    public function process(RequestInterface $request, Handler $handler): ResponseInterface
    {
        //antes de ser processado pelo servidor
        $authToken = $request->getHeader("Authorization");

        $response = $handler->handle($request);

        // após ser processado pelo servidor
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

## CLI

Também é possível iniciar um servidor de forma mais simples através de comando CLI, para isso basta ter os arquivos JSON na pasta e rodar

```shell
vendor/bin/json-server start
```

Podemos passar os seguintes parâmetros:

Parâmetros         | Descrição
-------------------|------------
data-dir=\<path\>    | especifica o diretório contento os arquivos json como `db.json` 
--use-static-route | habilita o middelware de rotas estáticas, as rotas devem ser especificadas no arquivos `static.json`
port=\<port\>        | especifica a porta que o servidor irá rodar