<?php

require './app/al.php';

use Lyco\Connectors\MsGraph;
use Lyco\Connectors\SqlServer;

$msGraph = new MsGraph();

$msGraph->listFilesInOneDriveFolder();