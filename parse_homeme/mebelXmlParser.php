<?

class MebelXmlParser
{
  
  protected $param = array();
  protected $path;
  protected $iblockId;
  protected $reader;
  protected $log = array();
  protected $arCategories = array();
  protected $writeOfferHelper;
  protected $dataFields = array();

  public function __construct(string $path, int $iblockId) {
    $this->path = $path;
    $this->iblockId = $iblockId;
    $this->log['summ'] = 0;
    $this->log['new'] = 0;
    $this->log['update'] = 0;

    //Установка параметров
    $this->param['LIMIT'] = 5000;
    $this->param['LOGGED'] = true;
    $this->param['GENERAL_SECTION'] = 668;

    //Проверка существования файла
    $this->reader = new XMLReader();
    try {
      if (!$this->reader->open($this->path)) {
        throw new Exception('Не удалось открыть файл '.$this->path);
      }
    }
    
    catch (Exception $ex) {
        echo $ex->getMessage();
    }

    $this->writeOfferHelper = new WriteOffer(array());
    $this->writeOfferHelper->setParam('IBLOCK_ID',$iblockId);

    //Собираем доп инфу по свойствам
    $code = 'mekhanizm_transformatsii';
    $this->dataFields[$code] = $this->getPropsByIblock($code);
    $code = 'material_obivki';
    $this->dataFields[$code] = $this->getPropsByIblock($code);
    $code = 'tsvet';
    $this->dataFields[$code] = $this->getPropsByIblock($code);
    $code = 'napolnitel';
    $this->dataFields[$code] = $this->getPropList($code);
  }

  public function setParam(array $param) {
    $this->param = $param;
  }

  public function process() {

    while ($this->reader->read()) {
      if (($this->reader->nodeType == XMLReader::ELEMENT) && ($this->reader->name == 'shop')) {
        while ($this->reader->read()) {
          if (($this->reader->nodeType == XMLReader::ELEMENT) && ($this->reader->name == 'categories')) {
            $this->prepareCategories();
          } elseif (($this->reader->nodeType == XMLReader::ELEMENT) && ($this->reader->name == 'offers')) {
            $offers = $this->prepareOffers();
          }
        }
        break;
        }
    }

   /*echo 'Вывод<br />';
    print_r($this->param);
    print_r($this->arCategories);
    print_r($offers);*/

    $this->reader->close();
  }

  protected function prepareCategories() {

    while ($this->reader->read()) {

          if (($this->reader->nodeType == XMLReader::ELEMENT) && ($this->reader->name == 'category')) {
              $data = array (
                'id' => $this->reader->getAttribute('id'),
                'parentId' => $this->reader->getAttribute('parentId'),
              );
          } elseif ($this->reader->nodeType == XMLReader::TEXT) {
              $this->arCategories[$data['id']] = array (
                'parentId' => $data['parentId'],
                'name' => $this->reader->value,
              );
          } elseif (($this->reader->nodeType == XMLReader::END_ELEMENT) && ($this->reader->name == 'categories')) {
              break;
          }
      }

  }
        
  protected function prepareOffers($action = 'save') {

    $offer = array();
    $ar_offer = array();
    $idx = 0;
        
      while ($this->reader->read()) {

          if (($this->reader->nodeType == XMLReader::ELEMENT) && ($this->reader->name == 'offer')) {
              $offer = array (
              'id' => $this->reader->getAttribute('id'),
              );

              while ($this->reader->read()) {
                
                if ($this->reader->nodeType == XMLReader::ELEMENT) {
                    switch($this->reader->name) {
                        case 'param':
                          $data = array (
                            'name' => $this->reader->getAttribute('name'),
                            'unit' => $this->reader->getAttribute('unit'),
                          );
                          $tag = 'param';
                        default:
                        $tag = $this->reader->name;
                    }
                  } elseif ($this->reader->nodeType == XMLReader::TEXT  || $this->reader->nodeType == XMLReader::CDATA) {

                    if ($tag == 'param') {
                      $offer[$tag][] = array (
                        'prop'  => $data,
                        'value' => $this->reader->value,
                      );
                    } else {
                      $offer[$tag][] = array (
                        'value' => $this->reader->value,
                      );
                    }
                  
                } elseif (($this->reader->nodeType == XMLReader::END_ELEMENT) && ($this->reader->name == 'offer')) {
                    break;
                }
              }

              $idx++;
              if ($action == 'save') {
                $this->writeOffer($offer);
              } else {
                $ar_offer[] = $offer;
              }
              if ($idx > $this->param['LIMIT']) break;
          }
      }
      return $ar_offer;
  }      

