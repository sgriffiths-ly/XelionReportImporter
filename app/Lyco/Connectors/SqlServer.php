<?php

namespace Lyco\Connectors;

use Exception;
use Lyco\Etc\Credentials;
use PDO;

class SqlServer
{
  private string $host = 'nav_2k16_sql';
  private string $dbname = 'Lyco History';

  private PDO $pdo;

  public function __construct()
  {
    //Set DSN (Data Source Name)
    $dsn = "sqlsrv:server=$this->host;database=$this->dbname;";
    $this->pdo = new PDO($dsn, Credentials::$sqlUsername, Credentials::$sqlPassword);
    $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }


  public function getOrders(): array
  {
    $data = [];
    $sql = 'SELECT * FROM [3dBinPackingOrders] WHERE NOT EXISTS(SELECT DISTINCT [order_no] FROM [3dBinPackingPackAShipmentToteResult])';
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([]);
      while ($row = $stmt->fetch()) {
        $data[$row->order_no][] = $row;
      }
    } catch (Exception $e) {
      error_log('There was an issue retrieving orders from the database.' . PHP_EOL . $e->getMessage() . PHP_EOL . 'SQL: ' . $sql . PHP_EOL);
    }

    return $data;
  }


  public function writePackAShipmentResult(string $orderNo, string $responseBody, string $numberOfTotesUsed, string $numberNotPacked): bool
  {
    $success = false;
    $sql = 'INSERT INTO [3dBinPackingPackAShipmentToteResult] 
    ([order_no],[result_data],[tote_boxes_used],[items_not_packed]) 
    VALUES (:orderNo, :resultData, :toteBoxesUsed, :itemsNotPacked)';
    try {
      $stmt = $this->pdo->prepare($sql);
      $success = $stmt->execute(['orderNo' => $orderNo, 'resultData' => $responseBody, 'toteBoxesUsed' => $numberOfTotesUsed, 'itemsNotPacked' => $numberNotPacked]);
    } catch (Exception $e) {
      error_log('There was an issue writing a PackAShipment result to the database. Order: ' . $orderNo . PHP_EOL . $e->getMessage() . PHP_EOL . 'SQL: ' . $sql . PHP_EOL);
    }
    return $success;
  }

  public function addComLogRequest(string $orderNo, string $endpoint, string $requestData): ?string
  {
    $rowId = null;
    $sql = 'INSERT INTO [3dBinPackingComLog] ([order_no], [endpoint], [request_datetime], [request_data]) 
                    OUTPUT INSERTED.row_id
                    VALUES (:order_no, :endpoint, GETDATE(), :request_data)';
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(['order_no' => $orderNo, 'endpoint' => $endpoint, 'request_data' => $requestData]);
      while ($row = $stmt->fetch()) {
        $rowId = $row->row_id;
      }
    } catch (Exception $e) {
      error_log('There was an issue writing a ComLog Request to the database. Order: ' . $orderNo . PHP_EOL . $e->getMessage() . PHP_EOL . 'SQL: ' . $sql . PHP_EOL);
    }
    return $rowId;

  }

  public function addComLogResponse(int $rowId, int $httpCode, string $responseData): bool
  {
    $success = false;
    $sql = 'UPDATE [3dBinPackingComLog] SET [response_http_code]=:response_http_code, [response_datetime]=GETDATE(), [response_data]= :response_data WHERE [row_id] = :row_id';
    try {
      $stmt = $this->pdo->prepare($sql);
      $success = $stmt->execute(['response_http_code' => $httpCode, 'response_data' => $responseData, 'row_id' => $rowId]);
    } catch (Exception $e) {
      error_log('There was an issue writing a ComLog Response to the database.' . PHP_EOL . $e->getMessage() . PHP_EOL . 'SQL: ' . $sql . PHP_EOL);
    }
    return $success;
  }


}