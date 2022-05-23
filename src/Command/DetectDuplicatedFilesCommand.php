<?php

namespace App\Command;

use App\Service\DuplicatedFilesDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete duplicate files')
        ;
    }

    public function __construct(DuplicatedFilesDetector $duplicatedFilesDetector )
    {
        $this->duplicatedFilesDetector = $duplicatedFilesDetector;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DuplicatedFilesDetector::setVerbose( $input->getOption('verbose'));
        DuplicatedFilesDetector::setDeleteDuplicatedFiles( $input->getOption('delete'));;

        $io = new SymfonyStyle($input, $output);

        $directoryPath = self::PUBLIC_FOLDER . DIRECTORY_SEPARATOR . $input->getArgument('directoryPath');

        if ($directoryPath) {
            $table = new Table($output);
            $table->setHeaders(['File 1', 'Duplication']);

            foreach ($this->duplicatedFilesDetector->getAllDuplicatedFiles($directoryPath) as $duplicatedFiles) {
                $listOfFiles = "";
                foreach ($duplicatedFiles[1] as $duplicatedFile) {
                    $listOfFiles .= sprintf("📁 %s \n", $duplicatedFile);
                }
                $table->addRow([$duplicatedFiles[0], $listOfFiles]);
                $table->addRow(new TableSeparator());
            }
            $table->render();
        }

        $io->success('');

        return Command::SUCCESS;
    }
}
