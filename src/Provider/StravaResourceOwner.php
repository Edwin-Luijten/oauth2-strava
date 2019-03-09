<?php
namespace League\OAuth2\Client\Provider;

class StravaResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response.
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->response['id'] ?: null;
    }

    /**
     * Returns resource owner first name.
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->response['firstname'] ?: null;
    }

    /**
     * Returns resource owner last name.
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->response['lastname'] ?: null;
    }

    /**
     * Returns resource owner premium membership status.
     *
     * @return bool
     */
    public function getPremium()
    {
        return $this->response['premium'];
    }

    /**
     * Returns all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
