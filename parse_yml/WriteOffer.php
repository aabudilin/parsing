<?

class WriteOffer
{
	protected $param = array;
	protected $log;

	public function __construct(array $param) {
		$this->param['TYPE_PRICE'] = 1;
	}

	public function setParam($param) {
		$this->param = array_merge($this->param,$param);
	}

	protected function createOffer($offerProp,$offerFields,$offerPrice,$offerQ = 1) {
		$result = array();

		$params = array(
		      "max_len" => "100", 
		      "change_case" => "L", 
		      "replace_space" => "_", 
		      "replace_other" => "_", 
		      "delete_repeat_replace" => "true", 
		      "use_google" => "false", 
		   );

		  $el = new CIBlockElement;

		  $prop = $offerProp;
		  
		  $fields = array (
		    "IBLOCK_ID"         => $this->param['IBLOCK_ID'],
		    "NAME"              => $data[1],
		    "ACTIVE"            => "Y",
		    "CODE"              => CUtil::translit($data[1], "ru", $params),
		    "PROPERTY_VALUES"   => $prop,
		  );

		  $fields = merge_array($fields,$offerFields);

		  if($id = $el->Add($fields)) {

		  //Записываем свойство типа справочник
		  //CIBlockElement::SetPropertyValuesEx($ID, CATALOG_IBLOCK, array('BRANDS_REF' => $data[4]));

		    //Создаем товар
		    $productID = CCatalogProduct::add(array("ID" => $id, "QUANTITY" => $offerQ));

		    //Добавляем цену
		    $priceFields = Array(
		      "CURRENCY"         => "RUB", // валюта
		      "PRICE"            => intval($data[4]), // значение цены
		      "CATALOG_GROUP_ID" => $this->param['TYPE_PRICE'], // ID типа цены
		      "PRODUCT_ID"       => $id, // ID товара
		    );

		    CPrice::Add($arFields);

		    //Количество по складам
		    /*$arFields = Array(
		      "PRODUCT_ID" => $productID,
		      "STORE_ID"   => $storeID,
		      "AMOUNT"     => $rest,
		    )
		    CCatalogStoreProduct::Add($arFields);/**/

		    $result = array (
		    	'status' => 'success',
		    	'id' => $id,
		    );
		  } else {
		  	$result = array (
		  		'status' => 'error',
		  		'error' => $el->LAST_ERROR,
		   	);
		  }

		  return $result;
	}


	protected function updateOffer($offerId,$offerPrice,$offerQ = 1) {
		$result = array();

		$arPropPrice = Array(
	      "CURRENCY"         => "RUB", // валюта
	      "PRICE"            => $offerPrice, // значение цены
	      "CATALOG_GROUP_ID" => $this->param['TYPE_PRICE'], // ID типа цены
	      "PRODUCT_ID"       => $offerId, // ID товара
	    );

	    $res_price = CPrice::GetList(
	        array(),
	        array(
	            "PRODUCT_ID" => $offerId,
	            "CATALOG_GROUP_ID" => $this->param['TYPE_PRICE'],
	        )
	    );

    	if ($arr = $res_price->Fetch()) {
      		CPrice::Update($arr["ID"],$arPropPrice);
        } else {
      		CPrice::Add($arPropPrice);
    	}
	}


	protected function getMapSections() {
		$result = array();
		$rsSection = \Bitrix\Iblock\SectionTable::getList(array(
		    	'filter' => array(
	        		'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
	    		),
				'order' => array('LEFT_MARGIN' => 'ASC'),
	    		'select' =>  array('ID','NAME', 'DEPTH_LEVEL'),
	    		'cache' => ['ttl' => 3600],
		));
		while ($arSection = $rsSection->fetch()) {
		    $result[$arSection['ID']] = $arSection;
		}

		return $result;
	}

	protected function toUtf8($value) {
		if (is_array($value)) {
	    	$result = array();
			foreach ($value as $item) {
				$new_arr[] = iconv("Windows-1251", "UTF-8", trim($item));
			}
	   } else {
	   		$result = iconv("Windows-1251", "UTF-8", trim($value));
	   }

	   return $result ;
	}

	protected function createSection($name, $parent = false) {
		$section = new CIBlockSection;
	    $arFields = Array(
	        "ACTIVE" => "Y", 
	        "IBLOCK_ID" => $this->param['IBLOCK_ID'],
	        "NAME" => $name,
	    );

	    if ($parent) {
	      $arFields["IBLOCK_SECTION_ID"] = $parent;
	    }

	    if ($id_section = $section->Add($arFields)) {
	    	return $id_section;
	    } else {
	        return $section->LAST_ERROR;
	    }
	}
}

?>