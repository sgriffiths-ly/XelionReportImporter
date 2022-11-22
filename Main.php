<?php

require './app/al.php';

use Lyco\Connectors\MsGraph;
use Lyco\Connectors\SqlServer;

$destinationFolder = "C:\\temp\\";

$msGraph = new MsGraph();

$files = $msGraph->listFilesInOneDriveFolder();

foreach ($files as $k=>$v){
  if (file_put_contents($destinationFolder.$k, file_get_contents($v)))
  {
    echo "$k downloaded successfully";
  }
  else
  {
    echo "$k downloading failed.";
  }
//$msGraph->downloadOneDriveFile('01OY3CATMTQBXZZZGCENE26BVYCTHPIFCT', 'testFileName.pdf');
}
