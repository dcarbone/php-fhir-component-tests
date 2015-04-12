<?php namespace FHIR\ComponentTests\Command;

use FHIR\ComponentTests\Template\ElementTestClassTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class GenerateElementTestsCommand
 * @package FHIR\ComponentTests\Command
 */
class GenerateElementTestsCommand extends Command
{
    /** @var string */
    protected $elementNamespace = '\\FHIR\\Elements\\';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('generate:element-tests')
            ->setDescription('Generate test classes for php-fhir-elements package');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $elementClassFiles = $finder
            ->files()
            ->in(FHIR_ELEMENTS_LIB_DIR)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Abstract*')
            ->notName('*Interface.php');

        $outputDir = FHIR_TEST_LIB_TEST_CLASS_DIR.'elements'.DIRECTORY_SEPARATOR;

        if (!is_dir($outputDir))
        {
            $ok = @mkdir($outputDir);
            if (!$ok)
                throw new \RuntimeException('Could not create Elements test class output dir, please check permissions.');
        }

        foreach($elementClassFiles as $classFile)
        {
            /** @var SplFileInfo $classFile */
            $className = $this->elementNamespace.str_replace(array('\\', 'src/', '/', '.php'), array('/', '', '\\', ''), $classFile->getRelativePathname());
            $classReflection = new \ReflectionClass($className);

            $template = new ElementTestClassTemplate($className, $classFile, $classReflection);

            file_put_contents(
                $outputDir.$template->getTestClassName().'.php',
                $template->generateClassCode()
            );
        }
    }

}