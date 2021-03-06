<?php

namespace EclipseGc\CommonConsole\EventSubscriber\ConsoleCommand;

use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\FindAliasEvent;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AliasFinder
 *
 * @package EclipseGc\CommonConsole\EventSubscriber\ConsoleCommand
 */
class AliasFinder implements EventSubscriberInterface {

  const ALIAS_PATTERN = '%^@?([a-zA-Z0-9_-]+)(\.[a-zA-Z0-9_-]+)?(\.[a-zA-Z0-9_-]+)?$%';

  /**
   * AliasFinder constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  protected $dispatcher;

  /**
   * AliasFinder constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConsoleEvents::COMMAND] = ['onConsoleCommand', 100];
    return $events;
  }

  /**
   * Finds alias platforms on which to invoke commands.
   *
   * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
   *   The console command event.
   *
   * @throws \ReflectionException
   */
  public function onConsoleCommand(ConsoleCommandEvent $event) {
    $input = $event->getInput();
    $output = $event->getOutput();
    $command = $event->getCommand();
    $tokens = $this->getTokens($input);
    array_shift($tokens);
    $i = 0;
    foreach ($tokens as $token) {
      if (preg_match(self::ALIAS_PATTERN, $token)) {
        $alias = substr($token, 1);
        $findAliasEvent = new FindAliasEvent($alias, $event);
        $this->dispatcher->dispatch(CommonConsoleEvents::ALIAS_FIND, $findAliasEvent);
        if (!$findAliasEvent->getPlatform()) {
          $output->writeln("<error>" . sprintf("Alias by name %s not found. Please check your available aliases and try again.", $alias) . "</error>");
          $event->disableCommand();
          $event->stopPropagation();
          return;
        }
        elseif ($command instanceof PlatformCommandInterface) {
          $command->addPlatform($alias, $findAliasEvent->getPlatform());
          continue;
        }
        else {
          $findAliasEvent->getPlatform()->execute($command, $output);
          $output->writeln("$alias");
          $event->disableCommand();
        }
      }
    }
  }

  /**
   * Extracts tokens from the input object.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *
   * @return string[]
   *   The array of tokens passed to this command.
   * @throws \ReflectionException
   */
  protected function getTokens(InputInterface $input) {
    $tokens = new \ReflectionProperty(ArgvInput::class, 'tokens');
    $tokens->setAccessible(TRUE);
    return $tokens->getValue($input);
  }

}
