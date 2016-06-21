# ESRI Feature Service Class


### Installation

Install via `composer`:

`$ composer require times/esri-feature-service`


### Usage

	require './vendor/autoload.php';
	require './EsriFeatureService.php';

	$featureServer = '';
	$sourceJson = '';
	$token = ''; // Or array containing `username` and `password`

	// Define our mapper class
	class CustomMapper {

		public function map($record) {
			return array(
				'someProperty' = $record->someProperty
			)
		}

	}
		
	// Initialise our mapper
	$CustomMapper = new CustomMapper;

	// Initialise the EsriFeatureService and pass in the mapper
	$FeatureService = new EsriFeatureService($featureServer, $sourceJson, $token, $CustomMapper);

	// Trigger the data update
	$response = $FeatureService->update();

	// Dump the response (an array of successes, failures and errors)
	var_dump($response);
	die();