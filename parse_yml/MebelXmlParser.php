<?

class MebelXmlParser
{
	
	protected $param = array();
	protected $path;
	protected $reader;
	protected $log;
	protected $arCategories = array();
	protected $writeOfferHelper;

	public function __construct(string $path, int $iblock_id) {
		$this->path = $path;

		//Установка параметров
		$this->param['LIMIT'] = 10;
		$this->param['LOGGED'] = true;

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

		$this->writeOfferHelper = new WriteOffer;
		$this->writeOfferHelper->setParam(array('IBLOCK_ID') = $iblock_id);
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
			echo 'c- '.$this->reader->name.'<br />';
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
	      
	protected function prepareOffers($action = 'default') {

		$offer = array();
		$ar_offer = array();
		$idx = 0;
	      
	    while ($this->reader->read()) {
	    	echo '- '.$this->reader->name.'<br />';

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
	                } elseif ($this->reader->nodeType == XMLReader::TEXT) {

		                if ($tag == 'param') {
		                  $offer[$tag][] = array (
		                    'prop'  => $data,
		                    'value' => $this->reader->value,
		                  );
		                } else {
		                  $offer[$tag] = array (
		                    'value' => $this->reader->value,
		                  );
		                }
	                
		            } elseif (($this->reader->nodeType == XMLReader::END_ELEMENT) && ($this->reader->name == 'offer')) {
		                break;
		            }
	            }

	            $idx++;
	            if ($action == 'save') {
	            	saveOffer($offer);
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

		//Подготовка параметров к созданию и создание оффера
		$this->writeOfferHelper->createOffer($offerProp,$offerFields,$offerPrice);

		//Подготовка парметров и обновление оффера
		$this->writeOfferHelper->updateOffer($offerId,$offerPrice);
	} 

}

?>