<?php

namespace EclipseGc\CommonConsole\Platform;

use Consolidation\Config\Config;
use Consolidation\Config\ConfigInterface;
use EclipseGc\CommonConsole\Event\Traits\PlatformArgumentInjectionTrait;
use EclipseGc\CommonConsole\PlatformDependencyInjectionInterface;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;

/**
 * Class DockerStackPlatform.
 *
 * @package EclipseGc\CommonConsole\Platform
 */
class DockerStackPlatform extends PlatformBase implements PlatformSitesInterface, PlatformDependencyInjectionInterface {

  use PlatformArgumentInjectionTrait;

  /**
   * Services added to the platform.
   */
  public const CONFIG_SERVICES = 'docker.services';

  /**
   * The path to the docker-compose.yml file, from where the command could run.
   */
  public const CONFIG_COMPOSE_FILE_PATH = 'docker.compose_file';

  /**
   * DockerStackPlatform constructor.
   *
   * @param \Consolidation\Config\ConfigInterface $config
   * @param \EclipseGc\CommonConsole\ProcessRunner $runner
   * @param \EclipseGc\CommonConsole\Platform\PlatformStorage $storage
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(ConfigInterface $config, ProcessRunner $runner, PlatformStorage $storage, EventDispatcherInterface $dispatcher) {
    parent::__construct($config, $runner, $storage);
    $this->dispatcher = $dispatcher;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function getPlatformId(): string {
    return 'DockerStack';
  }
  
  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, ConfigInterface $config, ProcessRunner $runner, PlatformStorage $storage): self {
    return new static($config, $runner, $storage, $container->get('event_dispatcher'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getQuestions() {
    return [
      static::CONFIG_SERVICES => new Question("Docker Service Names (spearate them with colon): "),
      static::CONFIG_COMPOSE_FILE_PATH => new Question("Location of docker-compose file: "),
    ];
  }
  
  /*
   * {@inheritDoc}
   *
   * This implementation relies on the hostname. Overwrite the method for
   * custom site retrieving logic.
   */
  public function getPlatformSites(): array {
    $services = explode(',', $this->get(static::CONFIG_SERVICES));
    $location = basename($this->get(static::CONFIG_COMPOSE_FILE_PATH));
    $sites = [];
    foreach ($services as $service) {
      $uriProcess = Process::fromShellCommandline("docker exec {$location}_{$service}_1 /bin/bash -c 'source /var/www/html/.sites; echo $$service'");
      $uriProcess->run();
      $uri = trim($uriProcess->getOutput());
      $sites[trim($service)] = $uri;
    }

    return $sites;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Command $command, InputInterface $input, OutputInterface $output) : void {
    $location = basename($this->get(static::CONFIG_COMPOSE_FILE_PATH));
    $services = explode(',', $this->get(static::CONFIG_SERVICES));
    $sites = $this->getPlatformSites();
    $args = $this->dispatchPlatformArgumentInjectionEvent($input, $sites, $command->getName());
    foreach ($services as $service) {
      $site = $sites[$service];
      $output->writeln("Executed on '$service'");
      $process = Process::fromShellCommandline("docker exec {$location}_{$service}_1 ./vendor/bin/commoncli --uri $site {$args[$site]->__toString()}");
      $this->runner->run($process, $this, $output);
    }
  }

}
