# ESRI Feature Service Class

A PHP class for interfacing with [ESRI's feature service API](http://resources.arcgis.com/en/help/rest/apiref/featureserver.html).


### Installation

Install via `composer`.

	$ composer require times/esri-feature-service



### Usage
	
	require './vendor/autoload.php';

	$featureService = ''; // URL to the feature service
	$sourceJson = ''; // URL to your JSON file containing an array of data
	$token = ''; // A string containing the token, or an array containing `username` and `password`

	// Define our mapper class. The `EsriFeatureService` class calls the `map()` method below to map the loaded in data into the format defined in this method (to match the needs of your ESRI map)
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
	$FeatureService = new EsriFeatureService($featureService, $sourceJson, $token, $CustomMapper);

	// Trigger the data update
	$response = $FeatureService->update();

	// Dump the response (an array of successes, failures and errors)
	var_dump($response);