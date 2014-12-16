<?php

namespace mindplay\heyloyalty;

use Exception;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\QueryString;
use RuntimeException;

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

    /** @var HeyLoyaltyList[] map of cached Lists where List ID => HeyLoyaltyList */
    private $list_cache = array();

    /** @var HeyLoyaltyMediator */
    private $mediator;

	/**
	 * @param string $api_key
	 * @param string $api_secret
	 */
	public function __construct($api_key, $api_secret)
	{
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;

		$this->client = $this->createClient();
        $this->mediator = new HeyLoyaltyMediator();
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
     * Obtain information about a list and it's fields.
     *
     * Lists are cached for subsequent calls.
     *
	 * @param int $list_id
	 *
	 * @return HeyLoyaltyList
	 */
	public function getList($list_id)
	{
        if (!isset($this->list_cache[$list_id])) {
            $this->list_cache[$list_id] = $this->buildList($this->createGetRequest("lists/{$list_id}")->send()->json());
        }

        return $this->list_cache[$list_id];
	}

	/**
	 * @return array
	 */
	public function getLists()
	{
		return $this->createGetRequest("lists")->send()->json(); // TODO build out a model
	}

	/**
     * Find a list of members by matching against a custom set of criteria.
     *
	 * @param int $list_id
	 * @param int $page
	 * @param int $per_page
     * @param HeyLoyaltyListFilter|null $filter
	 *
	 * @return HeyLoyaltyMember[]
	 */
	public function getListMembers($list_id, $page, $per_page, HeyLoyaltyListFilter $filter = null)
	{
		$request = $this->createGetRequest("lists/{$list_id}/members");

		$request->getQuery()
			->add('page', $page)
			->add('perpage', $per_page)
			->add('orderby', 'created_at');

        if ($filter !== null) {
            $params = $request->getQuery();

            $filters = $filter->toArray();

            foreach ($filters as $key => $value) {
                $params->add($key, $value);
            }
        }

        $result = $request->send()->json();

        $members = array();

        if (!empty($result['members'])) {
            foreach ($result['members'] as $data) {
                $members[] = $this->buildMember($list_id, $data);
            }
        }

        return $members;
	}

    /**
     * Find a list member by e-mail address.
     *
     * This should be used only for lists that DO NOT contain duplicates.
     *
     * @param int $list_id
     * @param string $email
     *
     * @return HeyLoyaltyMember|null
     */
    public function getListMemberByEmail($list_id, $email)
    {
        $filter = new HeyLoyaltyListFilter();
        $filter->equalTo('email', $email);

        $members = $this->getListMembers(HEY_LOYALTY_LIST_ID, 1, 1, $filter);

        return count($members) ? $members[0] : null;
    }

    /**
     * Find a list member by mobile phone number.
     *
     * This should be used only for lists that DO NOT contain duplicates.
     *
     * @param int $list_id
     * @param string $mobile
     *
     * @return HeyLoyaltyMember|null
     */
    public function getListMemberByPhone($list_id, $mobile)
    {
        $filter = new HeyLoyaltyListFilter();
        $filter->equalTo('mobile', $mobile);

        $members = $this->getListMembers(HEY_LOYALTY_LIST_ID, 1, 1, $filter);

        return count($members) ? $members[0] : null;
    }

    /**
     * Enumerate all of the members on a list, or a subset based on filter crieria.
     *
     * For nightly imports, etc. - may result in multiple round-trips to the API for batches of member records.
     *
     * @param int $list_id
     * @param callable $callback a callback function (HeyLoyaltyMember $member) : void
     * @param HeyLoyaltyListFilter|null $filter
     *
     * @return void
     */
    public function enumerateListMembers($list_id, $callback, $filter = null)
    {
        $total = 0;

        $page = 1;

        $PER_PAGE = 1000;

        do {
            $members = $this->getListMembers($list_id, $page, $PER_PAGE, $filter);

            $page += 1;

            $total += count($members);

            foreach ($members as $member) {
                call_user_func($callback, $member);
            }
        } while (count($members));
    }

	/**
     * Get an individual Member with a known Hey Loyalty Member GUID
     *
	 * @param int $list_id
	 * @param string $member_id Hey Loyalty Member GUID
	 *
	 * @return HeyLoyaltyMember
	 */
	public function loadMember($list_id, $member_id)
	{
		return $this->buildMember($list_id, $this->createGetRequest("lists/{$list_id}/members/{$member_id}")->send()->json());
	}

    /**
     * Save a new or existing Member.
     *
     * @param HeyLoyaltyMember $member
     */
    public function saveMember(HeyLoyaltyMember $member)
    {
        if ($member->id) {
            $this->updateMember($member);
        } else {
            $this->createMember($member);
        }
    }

    /**
     * Adds a given new Member ($id === null) to a specified Hey Loyalty List.
     *
     * @param HeyLoyaltyMember $member
     *
     * @return string new Hey Loyalty Member GUID
     */
    public function createMember(HeyLoyaltyMember $member)
    {
        if ($member->id) {
            throw new RuntimeException("cannot create a member when \$id is already set");
        }

        $request = $this->createPostRequest("lists/{$member->list_id}/members");

        /** @var QueryString $post */
        /** @noinspection PhpUndefinedMethodInspection */
        $post = $request->getPostFields();

        $fields = $this->getList($member->list_id)->fields;

        foreach ($fields as $name => $field) {
            $post->add($name, $this->mediator->formatValue($field->format, $member->$name));
        }

        $response = $request->send();

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException("unexpected HTTP status code {$response->getStatusCode()} - response: \n" . $response->getBody(true));
        }

        $result = $response->json();

        $member->id = $result['id'];

        return $member->id;
    }

    /**
     * Update a given existing Member ($id != null) previously retrieved from a Hey Loyalty List.
     *
     * @param HeyLoyaltyMember $member
     *
     * @return void
     */
    public function updateMember(HeyLoyaltyMember $member)
    {
        if (! $member->id) {
            throw new RuntimeException("cannot update a member with no \$id");
        }

        $request = $this->createPutRequest("lists/{$member->list_id}/members/{$member->id}");

        /** @var QueryString $post */
        /** @noinspection PhpUndefinedMethodInspection */
        $post = $request->getPostFields();

        $fields = $this->getList($member->list_id)->fields;

        foreach ($fields as $name => $field) {
            if (isset($data[$name])) {
                $post->add($name, $this->mediator->formatValue($field->format, $data[$name]));
            }
        }

        $response = $request->send();

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException("unexpected HTTP status code {$response->getStatusCode()} - response: \n" . $response->getBody(true));
        }
    }

    /**
     * Delete an existing Hey Loyalty Member.
     *
     * @param HeyLoyaltyMember|string $member Member object
     *
     * @return void
     */
    public function deleteMember(HeyLoyaltyMember $member)
    {
        $request = $this->createDeleteRequest("lists/{$member->list_id}/members/{$member->id}");

        $response = $request->send();

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException("unexpected HTTP status code {$response->getStatusCode()} - response: \n" . $response->getBody(true));
        }
    }

    /**
     * Patch a member with a known Hey Loyalty Member GUID.
     *
     * The given data must be complete - any field values omitted from the given
     * data, will cause any existing data in those fields to be lost!
     *
     * @param int $list_id Hey Loyalty List ID
     * @param string $member_id Hey Loyalty Member GUID
     * @param array $data complete data set with all fields (as native PHP values)
     *
     * @return void
     */
    public function updateMemberData($list_id, $member_id, $data)
    {
        $request = $this->createPutRequest("lists/{$list_id}/members/{$member_id}");

        /** @var QueryString $post */
        /** @noinspection PhpUndefinedMethodInspection */
        $post = $request->getPostFields();

        $fields = $this->getList($list_id)->fields;

        foreach ($fields as $name => $field) {
            if (isset($data[$name])) {
                $post->add($name, $this->mediator->formatValue($field->format, $data[$name]));
            }
        }

        $response = $request->send();

        if ($response->getStatusCode() !== 201) {
            throw new RuntimeException("unexpected HTTP status code {$response->getStatusCode()} - response: \n" . $response->getBody(true));
        }
    }

	/**
	 * @param array $data
	 *
	 * @return HeyLoyaltyList
	 */
	private function buildList($data)
	{
		$list = new HeyLoyaltyList();

		$list->id = (int) $data['id'];
		$list->name = utf8_decode($data['name']);
        $list->country_id = (int) $data['country_id'];
        $list->date_format = $data['date_format'];
        $list->duplicates = $data['duplicates'];

		$list->fields = $this->buildFields($list->id, $data['fields']);

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

    /**
     * Build a new member instance using data from the Hey Loyalty API
     *
     * @param int $list_id
     * @param array $data
     *
     * @return HeyLoyaltyMember
     */
    private function buildMember($list_id, $data)
    {
        $member = new HeyLoyaltyMember($list_id);

        $this->populateMember($member, $data);

        return $member;
    }

    /**
     * Update a member object with data from the Hey Loyalty API
     *
     * @param HeyLoyaltyMember $member
     * @param array $data
     *
     * @internal param int $list_id
     * @return void
     */
    private function populateMember($member, $data)
    {
        $member->id = $data['id'];
        $member->status = $data['status']['status'];
        $member->status_email = $data['status']['email'];
        $member->status_mobile = $data['status']['mobile'];
        $member->sent_mail = $data['sent_mail'];
        $member->sent_sms = $data['sent_sms'];
        $member->open_rate = $data['open_rate'];
        $member->imported = $data['imported'];
        $member->created_at = $this->mediator->parseDateTime($data['created_at']);
        $member->updated_at = $this->mediator->parseDateTime($data['updated_at']);

        $fields = $this->getList($member->list_id)->fields;

        foreach ($fields as $name => $field) {
            if (array_key_exists($name, $data)) {
                $member->$name = $this->mediator->parseValue($field->format, $data[$name]);
            }
        }
    }
}
