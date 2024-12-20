<?php
declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\MOM\TransportInterface;
use App\Application\Product\Import\ImportInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

#[AsCommand(
    name: 'app:import',
    description: 'Ein moderner Import-ImportWizard für den Verarbeitungs- und Versandprozess von DTOs.'
)]
class ImportWizard extends Command
{
    public function __construct(
        private ImportInterface         $import,
        private TransportInterface      $transport,
        private LoggerInterface         $logger,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('directory', InputArgument::REQUIRED, 'Pfad zum Import-Verzeichnis');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directory = $input->getArgument('directory');

        $io->title('🧙 Willkommen beim Import-ImportWizard 🧙');

        if (!is_dir($directory)) {
            $io->error(sprintf('Der angegebene Pfad "%s" ist kein Verzeichnis.', $directory));
            return Command::FAILURE;
        }

        $stopwatch = new Stopwatch();
        $stopwatch->start('import-process');

        $io->section('🔍 Starte den Import-Prozess...');
        $progressBar = $io->createProgressBar();
        $progressBar->start();

        try {
            $products = $this->import->processImport($directory);
            $progressBar->finish();
            $io->newLine(2);
            $this->logger->info(sprintf('Import erfolgreich für Verzeichnis: %s', $directory));
        } catch (\Exception $e) {
            $progressBar->finish();
            $io->newLine(2);
            $this->logger->error($e->getMessage());
            $io->error(sprintf('Fehler beim Import: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $event = $stopwatch->stop('import-process');
        $duration = $event->getDuration() / 1000;

        $this->logger->notice(json_encode([
            'directory' => $directory,
            'execution_time' => $duration,
            'products_count' => count($products),
        ], JSON_THROW_ON_ERROR));

        $this->displayDetailedStatistics($io, count($products), $duration);

        if ($io->confirm('Möchten Sie die Produkte jetzt an RabbitMQ senden?', false)) {
            $this->sendProducts($products, $io);
        } else {
            $io->note('Die Produkte wurden nicht versendet.');
        }

        return Command::SUCCESS;
    }

    public function sendProducts(array $products, SymfonyStyle $io): void
    {
        $io->section('📤 Sende Produkte an RabbitMQ...');

        $count = count($products);
        $progressBar = $io->createProgressBar($count);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $allSent = true;
        foreach ($products as $product) {
            if (!$this->transport->dispatch($product)) {
                $io->error(sprintf('Fehler beim Senden der Nachricht für Produkt: %s', $product->abstractSku));
                $this->logger->error(sprintf('Fehler beim Senden der Nachricht für Produkt: %s', $product->abstractSku));
                $allSent = false;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if ($allSent) {
            $io->success('Alle Produkte erfolgreich an RabbitMQ gesendet.');
            $this->logger->notice('Alle Produkte erfolgreich an RabbitMQ gesendet.');
        } else {
            $io->warning('Einige Produkte konnten nicht gesendet werden.');
            $this->logger->notice('Einige Produkte konnten nicht gesendet werden.');
        }
    }

    private function displayDetailedStatistics(SymfonyStyle $io, int $productsCount, float $executionTime): void
    {
        $io->section('📊 Detaillierte Statistiken');

        $averageSpeed = $executionTime > 0
            ? sprintf('%.2f Produkte/Sekunde', $productsCount / $executionTime)
            : 'N/A (Ausführungszeit ist 0)';

        $io->table(
            ['Metrik', 'Wert'],
            [
                ['Verarbeitete Produkte', $productsCount],
                ['Ausführungszeit', sprintf('%.2f Sekunden', $executionTime)],
                ['Durchschnittliche Verarbeitungsgeschwindigkeit', $averageSpeed],
            ]
        );
    }
}
