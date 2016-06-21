# ESRI Feature Service Class


### Installation

Install via `composer`. Add the following to your `composer.json` file:

	"repositories": [
	    {
	        "type": "vcs",
	        "url":  "git@github.com:times/EsriFeatureService.git"
	    }
	],

Then add this to your `dependencies`:

	"times/esri-feature-service": "dev-master"


### Usage

	require './vendor/autoload.php';

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