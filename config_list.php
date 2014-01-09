<?php

$conf_list = array();

foreach (new DirectoryIterator('config') as $file) {
  if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'php') {
    $conf_list[] = $file->getFilename();
  }
}

$current_id = 0;
if (!isset($conf_list[$current_id])) {
  $current_id = 0;
}
$current = $conf_list[$current_id];
