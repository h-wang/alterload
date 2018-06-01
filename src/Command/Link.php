<?php

namespace Alterload\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Alterload\Loader;
use RuntimeException;

class Link extends Command
{
    protected $projectPath;
    protected $configFileName = '.alterload.ini';

    protected function configure()
    {
        $this
            ->setName('link')
            ->setDescription('Make links to the development repository')
            ->setHelp('This command helps to make symlinks to the alterloading libraries instead of only loading classes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $links = Loader::getLinks();
        if (!$links) {
            $output->writeln('<error>Cound not find config file or config file is empty</>');
            exit;
        }
        $projectPath = realpath($links['projectPath']);
        if (!$projectPath) {
            $output->writeln('<error>Invalid project path: '.$links['projectPath'].'</>');
        }
        if (!$links['links']) {
            $output->writeln('<comment>Config file found but no links entry found.</>');
        }
        foreach ($links['links'] as $link) {
            $path = realpath($link['path']);
            $target = realpath($link['target']);
            // safety: $path must be in the vendor dir of the project
            if ($path && $target && strpos($path, $projectPath.'/vendor/') === 0) {
                $process = new Process('rm -rf '.$path.' && ln -s '.$target.' '.$path);
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
                $output->writeln('<info>Success! Class [ '.$link['class'].' ]</> replaced: <comment>'.$path.'</> -> <info>'.$target.'</>');
            } else {
                $output->writeln('<error>Path error: '.$path.' -> '.$target.'</> in project: '.$projectPath);
            }
        }
    }
}
