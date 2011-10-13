# ColissimoAPI

## Usage

    try{
		$colis = new ColissimoAPI();
		$colis->setImageDir('./tmp/'); // optionnal, default: ./images/

		$res = $colis->getStatus('8L84527382672');
	}
	catch(Exception $e){
		echo $e->getMessage();
	}

More info about the [Colissimo API](http://www.lackofinspiration.com/news-3-110-l-api-cachee-de-colissimo.html).
