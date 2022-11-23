<?php

namespace Lyco\Connectors;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lyco\Etc\Credentials;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;

class MsGraph
{
  private string $accessToken;
  private Graph $graph;

  public function __construct()
  {
    $this->graph = new Graph();
    $this->graph
        ->setBaseUrl('https://graph.microsoft.com/')
        ->setApiVersion('v1.0')
        ->setAccessToken($this->getAccessToken());

  }

  /**
   * @throws JsonException
   * @throws GuzzleException
   */
  private function getAccessToken(): string
  {
    $guzzle = new Client();
    $url = 'https://login.microsoftonline.com/1c3cf8fd-8f09-48f3-915c-3cc89f4ebfc9/oauth2/token?api-version=v1.0';
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

  /**
   * @throws GraphException
   * @throws GuzzleException
   */
  public function listFilesInOneDriveFolder(): array
  {
    //https://graph.microsoft.com/v1.0/users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/root/children
    //xelionreports-sp@lyco.co.uk = 65527d49-ffcd-4113-9df8-62901fc9bf84

    $response = $this->graph->createRequest('GET', '/users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/root/children/XelionReports/children/')
        ->execute();

    $status = $response->getStatus();
    $body = $response->getBody();

    $listOfFiles = [];
    foreach ($body['value'] as $item) {
      if ($item['size'] > 0) {
        $listOfFiles[] = ['name' => $item['name'], 'id' => $item['id'], 'downloadUrl' => $item['@microsoft.graph.downloadUrl']];
      }
    }

    //print_r($body);
    return $listOfFiles;
  }


  /**
   * @throws GraphException
   * @throws GuzzleException
   */
  public function moveOneDriveFileToProcessedFolder(string $fileId, string $filename)
  {
    //https://graph.microsoft.com/v1.0/users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/root/children
    //xelionreports-sp@lyco.co.uk = 65527d49-ffcd-4113-9df8-62901fc9bf84

    $response = $this->graph->createRequest('PATCH', '/users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/items/' . $fileId)
        ->attachBody('{"parentReference": {"id": "01OY3CATLQSTIB4734ZNBIL5KQ4TVJWWH7"}, "name": "' . $filename . '"}')
        ->execute();

    $status = $response->getStatus();
    $body = $response->getBody();
    print_r($body);

    return $status === 200;
  }


}