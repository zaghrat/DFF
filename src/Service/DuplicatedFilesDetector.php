<?php

namespace App\Service;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DuplicatedFilesDetector
{
    private static int $count = 0 ;
    private static bool $verbose = false ;
    private static bool $deleteDuplicatedFiles = false ;
    private array $listOfDuplicatedFiles = [];
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    private function getFiles(string $dir, string $excludedFile=null): Finder
    {
        $finder = new Finder();
        if (!$excludedFile) {
            $finder->files()->in($dir)->sortByChangedTime();
        } else {
            $finder->files()->in($dir)->sortByChangedTime()->notName($excludedFile);
        }

        return $finder;
    }

    public function getAllDuplicatedFiles(string $dirPath): \Iterator
    {
        if (!$this->filesystem->exists($dirPath)) {
            throw new \RuntimeException(sprintf('Directory [%s] not found!!!', $dirPath));
        }

        /** @var SplFileInfo $file */
        foreach ($this->getFiles($dirPath) as $file) {
            if (!$file->isFile() || in_array($file->getPathname() , $this->listOfDuplicatedFiles, true)) {
                continue;
            }

            $duplicatedFiles = $this->checkDuplication($dirPath, $file);
            if (!count($duplicatedFiles)) {
                continue;
            }

            if (self::$deleteDuplicatedFiles) {
                foreach ($duplicatedFiles[1] as $duplicatedFilename) {
                    if ($this->filesystem->exists($duplicatedFilename)) {
                        $this->filesystem->remove($duplicatedFilename);
                    }
                }
            }

            yield $duplicatedFiles;
        }
    }

    private function checkDuplication(string $dir, SplFileInfo $initialFile): array
    {
        $duplicatedFiles = [];
        /** @var SplFileInfo $file */
        foreach ($this->getFiles($dir, $initialFile->getFilename()) as $file) {
            if ($this->compareFiles($initialFile, $file)) {
                $duplicatedFiles[0] = $initialFile->getPathname(); // original file
                $duplicatedFiles[1][] = $file->getPathname(); // copy
                $this->listOfDuplicatedFiles[] = $file->getPathname();
                $this->listOfDuplicatedFiles[] =  $initialFile->getPathname();
            }
        }

        return $duplicatedFiles;
    }

    /**
     * @param bool $verbose
     */
    public static function setVerbose(bool $verbose): void
    {
        self::$verbose = $verbose;
    }

    /**
     * @param bool $deleteDuplicatedFiles
     */
    public static function setDeleteDuplicatedFiles(bool $deleteDuplicatedFiles): void
    {
        self::$deleteDuplicatedFiles = $deleteDuplicatedFiles;
    }

    private function compareFiles(SplFileInfo $firstFile, SplFileInfo $secondFile): bool
    {
        if (self::$verbose) {
            echo sprintf("%s -- %s \n", $firstFile->getFilename(), $secondFile->getFilename());
        }

        if (!$firstFile->isFile() || !$secondFile->isFile()) {
            return false;
        }

        if ($firstFile->getType() !== $secondFile->getType()) {
            return false;
        }

        if ($firstFile->getSize() !== $secondFile->getSize()) {
            return false;
        }

        if ($firstFile->getExtension() !== $secondFile->getExtension()) {
            return false;
        }

        if (md5_file($firstFile->getPathname()) !== md5_file($secondFile->getPathname())) {
            return false;
        }

        return true;
    }
}