<?php

namespace App\Service;

use App\DTO\UserDTO;
use App\Exception\CourseAlreadyExistException;
use App\Exception\CourseValidationException;
use JMS\Serializer\Serializer;
use App\Exception\BillingException;
use JMS\Serializer\SerializerBuilder;
use App\Exception\BillingUnavailableException;
use PHPUnit\Util\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class BillingClient
{
    private ValidatorInterface $validator;
    private Serializer $serializer;
    protected const AUTH_PATH = '/auth';
    protected const REGISTER_PATH = '/register';
    protected const GET_CURRENT_USER_PATH = '/users/current';
    protected const REFRESH_TOKEN = '/token/refresh';
    protected const GET_COURSES = '/courses';
    protected const GET_TRANSACTIONS = '/transactions';

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
        $this->serializer = SerializerBuilder::create()->build();
    }

    public function getTransactions($token, $type = null, $code = null, $skip_expired = false)
    {
        $response = $this->jsonRequest(
            'GET',
            self::GET_TRANSACTIONS,
            [
                'type' => $type,
                'code' => $code,
                'skip_expired' => $skip_expired
            ],
            [],
            ['Authorization' => 'Bearer ' . $token]
        );
        if ($response['code'] == Response::HTTP_OK) {
            return json_decode($response['body'], true);
        } else {
            $json = json_decode($response['body'], true);
            if (!empty($json['message']) && !empty($response['code'])) {
                throw new BillingException(
                    $json['message'],
                    $response['code']
                );
            }
        }
    }

    public function getCourses()
    {
        $response = $this->jsonRequest(
            'GET',
            self::GET_COURSES,
        );

        if ($response['code'] == Response::HTTP_OK) {
            return json_decode($response['body'], true);
        } else {
            throw new BillingUnavailableException($response['message']);
        }
    }

    public function auth($credentials)
    {
        $response = $this->jsonRequest(
            'POST',
            self::AUTH_PATH,
            [],
            $credentials,
        );

        if ($response['code'] === 200) {
            return json_decode($response['body'], true);
        } else {
            throw new CustomUserMessageAuthenticationException(json_decode($response['body'], true)['message']);
        }
    }

    public function register($credentials)
    {
        $response = $this->jsonRequest(
            'POST',
            self::REGISTER_PATH,
            [],
            $credentials,
        );
        if ($response['code'] == Response::HTTP_CREATED) {
            return json_decode($response['body'], true);
        } else {
            throw new CustomUserMessageAuthenticationException(
                json_decode($response['body'], true)['message']
            );
        }
    }

    public function getCourse($code)
    {
        $response = $this->jsonRequest(
            'GET',
            self::GET_COURSES . '/' . $code,
        );

        if ($response['code'] === 200) {
            return json_decode($response['body'], true);
        } elseif ($response['code'] === Response::HTTP_NOT_FOUND) {
            throw new ResourceNotFoundException(json_decode($response['body'], true)['message'], $response['code']);
        } else {
            throw new BillingUnavailableException(json_decode($response['body'], true)['message'], $response['code']);
        }
    }

    public function payForCourse($token, $code)
    {
        $response = $this->jsonRequest(
            'POST',
            self::GET_COURSES . '/' . $code . '/pay',
            [],
            [],
            ['Authorization' => 'Bearer ' . $token]
        );
        if ($response['code'] == Response::HTTP_OK) {
            return json_decode($response['body'], true);
        } elseif ($response['code'] === Response::HTTP_UNAUTHORIZED) {
            $json = json_decode($response['body'], true);
            if (!empty($json['message']) && !empty($response['code'])) {
                throw new CustomUserMessageAuthenticationException(
                    json_decode($response['body'], true)['message'],
                    $json['message'],
                    $response['code']
                );
            }
        } elseif ($response['code'] === Response::HTTP_NOT_FOUND) {
            throw new ResourceNotFoundException(json_decode($response['body'], true)['message'], $response['code']);
        } elseif ($response['code'] === Response::HTTP_NOT_ACCEPTABLE) {
            throw new MissingResourceException(json_decode($response['body'], true)['message'], $response['code']);
        } elseif ($response['code'] === Response::HTTP_CONFLICT) {
            throw new \LogicException(json_decode($response['body'], true)['message'], $response['code']);
        } else {
            throw new BillingUnavailableException();
        }
    }

    public function getCurrentUser(string $token)
    {
        $response = $this->jsonRequest(
            'GET',
            self::GET_CURRENT_USER_PATH,
            [],
            [],
            ['Authorization' => 'Bearer ' . $token]
        );
        if ($response['code'] == Response::HTTP_OK) {
            $userDto = $this->serializer->deserialize($response['body'], UserDTO::class, 'json');
            $errors = $this->validator->validate($userDto);
            if (count($errors) > 0) {
                throw new BillingUnavailableException('User data is not valid');
            }
            return $userDto;
        } elseif ($response['code'] === Response::HTTP_UNAUTHORIZED) {
            throw new CustomUserMessageAuthenticationException($response['code']);
        } else {
            throw new BillingUnavailableException();
        }
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = $this->jsonRequest(
            'POST',
            self::REFRESH_TOKEN,
            [],
            ['refresh_token' => $refreshToken],
        );
        if ($response['code'] == Response::HTTP_OK) {
            return json_decode($response['body'], true);
        } elseif ($response['code'] === Response::HTTP_UNAUTHORIZED) {
            throw new CustomUserMessageAuthenticationException(json_decode($response['body'], true)['message']);
        } else {
            throw new CustomUserMessageAuthenticationException(
                json_decode($response['body'], true)['message']
            );
        }
    }

    public function editCourse($token, $code, $course)
    {
        $response = $this->jsonRequest(
            'POST',
            self::GET_COURSES . '/' . $code . '/edit',
            [],
            $course,
            ['Authorization' => 'Bearer ' . $token]
        );
        if ($response['code'] == Response::HTTP_OK) {
            return json_decode($response['body'], true);
        } elseif ($response['code'] === Response::HTTP_UNAUTHORIZED) {
            $json = json_decode($response['body'], true);
            if (!empty($json['message']) && !empty($response['code'])) {
                throw new CustomUserMessageAuthenticationException(
                    $json['message'],
                    $response['code']
                );
            }
        } elseif ($response['code'] === Response::HTTP_FORBIDDEN) {
            throw new CourseValidationException(json_decode($response['body'], true)['message'], $response['code']);
        } elseif ($response['code'] === Response::HTTP_CONFLICT) {
            throw new CourseAlreadyExistException(json_decode($response['body'], true)['message'], $response['code']);
        } else {
            throw new CourseValidationException(json_decode($response['body'], true)['message'], $response['code']);
        }
    }

    public function newCourse($token, $course)
    {
        $response = $this->jsonRequest(
            'POST',
            self::GET_COURSES . '/' . 'new',
            [],
            $course,
            ['Authorization' => 'Bearer ' . $token]
        );
        if ($response['code'] == Response::HTTP_CREATED) {
            return json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        } elseif ($response['code'] === Response::HTTP_UNAUTHORIZED) {
            throw new CustomUserMessageAuthenticationException(
                json_decode($response['body'], true)['message'],
                $response['code']
            );
        } elseif ($response['code'] === Response::HTTP_FORBIDDEN) {
            throw new CourseValidationException(json_decode($response['body'], true)['message'], $response['code']);
        } elseif ($response['code'] === Response::HTTP_CONFLICT) {
            throw new CourseAlreadyExistException(json_decode($response['body'], true)['message'], $response['code']);
        } else {
            throw new CourseValidationException(json_decode($response['body'], true)['message'], $response['code']);
        }
    }

    public function jsonRequest($method, string $path, $params = [], $body = [], $headers = [])
    {
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';
        return $this->request($method, $path, $params, $this->serializer->serialize($body, 'json'), $headers);
    }

    public function request(
        $method,
        string $path,
        $params = [],
        $body = [],
        $headers = []
    )
    {
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (count($params) > 0) {
            $path .= '?';

            $newParameters = [];
            foreach ($params as $name => $value) {
                $newParameters[] = $name . '=' . $value;
            }
            $path .= implode('&', $newParameters);
        }
        $route = $_ENV['BILLING_URL'] . $path;
        $query = curl_init($route);

        if ($method === 'POST' && !empty($body)) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        if (count($headers) > 0) {
            $curlHeaders = [];
            foreach ($headers as $name => $value) {
                $curlHeaders[] = $name . ': ' . $value;
            }
            $options[CURLOPT_HTTPHEADER] = $curlHeaders;
        }
        curl_setopt_array($query, $options);
        $response = curl_exec($query);
        if (curl_error($query)) {
            throw new BillingUnavailableException(curl_error($query));
        }
        $responseCode = curl_getinfo($query, CURLINFO_RESPONSE_CODE);
        curl_close($query);
        return [
            'code' => $responseCode,
            'body' => $response,
        ];
    }
}