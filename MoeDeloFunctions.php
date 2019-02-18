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


  // функция возвращает массив с информацией обо всех договорах
function GetMoeDeloAccountsData() {
  $tmp = GetMoeDeloData('/kontragents/api/v1/kontragent', array('pageSize'=>99999));
  return $tmp['ResourceList'];
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


  // функция возвращает массив с информацией обо всех договорах
function GetMoeDeloContractsData() {
  $tmp = GetMoeDeloData('/contract/api/v1/contract', array('pageSize'=>99999));
  return $tmp['ResourceList'];
}


  // функция возвращает массив с информацией о договорах с условиями поиска (array)$search (field=>value)
  // $data - массив договоров (для случаев, когда нужно запускать эту функцию несколько раз и )
function GetMoeDeloContractData($search='', $data='') {
  if (!$search) return false;
  $tmp = '';
  $data = ($data && is_array($data)) ? $data : GetMoeDeloContractsData();
  foreach ($data as $contract) {
	$c = 0;
    foreach ($search as $key=>$value) { if ($value!=$contract[$key]) {$c=0;break;} else $c = 1; }
	if ($c) $tmp[] = $contract;
  }
  return $tmp;
}


  // функция создаёт договор, возвращает id созданного договора
function CreateMoeDeloContract($number='', $date='', $accountMDId='', $summ=0) {
  if (!$number || !$accountMDId) return false;
  if (!$date) $date = date('Y-m-d');
  $date = date(DATE_ATOM, strtotime($date));
    // сначала проверяем наличие этого договора в МД (тот же номер и контрагент)
  $tmp = GetMoeDeloContractData(array('Number'=>$number, 'KontragentId'=>$accountMDId));  
  if ($tmp[0]['Id']) return $tmp[0]['Id'];
    // данные для создания договора
  $data['Number'] = $number;
  $data['KontragentId'] = $accountMDId;
  $data['DocDate'] = $date;
  $data['Sum'] = intval($summ);
  $data['Status'] = 2;
  $data['Direction'] = 1;
    // запрос на создание
  $curl = curl_init(MOEDELO_URL.'/contract/api/v1/contract');
  curl_setopt_array($curl, array(
    CURLOPT_HTTPHEADER => array('Accept: application/json', 'md-api-key: '.MOEDELO_TOKEN, 'Content-Type: application/json'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
  ));
  if ($response=curl_exec($curl)) {
    $answer = json_decode($response, true);
    if ($answer['Id']) { curl_close($curl); return $answer['Id']; }
  }
  curl_close($curl);
  return false;
}


  // функция обновляет договор $mdId
function UpdateMoeDeloContract($mdId='', $number='', $date='', $accountMDId='', $summ=0) {
  if (!$mdId || !$number || !$accountMDId) return false;
  if (!$date) $date = date('Y-m-d');
  $date = date(DATE_ATOM, strtotime($date));
    // данные для обновления договора
  $data['Number'] = $number;
  $data['KontragentId'] = $accountMDId;
  $data['DocDate'] = $date;
  $data['Sum'] = intval($summ);
  $data['Status'] = 2;
  $data['Direction'] = 1;
    // запрос на обновление
  $curl = curl_init(MOEDELO_URL.'/contract/api/v1/contract/'.$mdId);
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
  return false;
}


  // функция возвращает массив с информацией обо всех актах
function GetMoeDeloActsData() {
  $tmp = GetMoeDeloData('/accounting/api/v1/sales/act', array('pageSize'=>99999));
  return $tmp['ResourceList'];
}


  // функция возвращает массив с информацией об актах с условиями поиска (array)$search (field=>value)
  // условия поиска - https://restapi.moedelo.org/s/?url=/docs#!/%D0%9F%D1%80%D0%BE%D0%B4%D0%B0%D0%B6%D0%B8_-_%D0%90%D0%BA%D1%82%D1%8B/SalesAct_Get
function GetMoeDeloActData($search) {
  $tmp = GetMoeDeloData('/accounting/api/v1/sales/act', $search);
  return $tmp['ResourceList'];
}


  // функция создаёт акт, возвращает id созданного акта
  // формат массива позиций акта - https://restapi.moedelo.org/s/?url=/docs#!/%D0%9F%D1%80%D0%BE%D0%B4%D0%B0%D0%B6%D0%B8_-_%D0%90%D0%BA%D1%82%D1%8B/SalesAct_Put
function CreateMoeDeloAct($number='', $date='', $accountMDId='', $contractMDId='', $summ=0, $original='Нет', $products='') {
  if (!$number || !$accountMDId || !$products || !in_array($original,array('','Да','Нет','Скан')) || !is_array($products)) return false;
  if (!$date) $date = date('Y-m-d');
  $date = date(DATE_ATOM, strtotime($date));
    // сначала проверяем наличие этого акта в МД (тот же номер и контрагент)
  $tmp = GetMoeDeloActData(array('number'=>$number, 'kontragentId'=>$accountMDId));  
  if ($tmp[0]['Id']) return $tmp[0]['Id'];
    // данные для создания акта
  $data['Number'] = $number;
  $data['KontragentId'] = $accountMDId;
  $data['DocDate'] = $date;
  $data['Sum'] = intval($summ);
  $data['ProjectId'] = $contractMDId;
  $data['OnHands'] = $original;
  $data['Items'] = $products;
    // запрос на создание
  $curl = curl_init(MOEDELO_URL.'/accounting/api/v1/sales/act');
  curl_setopt_array($curl, array(
    CURLOPT_HTTPHEADER => array('Accept: application/json', 'md-api-key: '.MOEDELO_TOKEN, 'Content-Type: application/json'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
  ));
  if ($response=curl_exec($curl)) {
    $answer = json_decode($response, true);
    if ($answer['Id']) { curl_close($curl); return $answer['Id']; }
  }
  curl_close($curl);
  return false;
}


  // функция обновляет акт $mdId
function UpdateMoeDeloAct($mdId='', $number='', $date='', $accountMDId='', $contractMDId='', $summ=0, $original='Нет', $products='') {
  if (!$mdId || !$number || !$accountMDId || !in_array($original,array('','Да','Нет','Скан'))) return false;
  if (!$date) $date = date('Y-m-d');
  $date = date(DATE_ATOM, strtotime($date));
    // данные для обновления акта
  $data['Number'] = $number;
  $data['KontragentId'] = $accountMDId;
  $data['DocDate'] = $date;
  $data['Sum'] = intval($summ);
  $data['ProjectId'] = $contractMDId;
  $data['OnHands'] = $original;
  $data['Items'] = $products;
    // запрос на обновление
  $curl = curl_init(MOEDELO_URL.'/accounting/api/v1/sales/act/'.$mdId);
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
  return false;
}






?>