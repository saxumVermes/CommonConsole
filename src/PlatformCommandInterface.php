<?php

namespace EclipseGc\CommonConsole;

/**
 * Interface PlatformCommandInterface
 *
 * @package EclipseGc\CommonConsole
 */
interface PlatformCommandInterface {

  /**
   * Add a platform for using within the command.
   *
   * Platforms are added in the order defined within
   * ::getExpectedPlatformOptions(). If this defines 'source' and 'destination'
   * platform options in that order, the first platform added will be the
   * source and the second will be the destination.
   *
   * @param string $alias
   *   The name of the alias used for this platform.
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   A platform to add to the command.
   */
  public function addPlatform(string $alias, PlatformInterface $platform) : void ;

  /**
   * Get the platform by its internal command name.
   *
   * If you wanted the source platform as documented within
   * ::getExpectedPlatformOptions(), you would call this command with the name
   * value of 'source'.
   *
   * $this->getPlatform('source');
   *
   * @param string $name
   *   The internal platform name.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface|null
   */
  public function getPlatform(string $name) : ?PlatformInterface ;

  /**
   * The list of necessary platforms for this command named by intent.
   *
   * For example, if you had a source and a destination platform for your
   * command, you might do the following:
   *
   *  return [
   *    'source',
   *    'destination',
   *  ];
   *
   * @return string[]
   */
  public static function getExpectedPlatformOptions() : array ;

}
