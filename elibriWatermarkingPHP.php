<?php
  //ustaw domyślną strefę czasową
  if (!ini_get("date.timezone")) {
    date_default_timezone_set("Europe/Warsaw"); 
  }
  require_once 'elibriWatermarkingPHP/elibriWatermarkingClient.php';
?>
