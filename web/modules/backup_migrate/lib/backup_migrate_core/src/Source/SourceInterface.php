<?php

/**
 * @file
 * Contains \BackupMigrate\Core\Source\SourceInterface.
 */

namespace BackupMigrate\Core\Source;

use BackupMigrate\Core\Plugin\PluginInterface;
use BackupMigrate\Core\File\BackupFileReadableInterface;

/**
 * Provides an interface defining a backup source.
 */
interface SourceInterface extends PluginInterface {
  /**
   * Export this source to the given temp file. This should be the main
   * back up function for this source.
   *
   * @return \BackupMigrate\Core\File\BackupFileReadableInterface $file
   *    A backup file with the contents of the source dumped to it..
   */
  public function exportToFile();

  /**
   * Import to this source from the given backup file. This is the main restore
   * function for this source.
   *
   * @param \BackupMigrate\Core\File\BackupFileReadableInterface $file
   *    The file to read the backup from. It will not be opened for reading
   */
  public function importFromFile(BackupFileReadableInterface $file);

}
