<?php
declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\Logger\LoggerInterface;
use App\Application\MOM\TransportInterface;
use App\Application\Product\Import\ImportInterface;
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
    private ImportInterface $import;
    private TransportInterface $transport;
    private LoggerInterface $logger;

    public function __construct(ImportInterface $importFacade, TransportInterface $messageDispatcher, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->import = $importFacade;
        $this->transport = $messageDispatcher;

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
            $result = $this->import->processImport($directory);
            $progressBar->finish();
            $io->newLine(2);
            $this->logger->logSuccess(sprintf('Import erfolgreich für Verzeichnis: %s', $directory));
        } catch (\Exception $e) {
            $progressBar->finish();
            $io->newLine(2);
            $this->logger->logException($e);
            $io->error(sprintf('Fehler beim Import: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $abstractCount = count($result['abstract_products']);
        $concreteCount = count($result['concrete_products']);

        $event = $stopwatch->stop('import-process');
        $duration = $event->getDuration() / 1000;

        $this->logger->logStatistics([
            'directory' => $directory,
            'abstract_products' => $abstractCount,
            'concrete_products' => $concreteCount,
            'execution_time' => $duration
        ]);

        $io->success(sprintf('Import abgeschlossen: %d Abstract Products, %d Concrete Products in %.2f Sekunden.',
            $abstractCount,
            $concreteCount,
            $duration
        ));

        $this->displayDetailedStatistics($io);

        if ($io->confirm('Möchten Sie die DTOs jetzt an RabbitMQ senden?', false)) {
            $this->sendMessages($result, $io);
        } else {
            $io->note('Die DTOs wurden nicht versendet.');
        }

        $this->logger->writeStatistics();

        return Command::SUCCESS;
    }

    private function sendMessages(array $result, SymfonyStyle $io): void
    {
        $io->section('📤 Sende DTOs an RabbitMQ...');

        $abstractSent = $this->sendDTOs($result['abstract_products'], $io, 'Abstract Products');
        $concreteSent = $this->sendDTOs($result['concrete_products'], $io, 'Concrete Products');

        if ($abstractSent && $concreteSent) {
            $io->success('Alle DTOs erfolgreich an RabbitMQ gesendet.');
            $this->logger->logSuccess('Alle DTOs erfolgreich an RabbitMQ gesendet.');
        } else {
            $io->warning('Einige DTOs konnten nicht gesendet werden.');
            $this->logger->logError('Einige DTOs konnten nicht gesendet werden.');
        }
    }

    private function sendDTOs(array $DTOs, SymfonyStyle $io, string $type): bool
    {
        $count = count($DTOs);
        $progressBar = $io->createProgressBar($count);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $allSent = true;
        foreach ($DTOs as $dto) {
            if (!$this->transport->dispatch($dto)) {
                $io->error(sprintf('Fehler beim Senden der Nachricht für %s DTO: %s', $type, $dto->getSku()));
                $this->logger->logError(sprintf('Fehler beim Senden der Nachricht für %s DTO: %s', $type, $dto->getSku()));
                $allSent = false;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);
        return $allSent;
    }

    private function displayDetailedStatistics(SymfonyStyle $io): void
    {
        $stats = $this->logger->getStatistics();

        $io->section('📊 Detaillierte Statistiken');

        $io->table(
            ['Metrik', 'Wert'],
            [
                ['Verarbeitetes Verzeichnis', $stats['directory']],
                ['Abstract Products', $stats['abstract_products']],
                ['Concrete Products', $stats['concrete_products']],
                ['Ausführungszeit', sprintf('%.2f Sekunden', $stats['execution_time'])],
                ['Warnungen', $stats['warnings']],
                ['Fehler', $stats['errors']],
            ]
        );

        $totalProducts = $stats['abstract_products'] + $stats['concrete_products'];
        $productsPerSecond = $totalProducts / $stats['execution_time'];

        $io->text([
            sprintf('Durchschnittliche Verarbeitungsgeschwindigkeit: %.2f Produkte/Sekunde', $productsPerSecond),
            sprintf('Fehlerrate: %.2f%%', ($stats['errors'] / $totalProducts) * 100),
        ]);

        if ($stats['warnings'] > 0 || $stats['errors'] > 0) {
            $io->warning('Es gab Warnungen oder Fehler während des Imports. Bitte überprüfen Sie die Logdateien für weitere Details.');
        }
    }
}
