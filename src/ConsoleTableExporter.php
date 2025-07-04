<?php

declare(strict_types=1);

namespace Bakame\Aide\Profiler;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function is_float;
use function json_encode;

use const JSON_PRETTY_PRINT;

final class ConsoleTableExporter implements Exporter
{
    public function __construct(private readonly OutputInterface $output = new ConsoleOutput())
    {
    }

    public function exportProfilingData(ProfilingResult|ProfilingData $profilingData): void
    {
        if ($profilingData instanceof ProfilingResult) {
            $profilingData = $profilingData->profilingData;
        }

        $table = $this->createTable();
        $table->addRow($this->profilingDataToRow($profilingData));
        $table->render();
    }

    public function exportProfiler(Profiler $profiler, ?string $label = null): void
    {
        $input = null === $label ? $profiler : $profiler->getAll($label);
        $table = $this->createTable();
        foreach ($input as $profilingData) {
            $table->addRow($this->profilingDataToRow($profilingData));
        }
        $table->addRow(new TableSeparator());
        $metrics = $profiler->average($label);
        $row = [
            '<fg=green>Average</>',
            DurationUnit::format($metrics->cpuTime, 3),
            DurationUnit::format($metrics->executionTime, 3),
            MemoryUnit::format($metrics->memoryUsage, 1),
            MemoryUnit::format($metrics->realMemoryUsage, 1),
            MemoryUnit::format($metrics->peakMemoryUsage, 1),
            MemoryUnit::format($metrics->realPeakMemoryUsage, 1),
        ];
        $table->addRow($row);
        $table->render();
    }

    private function createTable(): Table
    {
        return (new Table($this->output))
            ->setHeaders([
            'Label',
            'CPU Time',
            'Exec Time',
            'Memory',
            'Real Mem',
            'Peak Mem',
            'Real Peak',
        ]);
    }

    /**
     * @return list<string>
     */
    private function profilingDataToRow(ProfilingData $profilingData): array
    {
        $metrics = $profilingData->metrics;

        return [
            $profilingData->label,
            DurationUnit::format($metrics->cpuTime, 3),
            DurationUnit::format($metrics->executionTime, 3),
            MemoryUnit::format($metrics->memoryUsage, 1),
            MemoryUnit::format($metrics->realMemoryUsage, 1),
            MemoryUnit::format($metrics->peakMemoryUsage, 1),
            MemoryUnit::format($metrics->realPeakMemoryUsage, 1),
        ];
    }

    public function exportSnapshot(Snapshot $snapshot): void
    {
        $table = (new Table($this->output))
            ->setHeaders(['Metric', 'Value'])
            ->setRows([
                ['Timestamp', $snapshot->timestamp->format('Y-m-d\TH:i:s.uP')],
                ['Memory Usage', MemoryUnit::format($snapshot->memoryUsage, 3)],
                ['Real Memory Usage', MemoryUnit::format($snapshot->realMemoryUsage, 3)],
                ['Peak Memory Usage', MemoryUnit::format($snapshot->peakMemoryUsage, 3)],
                ['Real Peak Memory Usage', MemoryUnit::format($snapshot->realPeakMemoryUsage, 3)],
                ['CPU', (string) json_encode($snapshot->cpu, JSON_PRETTY_PRINT)],
            ]);

        $table->render();
    }

    public function exportEnvironment(Environment $environment): void
    {
        $memoryLimit = $environment->memoryLimit;
        if (is_float($memoryLimit) && $memoryLimit > 0) {
            $memoryLimit = MemoryUnit::format($memoryLimit);
        }

        $table = (new Table($this->output))
            ->setHeaders(['Property', 'Value'])
            ->setRows([
                ['Operating System', $environment->os],
                ['OS Family', $environment->osFamily],
                ['Hostname', $environment->hostname],
                ['Architecture', $environment->machine],
                ['PHP Integer Size', $environment->phpIntSize],
                ['PHP Architecture', $environment->phpArchitecture],
                ['SAPI', $environment->sapi],
                ['Memory Limit', $memoryLimit],
                ['CPU Cores', $environment->cpuCores],
                ['Disk Size', MemoryUnit::format($environment->totalDisk, 1)],
                ['Free Disk Space', MemoryUnit::format($environment->freeDisk, 1)],
            ]);

        $table->render();
    }
}
