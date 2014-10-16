<?php
namespace MJS\TopSort\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BenchmarkCommand extends Command
{
    /**
     * @var Table
     */
    protected $table;

    /**
     * @var ProgressBar
     */
    protected $process;

    protected function configure()
    {
        $this
            ->setName('benchmark')
            ->addArgument('count', InputArgument::OPTIONAL, 'Count', 1000)
        ;
    }

    protected function track(callable $fn)
    {
        $start = microtime(true);
        gc_collect_cycles();
        $lastMemory = memory_get_usage();

        $fn();

        $className = explode('\\', get_class($fn[0]));

        $this->table->addRow(
            [
                array_pop($className),
                sprintf('%11sb', number_format(memory_get_usage() - $lastMemory)),
                sprintf('%6.4fs', (microtime(true) - $start)),
            ]
        );

        $this->process->advance();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->testSimpleCount($input->getArgument('count'), $output);
        $this->testGroupCount($input->getArgument('count'), $output);
//        $this->testGroupCount(10000, $output);
//        $this->testSimpleCount(10000, $output);
    }

    protected function testGroupCount($count, $output)
    {
        $elements = [];
        for ($i = 0; $i < $count/3; $i++) {
            $elements['car' . $i] = ['car', ['brand' . $i]];
            $elements['owner' . $i] = ['owner', ['brand' . $i, 'car' . $i]];
            $elements['brand' . $i] = ['brand', []];
        }
        $this->test($elements, ['GroupedFixedArraySort', 'GroupedArraySort', 'GroupedStringSort'], $output);
    }

    protected function testSimpleCount($count, $output)
    {
        $elements = [];
        for ($i = 0; $i < $count/3; $i++) {
            $elements['car' . $i] = ['brand' . $i];
            $elements['owner' . $i] = ['brand' . $i, 'car' . $i];
            $elements['brand' . $i] = [];
        }
        $this->test($elements, ['FixedArraySort', 'ArraySort', 'StringSort'], $output);
    }

    protected function test($elements, $classes, OutputInterface $output)
    {
        $this->table = new Table($output);
        $this->table->setHeaders(array('Implementation', 'Memory', 'Duration'));
        $output->writeln(sprintf('<info>%d elements</info>', count($elements)));

        $this->process = new ProgressBar($output, 3);
        $this->process->start();

        if (50000 < count($elements)) {
            $blacklist = ['GroupedFixedArraySort', 'GroupedArraySort'];
        } else {
            $blacklist = [];
        }

        foreach ($classes as $class) {
            if (in_array($class, $blacklist)) {
                $this->table->addRow([$class, '--', '--']);
                continue;
            };
            $class = 'MJS\TopSort\Implementations\\' . $class;
            $sorted = new $class($elements);
            $this->track([$sorted, 'doSort']);
        }

        $this->process->finish();
        $output->writeln('');
        $this->table->render($output);
    }
}