<?php

  // Интеграция CRM Clientbase и Моё Дело
  // https://ClientbasePro.ru
  // https://restapi.moedelo.org/s/?url=/docs
  
require_once 'common.php';

    // функции

    // функция возвращает данные по запросу (string)$url с параметрами (array)$params
function GetMoeDeloData($url, $params) {
    // проверка наличия входных данных
  if (!$url) return false;
  $url = MOEDELO_URL.$url;
  if ($params) $url .= '?'.http_build_query($params);
  $curl = curl_init($url);
  curl_setopt_array($curl, array(
    CURLOPT_HTTPHEADER => array('Accept: application/json', 'md-api-key: '.MOEDELO_TOKEN, 'Content-Type: application/json'),
    CURLOPT_RETURNTRANSFER => true
  ));
  if ($response=curl_exec($curl)) if ($answer=json_decode($response,true)) { curl_close($curl); return $answer; }             
  curl_close($curl);
  return false;
}
	
	
	
	
	
	

?>