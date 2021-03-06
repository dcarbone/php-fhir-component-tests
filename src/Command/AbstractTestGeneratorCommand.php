<?php namespace FHIR\ComponentTests\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class AbstractTestGeneratorCommand
 * @package FHIR\ComponentTests\Command
 */
abstract class AbstractTestGeneratorCommand extends Command
{
    /**
     * @return string
     */
    abstract protected function getSourceClassSearchDir();

    /**
     * @return string
     */
    abstract protected function getTestClassOutputDir();

    /**
     * @return string
     */
    abstract protected function getGeneratorClassName();

    /**
     * @return string
     */
    abstract protected function getSourceClassBaseNamespace();

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $sourceClassFiles = $finder
            ->files()
            ->in($this->getSourceClassSearchDir())
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Abstract*')
            ->notName('*Interface.php');

        $outputDir = $this->getTestClassOutputDir();

        if (!is_dir($outputDir) && !(bool)@mkdir($outputDir))
            throw new \RuntimeException('Could not create test class output dir at location "'.$outputDir.'", please check permissions.');

        $generatorClass = $this->getGeneratorClassName();
        $sourceClassNamespace = $this->getSourceClassBaseNamespace();

        $progressBar = new ProgressBar($output, count($sourceClassFiles));

        foreach($sourceClassFiles as $classFile)
        {
            /** @var SplFileInfo $classFile */
            $className = $sourceClassNamespace.str_replace(array('\\', 'src/', '/', '.php'), array('/', '', '\\', ''), $classFile->getRelativePathname());
            $classReflection = new \ReflectionClass($className);

            /** @var \FHIR\ComponentTests\Generator\AbstractTestClassGenerator $generator */
            $generator = new $generatorClass($className, $classFile, $classReflection);

            file_put_contents(
                $outputDir.$generator->getTestClassName().'.php',
                $generator->generateClassCode()
            );

            $progressBar->advance(1);
        }

        $progressBar->finish();

        $output->writeln("\n\n".'Test class generation completed.');

        return 1;
    }
}