<?php

declare(strict_types=1);

namespace JsonServer\Command\Help;

use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    private int $padding = 25;

    public function handle(): void
    {
        $this->getPrinter()->info('Comando disponpiveis');

        $this->getPrinter()->out(str_pad('help', $this->padding), 'success');
        $this->getPrinter()->out('lista todos os comando');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('start', $this->padding), 'success');
        $this->getPrinter()->out('inicia o servidor');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('generate database', $this->padding), 'success');
        $this->getPrinter()->out('cria um arquivo de dados json');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('generate resource', $this->padding), 'success');
        $this->getPrinter()->out('gera n resources e adiciona no arquivo de dados json');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('generate static', $this->padding), 'success');
        $this->getPrinter()->out('gera/adiciona rota no arquivo de rotas estaticas');
        $this->getPrinter()->newline();

        $this->helpStart();
        $this->helpGenerateDatabase();
        $this->helpGenerateResource();
        $this->helpGenerateStatic();
        $this->getPrinter()->newline();
    }

    public function helpStart(): void
    {
        $this->getPrinter()->info('# start');
        $this->getPrinter()->info('Uso: start [options]');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('data-dir=DIR', $this->padding), 'success');
        $this->getPrinter()->out('especifica o diretório dos arquivos de dados json');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('port=PORT-NUMBER', $this->padding), 'success');
        $this->getPrinter()->out('especifica a porta a ser usada');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('--use-static-route', $this->padding), 'success');
        $this->getPrinter()->out('habilita o middleware de rotas estaticas');
        $this->getPrinter()->newline();

        $this->getPrinter()->newline();
        $this->getPrinter()->newline();
    }

    public function helpGenerateDatabase(): void
    {
        $this->getPrinter()->info('# generate database');
        $this->getPrinter()->info('Uso: generate database resource1 resource2 ... [filename=FILENAME] [embed=RELATIONS]');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('filename=FILENAME', $this->padding), 'success');
        $this->getPrinter()->out('especifica o nome do arquivo que será gerado o arquivo de dados json.');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('embed', $this->padding), 'success');
        $this->getPrinter()->out("lista de relacionamentos entre os resources. Deve ser passado no formato: 'resourcePai[resourceFilho1,resourceFilho2]; ... '");
        $this->getPrinter()->newline();

        $this->getPrinter()->newline();
        $this->getPrinter()->newline();
    }

    public function helpGenerateResource(): void
    {
        $this->getPrinter()->info('# generate resource');
        $this->getPrinter()->info('Uso: generate resources resource_name [filename=FILENAME] [num=NUM_OF_RESOURCES] [fields=FIELD.TYPE;...]');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('filename=FILENAME', $this->padding), 'success');
        $this->getPrinter()->out('especifica o nome do arquivo dados json no qual será adicionando o recurso.');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('num=NUM_OF_RESOURCES', $this->padding), 'success');
        $this->getPrinter()->out('especifica o número de recursos a serem criados.');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('fields', $this->padding), 'success');
        $this->getPrinter()->out("lista de campos que o rescurso irá ter. Utiliza formato: 'FIELD_NAME.TYPE', sendo field_name o nome do campo e type uma das funções da lib Faker. Se a função tiver parametros eles são passado separados após o nome da função('idade.numberBetween.20.70') ");
        $this->getPrinter()->newline();
    }

    public function helpGenerateStatic(): void
    {
        $this->getPrinter()->info('# generate static');
        $this->getPrinter()->info('Uso: generate database static [filename=FILENAME] [path=PATH] [method=METHOD] [body=BODY] [statusCode=STATUS_CODE] [headers=HEADER-LIST]');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('filename=FILENAME', $this->padding), 'success');
        $this->getPrinter()->out('especifica o nome do arquivo dados json no qual será adicionando o recurso.');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('path=PATH', $this->padding), 'success');
        $this->getPrinter()->out('path da rota');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('method=METHOD', $this->padding), 'success');
        $this->getPrinter()->out('método da rota');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('body=BODY', $this->padding), 'success');
        $this->getPrinter()->out('body da resposta');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('statusCode=STATUS_CODE', $this->padding), 'success');
        $this->getPrinter()->out('código de esta http da resposta');
        $this->getPrinter()->newline();

        $this->getPrinter()->out(str_pad('headers=HEADER-LIST', $this->padding), 'success');
        $this->getPrinter()->out('lista de header da resposta. Informadado no formato headers="header1|valor-header1|header2|valor-header2"');
        $this->getPrinter()->newline();
    }
}
