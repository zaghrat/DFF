<?php

namespace App\Service;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DuplicatedFilesDetector
{
    private static int $count = 0 ;
    private static bool $verbose = false ;
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    private function getFiles(string $dir, string $excludedFile=null): Finder
    {
        $finder = new Finder();
        if (!$excludedFile) {
            $finder->files()->in($dir);
        } else {
            $finder->files()->in($dir)->notName($excludedFile);
        }

        return $finder;
    }

    private function getDirectories(string $dir): Finder
    {
        $finder = new Finder();
        $finder->directories()->in($dir);

        return $finder;
    }

    public function getAllDuplicatedFiles(string $dirPath): \Iterator
    {
        if (!$this->filesystem->exists($dirPath)) {
            throw new \RuntimeException(sprintf('Directory [%s] not found!!!', $dirPath));
        }

        /** @var SplFileInfo $file */
        foreach ($this->getFiles($dirPath) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $duplicatedFiles = $this->checkDuplication($dirPath, $file);
            if (!count($duplicatedFiles)) {
                continue;
            }

            sleep(2);
            yield $duplicatedFiles;
        }

        foreach ($this->getDirectories($dirPath) as $directory) {
            $this->getAllDuplicatedFiles($directory->getPathname());
        }
    }

    private function checkDuplication(string $dir, SplFileInfo $initialFile): array
    {
        /** @var SplFileInfo $file */
        foreach ($this->getFiles($dir, $initialFile->getFilename()) as $file) {
            if ($this->compareFiles($initialFile, $file)) {
                return [
                    ++self::$count,
                    $initialFile->getFilename(),
                    self::$count,
                    $file->getFilename()
                ];
            }
        }

        return [];
    }

    /**
     * @param bool $verbose
     */
    public static function setVerbose(bool $verbose): void
    {
        self::$verbose = $verbose;
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