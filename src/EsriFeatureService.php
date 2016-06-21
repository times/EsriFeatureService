<?php

namespace Times;

class EsriFeatureService {

	protected $featureService;
	protected $source;
	protected $auth;
	protected $mapper;
	protected $authType;
	protected $client;

	/**
	 * Constructor
	 *
	 * @param string $featureService The URL to the ArcGIS feature service
	 * @param string $source 		 The JSON source of the data to send to ArcGIS
	 * @param string $auth 		 	 The auth method to use (token or array of username / password)
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */ 
	function __construct($featureService, $source, $auth, $mapper) {
		// Store params
		$this->featureService = $featureService;
		$this->source = $source;
		$this->mapper = $mapper;

		// Configure auth
		$this->auth = $this->configureAuth($auth);

		// Setup the HTTP client
		$this->client = new \GuzzleHttp\Client();
	}


	/**
	 * Sets up the auth token
	 *
	 * @param array|string $auth The token as a string, or array of username and password
	 *
	 * @return The token
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	private function configureAuth($auth) {
		if(is_array($auth)) {
			// Username and password login
			$this->authType = 'username';
			return $this->generateToken($auth['username'], $auth['password']);
		} else {
			$this->authType = 'token';
			return $auth;
		}
	}


	/**
	 * Generates a token for the request
	 *
	 * @param string $username The username of the ArcGIS account
	 * @param string $password The password of the ArcGIS account
	 *
	 * @return string The token 
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	private function generateToken($username, $password) {
		// Make a GET request for the token
		$res = $this->client->get('https://www.arcgis.com/sharing/generateToken', array(
			'query' => array(
				'username' => $username,
				'password' => $password,
				'f' => 'json',
				'referer' => 'localhost',
				'expiration' => '60'
			)
		));

		// Decode the body
		$body = json_decode($res->getBody());

		// Return the token
		return $body->token;
	}


	/**
	 * Gets the results from the source URL and decodes into an array
	 *
	 * @return array The results
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	private function getResults() {
		try {
			$res = $this->client->get($this->source);
			$results = json_decode($res->getBody());
			return $results;
		} catch(Exception $e) {
			throw $e;
		}
	}


	/**
	 * Gets the features from ArcGIS
	 *
	 * @return array The features
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	private function getFeatures() {
		// Setup the URL
		$url = $this->featureService . '/query?where=1%3D1&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&resultType=none&distance=&units=esriSRUnit_Meter&outFields=pa_name%2C+objectid&returnGeometry=false&returnCentroid=false&multipatchOption=&maxAllowableOffset=&geometryPrecision=&outSR=&returnIdsOnly=false&returnCountOnly=false&returnExtentOnly=false&returnDistinctValues=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&resultOffset=&resultRecordCount=&returnZ=false&returnM=false&quantizationParameters=&f=json&token=' . $this->auth;

		try {
			$res = $this->client->get($url);
			$body = json_decode($res->getBody());
			$features = $body->features;
			$featuresArray = array();

			foreach($features as $key => $feature) {
				$featuresArray[$feature->attributes->pa_name] = $feature->attributes->OBJECTID;
			}

			return $featuresArray;
		} catch(Exception $e) {
			throw $e;
		}
	}

	/**
	 * Updates a single feature
	 *
	 * @param string 	   $objectId The ID of the object to update
	 * @param array|object $record   The record to send
	 *
	 * @return string The response from the HTTP request
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	private function updateFeature($objectId, $record) {
		$url = $this->featureService . '/applyEdits';

		$data = array(
			array(
				'attributes' => array(
					'OBJECTID' => (string) $objectId,
				),
			)
		);

		$data[0]['attributes'] = array_merge($data[0]['attributes'], $this->mapper->map($record));

		try {
			$res = $this->client->request('POST', $url, array(
				'multipart' => array(
					array(
						'name' => 'f',
						'contents' => 'json'
					),
					array(
						'name' => 'updates',
						'contents' => json_encode($data)
					),
					array(
						'name' => 'token',
						'contents' => $this->auth
					)
				)
			));
			$body = json_decode($res->getBody()->getContents());
			return $body;
		} catch(Exception $e) {
			throw $e;
		}
	}

	/**
	 * Triggers an update of all records
	 *
	 * @return array An array containing all records classified by successful, failure and error
	 *
	 * @author Chris Hutchinson <chris.hutchinson@thetimes.co.uk>
	 */
	public function update() {
		$results = $this->getResults();
		$features = $this->getFeatures();
		$sent = [];
		$failures = [];
		$errors = [];

		foreach($results as $key => $result) {
			if(!isset($features[$result->name])) {
				$failures[] = $result->name;
				continue;
			}

			$res = $this->updateFeature($features[$result->name], $result);

			if(isset($res->error)) {
				$errors[] = $result->name;
				continue;
			}

			if($res) {
				$sent[] = $result->name;
			}
		}

		return array(
			'sent' => $sent,
			'failures' => $failures,
			'errors' => $errors
		);
	}

}