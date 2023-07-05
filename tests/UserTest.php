<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingMock;
use App\DataFixtures\AppFixtures;

class UserTest extends AbstractTest
{
    public function urlProviderIsSuccessful(): \Generator
    {
        yield ['/login'];
        yield ['/register'];
    }

    /**
     * @dataProvider urlProviderIsSuccessful
     */
    public function testPageIsSuccessful($url): void
    {
        $client = $this->getClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }

    public function testSuccessfulAuth(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Авторизоваться');
        $login = $submitBtn->form([
            'email' => "admin@mail.ru",
            'password' => "123456",
        ]);
        $client->submit($login);
        $this->assertResponseRedirect();
    }

    public function testSuccessfulRegister(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/register');
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Зарегестрироваться');

        $form = $submitBtn->form([
            'register[email]' => "newUser@mail.ru",
            'register[password][first]' => "123456",
            'register[password][second]' => "123456"
        ]);
        $client->submit($form);
        $this->assertResponseRedirect();
    }
//    TODO тесты на неполные данные в форме
//    public function testNoEmailAuth(): void
//    {
//
//        $client = $this->getClient();
//        $crawler = $client->request('GET', '/login');
//        $this->assertResponseOk();
//
//        $submitBtn = $crawler->selectButton('Авторизоваться');
//        $login = $submitBtn->form([
//            'email' => "adn@mail.ru",
//            'password' => "123456",
//        ]);
//        $client->submit($login);
//        $this->assertResponseRedirect();
//        $this->assertEquals('Неправильные логин или пароль', $crawler->filter('.alert-danger')->text());
//    }
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}