  protected function writeOffer($offer) {
    //Проверка на существование оффера
    $existsOffer = $this->isExistsOffer('PROPERTY_PROVIDER_ART_CODE', $offer['id']);
    if (!$existsOffer) {
      //Подготовка параметров к созданию и создание оффера
      $offerFields = array ();
      $offerFields['ACTIVE'] = 'N';
      $offerFields['PREVIEW_PICTURE'] = $this->getImg($offer['picture'][0]['value']);
      $offerFields['NAME'] = $offer['model'][0]['value'];
      $offerFields['IBLOCK_SECTION'] = $this->param['GENERAL_SECTION'];
      $offerFields['DETAIL_TEXT'] = $offer['description'][0]['value'];
      $offerFields['DETAIL_TEXT_TYPE'] = 'html';

      //Количество 999

      $offerProp = array();
      $offerProp['PROVIDER_ART_CODE'] = $offer['id'];

      $offerPrice = $offer['price'][0]['value'];

      //Механизм трансформации
      $offerProp['mekhanizm_transformatsii'] = $this->dataFields['mekhanizm_transformatsii'][mb_strtoupper($this->getParamXml($offer,'Механизм'))];
      $offerProp['LENGTH'] = $this->getParamXml($offer,'Длина');
      $offerProp['HEIGHT'] = $this->getParamXml($offer,'Высота');
      $offerProp['WIDTH'] = $this->getParamXml($offer,'Глубина');
      if($this->getParamXml($offer,'Бельевой короб') == 'Есть') {
        $offerProp['s_yashchikom_dlya_belya'] = array(28);
      }
      if($this->getParamXml($offer,'Длина спального места')) {
        $offerProp['BED_SIZES'] = $this->getParamXml($offer,'Длина спального места').'x'.$this->getParamXml($offer,'Ширина спального места');
      }
      $offerProp['material_obivki'] = $this->dataFields['material_obivki'][mb_strtoupper($this->getParamXml($offer,'Тип обивки'))];
      $offerProp['tsvet'] = $this->dataFields['tsvet'][mb_strtoupper($this->getParamXml($offer,'Цвет'))];
      $offerProp['napolnitel'] = array($this->dataFields['napolnitel'][mb_strtoupper($this->getParamXml($offer,'Наполнитель'))]);
      $offerProp['BRAND'] = array(259);
      $offerProp['PROVIDER'] = 87841;
      $offerProp['SUBNAME'] = $this->getParamXml($offer,'Базовая обивка');

      //Доп. фото
      foreach($offer['picture'] as $key => $link_img) {
          $offerProp['DETAIL_PICTURES'][] = array(
            'VALUE' => $this->getImg($link_img['value']),
            'DESCRIPTION' => ''
          );
      }

      $resultCreate = $this->writeOfferHelper->createOffer($offerProp,$offerFields,$offerPrice,999);

      if($this->param['LOGGED']) {
        $this->log['items'][] = array (
          $offer['model'][0]['value'],
          $resultCreate['id'],
          $resultCreate['status']
        );
        $this->log['summ'] ++;
        $this->log['new'] ++;
      }
    } else {
      //Подготовка парметров и обновление оффера
        
      $offerProp = array();
      $offerProp['SUBNAME'] = $this->getParamXml($offer,'Базовая обивка');

      $this->writeOfferHelper->updateOffer($existsOffer['ID'],$offer['price'][0]['value']);
      $this->writeOfferHelper->updateProps($existsOffer['ID'],$offerProp);

      if($this->param['LOGGED']) {
        $this->log['items'][] = array (
          $offer['model'][0]['value'],
          $existsOffer['ID'],
          'update'
        );
        $this->log['summ'] ++;
        $this->log['update'] ++;
      }
    }
  }

  /*
    Проверка на существование товара
  */
  protected function isExistsOffer($field,$value) {
    $arFilter = Array("IBLOCK_ID"=>$this->iblockId, $field => $value);
    return  \CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize"=>1), array('ID'))->Fetch();
  }

  protected function getPropsByIblock($code) {
    $properties = CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>28, "CODE" => $code));
    $prop_fields = $properties->Fetch();
    $dbItems = \Bitrix\Iblock\ElementTable::getList(array(
      'order' => array('SORT' => 'ASC'),
      'select' => array('ID', 'NAME'),
      'filter' => array('IBLOCK_ID' => $prop_fields['LINK_IBLOCK_ID']),
      'limit' => 100,
      'cache' => array(
        'ttl' => 3600,
        'cache_joins' => true
      ),
    ))->FetchAll();
    $result = array();
    foreach($dbItems as $item) {
      $result[mb_strtoupper($item['NAME'])] = $item['ID'];
    }

    return $result;
  }

  protected function getPropList($code) {
    $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>$this->iblockId, "CODE"=>$code));
    $result = array();
    while($enum_fields = $property_enums->GetNext())
    {
      $result[mb_strtoupper($enum_fields["VALUE"])] = $enum_fields["ID"];
    }
    return $result;
  }

  protected function getParamXml($offer,$param) {
    foreach($offer['param'] as $item) {
      if ($param == $item['prop']['name']) {
        return $item['value'];
      }
    }
    return false;
  }

  protected function getImg($url,$maxWidth = 1024, $maxHeight = 525) {
    $img = CFile::MakeFileArray($url);
    //изменяем размеры картинки:
    $result = CIBlock::ResizePicture(
      $img,
      [
        "WIDTH" => $maxWidth,
        "HEIGHT" => $maxHeight,
        "METHOD" => "resample",
      ]
    );
    return $result;
  }

  public function viewLog($type = 'console') {
    if($type == 'console') {
      foreach($this->log['items'] as $row) {
        echo implode(' | ', $row).PHP_EOL;
      }
      echo 'Обработано: '.$this->log['summ'].PHP_EOL;
      echo 'Создано: '.$this->log['new'].PHP_EOL;
      echo 'Обновлено: '.$this->log['update'].PHP_EOL;
    }
  }

}

?>