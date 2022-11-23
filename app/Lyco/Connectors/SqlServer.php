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


  public function addData($date, $dataRow): bool
  {
    $success = false;
    $sql = /** @lang TSQL */
        'DECLARE @date date = :date
,@phone_line varchar(50) = :phone_line
,@inbound_average_answer_time varchar(10) = :inbound_average_answer_time
,@inbound_number_of_calls int = :inbound_number_of_calls
,@inbound_answered int = :inbound_answered
,@inbound_missed int = :inbound_missed
,@inbound_fallback int = :inbound_fallback
,@inbound_total_duration varchar(10) = :inbound_total_duration
,@inbound_average_duration varchar(10) = :inbound_average_duration
,@inbound_pc_answered decimal(5, 2) = :inbound_pc_answered
,@inbound_pc_missed decimal(5, 2) = :inbound_pc_missed
,@outbound_number_of_calls int = :outbound_number_of_calls
,@outbound_answered int = :outbound_answered
,@outbound_total_duration varchar(10) = :outbound_total_duration
,@outbound_average_duration varchar(10) = :outbound_average_duration
,@total_number_of_calls int = :total_number_of_calls
,@total_total_duration varchar(10) = :total_total_duration
,@total_average_duration varchar(10) = :total_average_duration;

IF EXISTS(SELECT [date], [phone_line] FROM Xelion_External_Calls_By_Phoneline WHERE [date] = @date AND [phone_line] = @phone_line)
	UPDATE Xelion_External_Calls_By_Phoneline SET [date]= @date
,[phone_line]=@phone_line
,[inbound_average_answer_time]=@inbound_average_answer_time
,[inbound_number_of_calls]=@inbound_number_of_calls
,[inbound_answered]=@inbound_answered
,[inbound_missed]=@inbound_missed
,[inbound_fallback]=@inbound_fallback
,[inbound_total_duration]= @inbound_total_duration
,[inbound_average_duration]=@inbound_average_duration
,[inbound_%_answered] =@inbound_pc_answered
,[inbound_%_missed]=@inbound_pc_missed
,[outbound_number_of_calls]=@outbound_number_of_calls
,[outbound_answered]=@outbound_answered
,[outbound_total_duration]=@outbound_total_duration
,[outbound_average_duration]=@outbound_average_duration
,[total_number_of_calls]=@total_number_of_calls
,[total_total_duration]=@total_total_duration
,[total_average_duration]=@total_average_duration
    WHERE [date]=@date AND [phone_line]=@phone_line
ELSE
INSERT INTO Xelion_External_Calls_By_Phoneline (
[date],[phone_line],[inbound_average_answer_time],[inbound_number_of_calls],[inbound_answered],[inbound_missed],[inbound_fallback],[inbound_total_duration],[inbound_average_duration],[inbound_%_answered] ,[inbound_%_missed],[outbound_number_of_calls],[outbound_answered],[outbound_total_duration],[outbound_average_duration],[total_number_of_calls],[total_total_duration],[total_average_duration]
)VALUES (
@date,@phone_line,@inbound_average_answer_time,@inbound_number_of_calls,@inbound_answered,@inbound_missed,@inbound_fallback,@inbound_total_duration,@inbound_average_duration,@inbound_pc_answered,@inbound_pc_missed,@outbound_number_of_calls,@outbound_answered,@outbound_total_duration,@outbound_average_duration,@total_number_of_calls,@total_total_duration,@total_average_duration)

';
    try {
      $stmt = $this->pdo->prepare($sql);
      $success = $stmt->execute([
          'date' => $date,
          'phone_line' => $dataRow[0],
          'inbound_average_answer_time' => $dataRow[1],
          'inbound_number_of_calls' => $dataRow[2],
          'inbound_answered' => $dataRow[3],
          'inbound_missed' => $dataRow[4],
          'inbound_fallback' => $dataRow[5],
          'inbound_total_duration' => $dataRow[6],
          'inbound_average_duration' => $dataRow[7],
          'inbound_pc_answered' => $dataRow[8],
          'inbound_pc_missed' => $dataRow[9],
          'outbound_number_of_calls' => $dataRow[10],
          'outbound_answered' => $dataRow[11],
          'outbound_total_duration' => $dataRow[12],
          'outbound_average_duration' => $dataRow[13],
          'total_number_of_calls' => $dataRow[14],
          'total_total_duration' => $dataRow[15],
          'total_average_duration' => $dataRow[16],
      ]);
    } catch (Exception $e) {
      error_log('There was an issue adding data to the database.' . PHP_EOL . $e->getMessage() . PHP_EOL . 'SQL: ' . $sql . PHP_EOL);
    }

    return $success;
  }


}