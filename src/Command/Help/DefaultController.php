<?php

declare(strict_types=1);

namespace JsonServer\Command\Help;

use JsonServer\Utils\TagFilter;
use Minicli\Command\CommandController;
use Minicli\Output\Helper\TableHelper;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()->info('Comando Disponíveis');

        $table = new TableHelper();
        $table->addRow(["\t<success>help</success>",'Lista todos os comandos']);
        $table->addRow(["\t<success>start</success>",'Inicia o servidor']);
        $table->addRow(["\t<success>generate database</success>",'Cria um arquivo de dados json']);
        $table->addRow(["\t<success>generate resource</success>",'Gera n resources e adiciona no arquivo de dados json']);
        $table->addRow(["\t<success>generate static</success>",'Gera/Adiciona rota no arquivo de rotas estaticas']);

        $this->getPrinter()->rawOutput($table->getFormattedTable(new TagFilter()));
        $this->getPrinter()->newline();

        $this->helpStart();
        $this->helpGenerateDatabase();
        $this->helpGenerateResource();
        $this->helpGenerateStatic();
        $this->getPrinter()->newline();
    }

    public function helpStart(): void
    {
        $this->getPrinter()->info('- start');
        $this->getPrinter()->info('Uso: start [options]');

        $table = new TableHelper();
        $table->addRow(["\t<success>data-dir=DIR</success>", 'especifica o diretório dos arquivos de dados json']);
        $table->addRow(["\t<success>port=PORT-NUMBER</success>", 'especifica a porta a ser usada']);
        $table->addRow(["\t<success>--use-static-route</success>", 'habilita o middleware de rotas estaticas']);

        $this->getPrinter()->rawOutput($table->getFormattedTable(new TagFilter()));
        $this->getPrinter()->newline();
    }

    public function helpGenerateDatabase(): void
    {
        $this->getPrinter()->info('- generate database');
        $this->getPrinter()->info('Uso: generate database resource1 resource2 ... [filename=FILENAME] [embed=RELATIONS]');

        $table = new TableHelper();
        $table->addRow(["\t<success>filename=FILENAME</success>", 'Especifica o nome do arquivo que será gerado o arquivo de dados json.']);
        $table->addRow(["\t<success>embed=EMBED_LIST</success>", "Lista de relacionamentos entre os resources. Formato: 'resourcePai[resourceFilho1,resourceFilho2]; ... '"]);

        $this->getPrinter()->rawOutput($table->getFormattedTable(new TagFilter()));
        $this->getPrinter()->newline();
    }

    public function helpGenerateResource(): void
    {
        $this->getPrinter()->info('- generate resource');
        $this->getPrinter()->info('Uso: generate resources resource_name [filename=FILENAME] [num=NUM_OF_RESOURCES] [fields=FIELD=TYPE&...]');

        $table = new TableHelper();
        $table->addRow(["\t<success>filename=FILENAME</success>", 'Especifica o nome do arquivo dados json no qual será adicionando o recurso.']);
        $table->addRow(["\t<success>num=NUM_OF_RESOURCES</success>", 'Especifica o número de recursos a serem criados.']);
        $table->addRow(["\t<success>fields=FIELD_LIST</success>", "Lista de campos a serem criados no resource. Deve ser informado no formato. 'field=type&field=type& ...'. O type deve ser um método do lib Faker, com seus parâmetros(se houver) passados separados por ponto após o nome do método. Ex.: idade=numberBetween.20.70"]);

        $this->getPrinter()->rawOutput($table->getFormattedTable(new TagFilter()));
        $this->getPrinter()->newline();
    }

    public function helpGenerateStatic(): void
    {
        $this->getPrinter()->info('- generate static');
        $this->getPrinter()->info('Uso: generate database static [filename=FILENAME] [path=PATH] [method=METHOD] [body=BODY] [statusCode=STATUS_CODE] [headers=HEADER-LIST]');

        $table = new TableHelper();
        $table->addRow(["\t<success>filename=FILENAME</success>", 'Especifica o nome do arquivo dados json no qual será adicionando o recurso.']);
        $table->addRow(["\t<success>path=PATH</success>", 'Path da rota']);
        $table->addRow(["\t<success>method=METHOD</success>", 'Método da rota']);
        $table->addRow(["\t<success>body=BODY</success>", 'Body da resposta']);
        $table->addRow(["\t<success>statusCode=STATUS_CODE</success>", 'Código HTTP da resposta']);
        $table->addRow(["\t<success>headers=HEADER-LIST</success>", 'Lista de header da resposta. Informadado no formato headers="header1=valor-header1&header2=valor-header2']);

        $this->getPrinter()->rawOutput($table->getFormattedTable(new TagFilter()));
        $this->getPrinter()->newline();
    }
}
