<?php

namespace App\Command;

use App\Service\DuplicatedFilesDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:detect-duplicated-files',
    description: 'Detect and list duplicated files',
)]
class DetectDuplicatedFilesCommand extends Command
{
    private const  PUBLIC_FOLDER = 'public';
    private DuplicatedFilesDetector $duplicatedFilesDetector;

    protected function configure(): void
    {
        $this
            ->addArgument('directoryPath', InputArgument::REQUIRED, 'Path of directory to scan')
        ;
    }

    public function __construct(DuplicatedFilesDetector $duplicatedFilesDetector )
    {
        $this->duplicatedFilesDetector = $duplicatedFilesDetector;
        parent::__construct(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DuplicatedFilesDetector::setVerbose( $input->getOption('verbose'));

        $io = new SymfonyStyle($input, $output);

        $directoryPath = self::PUBLIC_FOLDER . DIRECTORY_SEPARATOR . $input->getArgument('directoryPath');

        if ($directoryPath) {
            $table = new Table($output);
            $table->setHeaders(['#', 'File 1', '#', 'File 2']);

            foreach ($this->duplicatedFilesDetector->getAllDuplicatedFiles($directoryPath) as $duplicatedFiles) {
                $table->addRow($duplicatedFiles);
            }
            $table->render();

        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
