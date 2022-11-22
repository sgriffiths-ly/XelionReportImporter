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

  public function __construct()
  {

    try {
      $this->getAccessToken();
    } catch (Exception|GuzzleException $e) {
      echo 'There was an issue getting a MsGraph Access Token.';
    }

  }

  /**
   * @return string
   * @throws JsonException
   * @throws GuzzleException
   */
  private function getAccessToken(): void
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
    $this->accessToken = $token->access_token;
  }

  /**
   * @throws GraphException
   * @throws GuzzleException
   */
  public function listFilesInOneDriveFolder()
  {
    //https://graph.microsoft.com/v1.0/users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/root/children
    //xelionreports-sp@lyco.co.uk = 65527d49-ffcd-4113-9df8-62901fc9bf84

    $graph = new Graph();
    $graph
        ->setBaseUrl('https://graph.microsoft.com/')
        ->setApiVersion('v1.0')
        ->setAccessToken($this->accessToken);

    $response = $graph->createRequest('GET', 'users/65527d49-ffcd-4113-9df8-62901fc9bf84/drive/root/children')
        ->execute();

    $status = $response->getStatus();
    $body = $response->getBody();
    print_r($body);
  }
}