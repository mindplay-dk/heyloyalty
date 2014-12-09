<?php

namespace mindplay\heyloyalty;

use Guzzle\Http\Client;
use Guzzle\Http\Message\RequestInterface;

/**
 * This class implements a client for the Hey Loyalty API.
 */
class HeyLoyaltyClient
{
	/** @type string API endpoint */
	const BASE_URL = 'https://api.heyloyalty.com/loyalty/v1';

	/** @var string */
	private $api_key;

	/** @var string */
	private $api_secret;

	/** @var Client HTTP client */
	private $client;

	/**
	 * @param string $api_key
	 * @param string $api_secret
	 */
	public function __construct($api_key, $api_secret)
	{
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;

		$this->client = $this->createClient();
	}

    /**
     * @return Client
     */
    private function createClient()
    {
        return new Client(self::BASE_URL);
    }

	/**
	 * Sign a request for use with the Hey Loyalty API
	 *
	 * @param RequestInterface $request
	 *
	 * @return void
	 */
	private function signRequest(RequestInterface $request)
	{
		$timestamp = gmdate("D, d M Y H:i:s") . ' GMT';

		$signature = base64_encode(hash_hmac('sha256', $timestamp, $this->api_secret));

		$request->setAuth($this->api_key, $signature);

		$request->addHeader('X-Request-Timestamp', $timestamp);
	}

	/**
	 * @param string $uri
	 * @param string[] $headers
	 * @param array $options
	 *
	 * @return RequestInterface
	 */
	protected function createGetRequest($uri = null, $headers = null, $options = array())
	{
		$request = $this->client->get($uri, $headers, $options);

		$this->signRequest($request);

		return $request;
	}

    /**
     * @param string $uri
     * @param string[] $headers
     * @param string $body
     * @param array $options
     *
     * @return RequestInterface
     */
    protected function createPutRequest($uri = null, $headers = null, $body = null, array $options = array())
    {
        $request = $this->client->put($uri, $headers, $body, $options);

        $this->signRequest($request);

        return $request;
    }

    /**
	 * @param string $uri
	 * @param string[] $headers
	 * @param string $postBody
	 * @param array $options
	 *
	 * @return RequestInterface
	 */
	protected function createPostRequest($uri = null, $headers = null, $postBody = null, array $options = array())
	{
		$request = $this->client->post($uri, $headers, $postBody, $options);

		$this->signRequest($request);

		return $request;
	}

    /**
     * @param string $uri
     * @param string[] $headers
     * @param string $body
     * @param array $options
     *
     * @return RequestInterface
     */
    protected function createDeleteRequest($uri = null, $headers = null, $body = null, array $options = array())
    {
        $request = $this->client->delete();

        $this->signRequest($request);

        return $request;
    }

	/**
	 * @param int $list_id
	 *
	 * @return HeyLoyaltyList
	 */
	public function getList($list_id)
	{
		return $this->buildList($this->createGetRequest("lists/{$list_id}")->send()->json());
	}

	/**
	 * @return array
	 */
	public function getLists()
	{
		return $this->createGetRequest("lists")->send()->json(); // TODO build out a model
	}

	/**
	 * @param int $list_id
	 * @param int $page
	 * @param int $per_page
	 *
	 * @return array|bool|float|int|string
	 */
	public function getListMembers($list_id, $page, $per_page)
	{
		$request = $this->createGetRequest("lists/{$list_id}/members");

		$request->getQuery()
			->add('page', $page)
			->add('perpage', $per_page)
			->add('orderby', 'created_at');

		return $request->send()->json(); // TODO build out a model
	}

	/**
	 * @param int $list_id
	 * @param string $member_id
	 *
	 * @return array
	 */
	public function getMember($list_id, $member_id)
	{
		return $this->createGetRequest("lists/{$list_id}/members/{$member_id}")->send()->json(); // TODO build out a model
	}

	/**
	 * @param array $data
	 *
	 * @return HeyLoyaltyList
	 */
	private function buildList($data)
	{
        var_dump($data);

		$list = new HeyLoyaltyList();

		$list->id = (int) $data['id'];
		$list->name = utf8_decode($data['name']);
        $list->country_id = (int) $data['country_id'];
        $list->date_format = $data['date_format'];
        $list->duplicates = $data['duplicates'];

		$list->fields = $this->buildFields($list->id, $data['fields']);

        // TODO build out the HeyLoyaltyList model with additional properties

		return $list;
	}

	/**
	 * @param int $list_id
	 * @param array $field_data
	 *
	 * @return HeyLoyaltyField[]
	 */
	private function buildFields($list_id, $field_data)
	{
		$fields = array();

		foreach ($field_data as $data) {
			$field = new HeyLoyaltyField();

			$field->id = (int) $data['id'];
			$field->list_id = $list_id;
			$field->name = utf8_decode($data['name']);
			$field->label = utf8_decode($data['label']);
			$field->required_in_shop = (bool) $data['required_in_shop'];
			$field->fallback = $data['fallback'];
			$field->type = $data['type'];
			$field->type_id = (int) $data['type_id'];
			$field->format = $data['format'];

			if (isset($data['options'])) {
				$field->options = array_map('utf8_decode', $data['options']);
			}

			$fields[$field->name] = $field;
		}

		return $fields;
	}
}
