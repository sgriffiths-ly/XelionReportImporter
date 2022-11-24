<?php

namespace Lyco\Connectors;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lyco\Etc\Credentials;
use Microsoft\Graph\Graph;

class MsGraph
{
  private const TENANT_ID = '1c3cf8fd-8f09-48f3-915c-3cc89f4ebfc9';
  private const XELION_USER_ID = '65527d49-ffcd-4113-9df8-62901fc9bf84'; //xelionreports-sp@lyco.co.uk
  private Graph $graph;
  public bool $failedToLoad = false;

  public function __construct()
  {
    try {

      $this->graph = new Graph();
      $this->graph
          ->setBaseUrl('https://graph.microsoft.com/')
          ->setApiVersion('v1.0')
          ->setAccessToken($this->getAccessToken());
    } catch (Exception|GuzzleException $e) {
      $this->failedToLoad = true;
      error_log('Unable to initialise MS Graph' . PHP_EOL . $e->getMessage());
    }

  }

  /**
   * @throws JsonException
   * @throws GuzzleException
   */
  private function getAccessToken(): string
  {
    $guzzle = new Client();
    $url = 'https://login.microsoftonline.com/' . self::TENANT_ID . '/oauth2/token?api-version=v1.0';
    $token = json_decode(
        $guzzle->post(
            $url,
            [
                'form_params' => [
                    'client_id' => Credentials::$msGraphClientId,
                    'client_secret' => Credentials::$msGraphSecret,
                    'resource' => 'https://graph.microsoft.com/',
                    'grant_type' => 'client_credentials',
                ],
            ]
        )->getBody()->getContents(),
        false,
        512,
        JSON_THROW_ON_ERROR
    );
    return $token->access_token;
  }


  public function listFilesInOneDriveFolder(): array
  {
    $listOfFiles = [];
    try {
      $response = $this->graph->createRequest('GET', '/users/' . self::XELION_USER_ID . '/drive/root/children/XelionReports/children/')
          ->execute();

      //$status = $response->getStatus();
      $body = $response->getBody();

      foreach ($body['value'] as $item) {
        if ($item['size'] > 0) {
          $listOfFiles[] = ['name' => $item['name'], 'id' => $item['id'], 'downloadUrl' => $item['@microsoft.graph.downloadUrl']];
        }
      }
    } catch (Exception|GuzzleException $e) {
      error_log('Unable to list files in OneDrive folder' . PHP_EOL . $e->getMessage());
    }
    return $listOfFiles;
  }


  public function moveOneDriveFileToProcessedFolder(string $fileId, string $filename): bool
  {
    $status = null;
    try {
      $response = $this->graph->createRequest('PATCH', '/users/' . self::XELION_USER_ID . '/drive/items/' . $fileId)
          ->attachBody('{"parentReference": {"id": "01OY3CATLQSTIB4734ZNBIL5KQ4TVJWWH7"}, "name": "' . $filename . '"}')
          ->execute();

      $status = $response->getStatus();
      //$body = $response->getBody();
    } catch (Exception|GuzzleException $e) {
      error_log('Unable to move a file in OneDrive folder' . PHP_EOL . 'Filename: ' . $filename . PHP_EOL . $e->getMessage());
    }

    return $status === '200';
  }


}