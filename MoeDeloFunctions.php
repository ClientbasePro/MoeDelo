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

 
  // функция возвращает массив с информацией о контрагенте по ИНН или названию
function GetMoeDeloAccountData($INN='', $name='') {
  $INN = preg_replace('/\D/i','',$INN);
  if (!$INN && !$name) return false;
  if ($INN) $params['inn'] = $INN;
  if ($name) $params['name'] = $name;
  $tmp = GetMoeDeloData('/kontragents/api/v1/kontragent', $params);
  return $tmp['ResourceList'];
}


  // функция создаёт контрагента по ИНН, возвращает id созданного контрагента
function CreateMoeDeloAccountByINN($INN='') {
  $INN = preg_replace('/\D/i','',$INN);
  if (!$INN) return false;
    // сначала проверяем наличие такого же контрагента
  if (($tmp=GetMoeDeloAccountData($INN)) && $tmp[0]['Id']) return $tmp[0]['Id'];
    // запрос на создание
  $curl = curl_init(MOEDELO_URL.'/kontragents/api/v1/kontragent/inn');
  curl_setopt_array($curl, array(
    CURLOPT_HTTPHEADER => array('Accept: application/json', 'md-api-key: '.MOEDELO_TOKEN, 'Content-Type: application/json'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '{"Inn":"'.$INN.'"}'
  ));
  if ($response=curl_exec($curl)) {
    $answer = json_decode($response, true);
    if ($answer['Id']) { curl_close($curl); return $answer['Id']; }
  }
  curl_close($curl);
  return false;
}

  // функция обновляет контрагента в МД по его ID и названию $name
function UpdateMoeDeloAccount($mdId='', $name='', $inn='', $ogrn='', $LegalAddress='', $ActualAddress='') {
  $mdId = preg_replace('/\D/i','',$mdId);
  if (!$mdId || !$name || (!$inn && !$ogrn && !$LegalAddress && !$ActualAddress)) return false;
    // массив данных для обновления  
  if ($inn) $data['Inn'] = $inn;
  if ($ogrn) $data['Ogrn'] = $ogrn;  
  if ($LegalAddress) $data['LegalAddress'] = $LegalAddress;
  if ($ActualAddress) $data['ActualAddress'] = $ActualAddress;
  if ($data) {
    $data['Name'] = $name;
      // запрос на обновление
    $curl = curl_init(MOEDELO_URL.'/kontragents/api/v1/kontragent/'.$mdId);
    curl_setopt_array($curl, array(
      CURLOPT_HTTPHEADER => array('Accept: application/json', 'md-api-key: '.MOEDELO_TOKEN, 'Content-Type: application/json'),
      CURLOPT_RETURNTRANSFER => true, 
      CURLOPT_CUSTOMREQUEST => 'PUT',
      CURLOPT_POSTFIELDS => json_encode($data)
    ));
    if ($response=curl_exec($curl)) {
      $answer = json_decode($response, true);
      if ($answer['Id']) { curl_close($curl); return true; }
    }
    curl_close($curl);
  }
  return false;
}


?>