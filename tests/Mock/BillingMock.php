<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\DTO\UserDTO;
use App\Security\User;
use App\Tests\AbstractTest;
use App\Service\BillingClient;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BillingMock extends BillingClient
{
    private static array $user = [
        'username' => 'user@mail.ru',
        'password' => '123456',
        'roles' => ['ROLE_USER'],
        'balance' => 100.0,
    ];

    private static array $admin = [
        'username' => 'admin@mail.ru',
        'password' => '123456',
        'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        'balance' => 1000.0,
    ];

    public static array $new_user = [
        'username' => 'newUser@mail.ru',
        'password' => '123456',
        'roles' => ['ROLE_USER'],
        'balance' => 0.0,
    ];

    private static array $courses = [
        [
            'code' => 'car-1',
            'type' => 'free'
        ],
        [
            'code' => 'cooking-1',
            'type' => 'rent',
            'price' => 10
        ],
        [
            'code' => 'cleanCourse-1',
            'type' => 'buy',
            'price' => 20
        ],
        [
            'code' => 'test_buy',
            'type' => 'buy',
            'price' => 20
        ],
        [
            'code' => 'test_rent',
            'type' => 'rent',
            'price' => 20
        ],
        [
            'code' => 'код-тест-1',
            'type' => 'free'
        ]
    ];

    private static array $transactions = [
        [
            "id" => 1,
            "code" => "cleanCourse-1",
            "type" => "0",
            "amount" => 30,
            "created" => [
                "date" => "2023-07-05 04:21:26.080125",
                "timezone_type" => 3,
                "timezone" => "UTC"
            ],
            "expires" => null
        ],
        [
            "id" => 2,
            "code" => "cooking-1",
            "type" => "0",
            "amount" => 20,
            "created" => [
                "date" => "2023-07-05 04:21:26.080125",
                "timezone_type" => 3,
                "timezone" => "UTC"
            ],
            "expires" => [
                "date" => "2023-07-05 04:21:26.080125",
                "timezone_type" => 3,
                "timezone" => "UTC"
            ]
        ],

    ];


    private static string $newToken;

    public function __construct(ValidatorInterface $validator)
    {


        $created = (new DateTime());
        $expires = $created;

        self::$user['token'] = $this->generateToken(self::$user['roles'], self::$user['username']);
        self::$admin['token'] = $this->generateToken(self::$admin['roles'], self::$admin['username']);
        self::$new_user['token'] = $this->generateToken(self::$user['roles'], 'test@example.com');
        self::$new_user['refresh_token'] = $this->generateRefreshToken(self::$user['roles'], 'test@example.com');
        self::$user['refresh_token'] = $this->generateRefreshToken(self::$user['roles'], self::$user['username']);
        self::$admin['refresh_token'] = $this->generateRefreshToken(self::$admin['roles'], self::$admin['username']);

        parent::__construct($validator);
    }

    private function generateToken($roles, $username): string
    {
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.' . $query . '.signature';
    }

    private function generateRefreshToken($roles, $username): string
    {
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 month'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.' . $query . '.signature';
    }

    public function auth($credentials)
    {
        $users_to_check = [self::$user, self::$admin];

        foreach ($users_to_check as $user) {
            if ($credentials["username"] == $user["username"] && $credentials["password"] == $user["password"]) {
                return [
                    'token' => $user['token'],
                    'refresh_token' => $user['refresh_token'],
                ];
            }
        }
        throw new CustomUserMessageAuthenticationException('Неправильные логин или пароль');
    }

    public function register($credentials)
    {
        $users_to_check = [self::$user, self::$admin];
        foreach ($users_to_check as $user) {
            if ($credentials["username"] == $user["username"]) {
                throw new CustomUserMessageAuthenticationException('Данная почта уже зарегистрирована');
            }
        }
        return [
            'token' => self::$new_user['token'],
            'refresh_token' => self::$new_user['refresh_token'],
            'roles' => ['ROLE_USER']
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        [$exp, $email, $roles] = User::jwtDecode($refreshToken);
        if (self::$user['username'] == $email) {
            return ['token' => self::$user['token'], 'refresh_token' => self::$user['refresh_token']];
        }
        if (self::$admin['username'] == $email) {
            return ['token' => self::$admin['token'], 'refresh_token' => self::$admin['refresh_token']];
        }
        return ['error' => 'неверные данные'];
    }

    public function getCurrentUser(string $token)
    {
        $users_to_check = [self::$user, self::$admin];
        foreach ($users_to_check as $user) {
            if ($token == $user["token"]) {
                return new UserDTO($user["username"], $user["roles"], $user['balance']);
            }
        }
        throw new CustomUserMessageAuthenticationException('Некорректный JWT токен');
    }

    public function authClient($client, $username, $password)
    {
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Авторизоваться')->form();
        $form['email'] = $username;
        $form['password'] = $password;

        $client->submit($form);
    }

    public function authAsAdmin($client)
    {
        self::authClient($client, self::$admin['username'], self::$admin['password']);
    }

    public function getCourse($code)
    {
        foreach (self::$courses as $course) {
            if ($course['code'] == $code) {
                $result = ['code' => $course['code'], 'type' => $course['type'],];
                if ($course['type'] != 'free') {
                    $result['price'] = $course['price'];
                }
                return $result;
            }
        }
        throw new CustomUserMessageAccountStatusException('Нет курса с таким кодом');
    }

    public function getCourses()
    {
        return self::$courses;
    }

    public function newCourse($token, $course)
    {
        return ['success' => true];
    }

    public function editCourse($token, $code, $course)
    {
        foreach (self::$courses as $course) {
            if ($course['code'] == $code) {
                return ['success' => true];
            }
        }
        throw new CustomUserMessageAccountStatusException('Нет курса с таким кодом');
    }

    public function payForCourse($refreshToken, $code)
    {
        [$exp, $email, $roles] = User::jwtDecode($refreshToken);
        $pay_course = [];
        foreach (self::$courses as $course) {
            if ($course['code'] == $code) {
                $pay_course = ['code' => $course['code'], 'type' => $course['type'], 'price' => $course['price']];
            }
        }
        // if (self::$user['username'] == $email) {
        //     self::$user['balance']-=$pay_course['price'];
        // }
        // if (self::$admin['username'] == $email) {
        //     self::$admin['balance']-=$pay_course['price'];
        // }
        if (self::$new_user['username'] == $email) {
            throw new CustomUserMessageAccountStatusException('Недостаточно денег');
        }
        return ['success' => true];
    }

    public function getTransactions($token, $type = null, $code = null, $skip_expired = false): array
    {
        [$exp, $email, $roles] = User::jwtDecode($token);
        $result = [];
        foreach (self::$transactions as $transaction) {
            if ($transaction['code'] == $code) {
                $result[] = $transaction;
            }
        }
        return $result;
    }
}