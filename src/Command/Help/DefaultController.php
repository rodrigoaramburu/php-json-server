<?php

declare(strict_types=1);

namespace JsonServer\Command\Help;

use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()->info('Available Commands');

        $this->getPrinter()->display("help  \t lista todos os comando");
        $this->getPrinter()->display("start \t inicia o servidor");
        $this->getPrinter()->newline();

        $this->getPrinter()->info('- start options');
        $this->getPrinter()->display("data-dir=<dir>     \t especifica o diretorio dos arquivos de dados json");
        $this->getPrinter()->display("--use-static-route \t habilita o middleware de rotas estaticas ");
        $this->getPrinter()->display("port=<port-number> \t especifica a porta a ser usada");

        $this->getPrinter()->newline();
        $this->getPrinter()->newline();
    }
}
