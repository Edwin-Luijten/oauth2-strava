<?php

namespace League\OAuth2\Client\Provider;

class StravaResourceOwner implements ResourceOwnerInterface
{
    /**
     * Domain
     *
     * @var string
     */
    protected $domain;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['id'] ?: null;
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['email'] ?: null;
    }

    /**
     * Get resource owner first name
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->response['firstname'] ?: null;
    }

    /**
     * Get resource owner last name
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->response['lastname'] ?: null;
    }

    /**
     * Get resource owner nickname
     *
     * @return string|null
     */
    public function getPremium()
    {
        return $this->response['premium'] ?: false;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
