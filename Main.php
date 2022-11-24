<?php

require_once __DIR__ . '/vendor/autoload.php';

use Lyco\Connectors\MsGraph;
use Lyco\Connectors\SqlServer;

$downloadFolder = "\\\\lyco_fs01\\shared\\Information Technology\\Data uploads\\Xelion Reports\\";
$archiveFolder = "\\\\lyco_fs01\\shared\\Information Technology\\Data uploads\\Xelion Reports\\Archive\\";
$reportFileNameFragment = 'Daily Phoneline Report For SQL';
$sql = new SqlServer();
$msGraph = new MsGraph();

if ($msGraph->failedToLoad) {
  exit(1);
}
M365Part($msGraph, $downloadFolder, $reportFileNameFragment);

//loop through files on shared drive, process them, archive them
$dir = new DirectoryIterator($downloadFolder);
foreach ($dir as $fileInfo) {
  //if the file, is not a directory and name contains the correct name fragment
  if (!$fileInfo->isDot() && !$fileInfo->isDir() && strpos($fileInfo->getFilename(), $reportFileNameFragment) !== false) {

    processCsv($downloadFolder . $fileInfo->getFilename(), $sql);

    //rename to move file to Archive folder.
    rename($downloadFolder . $fileInfo->getFilename(), $archiveFolder . $fileInfo->getFilename());
  }
}

function processCsv(string $path, SqlServer $sql)
{
  $firstDataLine = 8;
  $data = [];
  $handle = fopen($path, 'rb'); // open in readonly mode
  while (($row = fgetcsv($handle)) !== false) {
    $data[] = $row;
  }
  fclose($handle);

  $date = explode(' ', $data[2][10])[0];
  $arrayEnd = count($data) - 1;
  for ($i = $firstDataLine; $i <= $arrayEnd; $i++) {
    if ($data[$i][0] !== 'Total') {
      //echo $data[$i][$headers['Phone Line']] . PHP_EOL;
      $sql->addData($date, $data[$i]);
    }
  }
//echo $date;
}


function M365Part(MsGraph $msGraph, string $downloadFolder, string $reportFileNameFragment): void
{


//list files in OneDrive
  $files = $msGraph->listFilesInOneDriveFolder();

//download files from OneDrive and move them to Processed folder on OneDrive
  foreach ($files as $file) {
    if (strpos($file['name'], $reportFileNameFragment) !== false) {
      if (file_put_contents($downloadFolder . $file['name'], file_get_contents($file['downloadUrl']))) {
        echo "{$file['name']} downloaded successfully";
        $msGraph->moveOneDriveFileToProcessedFolder($file['id'], $file['name']);
      } else {
        echo "{$file['name']} downloading failed.";
      }
    }
  }
}

