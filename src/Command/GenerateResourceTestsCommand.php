<?php namespace FHIR\ComponentTests\Command;

use FHIR\ComponentTests\Template\ResourceTestClassTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class GenerateResourceTestsCommand
 * @package FHIR\ComponentTests\Command
 */
class GenerateResourceTestsCommand extends Command
{
    /** @var string */
    protected $resourcesNamespace = '\\FHIR\\Resources\\';

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this
            ->setName('generate:resource-tests')
            ->setDescription('Generate test classes for php-fhir-resources package');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $resourceClassFiles = $finder
            ->files()
            ->in(FHIR_RESOURCES_LIB_DIR)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Abstract*')
            ->notName('*Interface.php');

        $outputDir = FHIR_TEST_LIB_TEST_CLASS_DIR.'resources'.DIRECTORY_SEPARATOR;

        if (!is_dir($outputDir))
        {
            $ok = @mkdir($outputDir);
            if (!$ok)
                throw new \RuntimeException('Could not create Resources test class output dir, please check permissions.');
        }

        foreach($resourceClassFiles as $classFile)
        {
            /** @var SplFileInfo $classFile */
            $className = $this->resourcesNamespace.str_replace(array('\\', 'src/', '/', '.php'), array('/', '', '\\', ''), $classFile->getRelativePathname());
            $classReflection = new \ReflectionClass($className);

            $template = new ResourceTestClassTemplate($className, $classFile, $classReflection);

            file_put_contents(
                $outputDir.$template->getTestClassName().'.php',
                $template->generateClassCode()
            );
        }
    }
}