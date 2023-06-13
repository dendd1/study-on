<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\Mock\BillingMock;

class LessonControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }

    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // страница урока
            $client->request('GET', '/lesson/' . $lesson->getId());
            $this->assertResponseOk();

            // страница редактирования урока
            $client->request('GET', '/lesson/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testSuccessfulLessonCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно создания
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $form = $crawler->selectButton('Сохранить')->form();

        // сохранение id курса
        $courseId = $form['lesson[course]']->getValue();

        // заполнение формы корректными значениями
        $form['lesson[name]'] = 'Тест-имя';
        $form['lesson[content]'] = 'Тест-контент';
        $form['lesson[number]'] = '10';
        $client->submit($form);

        // редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // сравнение имени и переход на страницу урока
        $this->assertSame($crawler->filter('.lesson')->last()->text(), 'Тест-имя');
        $crawler = $client->click($crawler->filter('.lesson')->last()->link());
        $this->assertResponseOk();

        // сравнение данных
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'Тест-имя');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Тест-контент');
    }

    public function testLessonFailedCreating(): void
    {
        // список курсов
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на страницу создания урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями (кроме номера)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Тест-имя',
            'lesson[content]' => 'Тест-контент',
            'lesson[number]' => '',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Номер урока обязателен'
        );

        // заполнение формы корректными значениями (кроме названия)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => '',
            'lesson[content]' => 'Тест-контент',
            'lesson[number]' => '1',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Название урока не должно быть пустым'
        );

        // заполнение формы корректными значениями (кроме контента)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Тест-имя',
            'lesson[content]' => '',
            'lesson[number]' => '1',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Контент обязателен'
        );

        // заполнение формы корректными значениями (кроме номера)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Тест-имя',
            'lesson[content]' => 'Тест-контент',
            'lesson[number]' => '10001',
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Порядковый номер урока должен быть между 1 и 10000'
        );

        // заполнение формы корректными значениями (кроме названия)
        $lessonCreatingForm = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => str_repeat("test", 64),
            'lesson[content]' => 'Тест-контент',
            'lesson[number]' => 1,
        ]);
        $client->submit($lessonCreatingForm);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Название урока не может содержать более 255 символов'
        );
    }

    public function testLessonSuccessfulEditing(): void
    {
        // список курсов
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно редактирования урока
        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();
        $form = $crawler->selectButton('Сохранить')->form();

        // сохранение id курса
        $courseId = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy([
                'id' => $form['lesson[course]']->getValue(),
            ])->getId();

        // заполнение формы корректными значениями
        $form['lesson[name]'] = 'Тест-имя';
        $form['lesson[content]'] = 'Тест-контент';
        $form['lesson[number]'] = '10';
        $client->submit($form);

        // редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // сравнение имени и переход на страницу урока
        $this->assertSame($crawler->filter('.lesson')->last()->text(), 'Тест-имя');
        $link = $crawler->filter('.lesson')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // сравнение данных
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'Тест-имя');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Тест-контент');
    }

    public function testLessonFailedEditing(): void
    {
        // список курсов
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на окно редактирования урока
        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнение формы корректными значениями (кроме номера)
        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Тест-имя',
            'lesson[content]' => 'Тест-контент',
            'lesson[number]' => '',
        ]);
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Номер урока обязателен'
        );

        // заполнение формы корректными значениями (кроме названия)
        $form['lesson[name]'] = '';
        $form['lesson[content]'] = 'Тест-контент';
        $form['lesson[number]'] = '10';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Название урока не должно быть пустым'
        );

        // заполнение формы корректными значениями (кроме контента)
        $form['lesson[name]'] = 'Тест-имя';
        $form['lesson[content]'] = '';
        $form['lesson[number]'] = '10';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Контент обязателен'
        );

        // заполнение формы корректными значениями (кроме контента)
        $form['lesson[name]'] = 'Тест-имя';
        $form['lesson[content]'] = 'Тест-контент';
        $form['lesson[number]'] = '10001';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Порядковый номер урока должен быть между 1 и 10000'
        );

        // заполнение формы корректными значениями (кроме имени)
        $form['lesson[name]'] = str_repeat("test", 64);
        $form['lesson[content]'] = 'Тест-контент';
        $form['lesson[number]'] = '10';
        $client->submit($form);
        $this->assertResponseCode(422);

        // сравнение текста ошибки
        $this->assertSelectorTextContains(
            'li',
            'Название урока не может содержать более 255 символов'
        );
    }

    public function testLessonDeleting(): void
    {
        // список курсов
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // переход на первый курс
        $link = $crawler->filter('.course-show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на первый урок
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // переход на страницу редактирования
        $crawler = $client->click($crawler->selectLink('Редактировать')->link());
        $this->assertResponseOk();
        // сохранение информации о курсе
        $form = $crawler->selectButton('Сохранить')->form();
        $course = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);
        // сохранение количества уроков
        $countBeforeDeleting = count($course->getLessons());

        // переход назад к уроку
        $crawler = $client->click($crawler->selectLink('Назад к уроку')->link());
        $this->assertResponseOk();

        //удаление урока
        $client->submitForm('Удалить');
        $this->assertSame($client->getResponse()->headers->get('location'), '/course/' . $course->getId());
        $crawler = $client->followRedirect();

        // сравнение количества уроков
        $this->assertCount($countBeforeDeleting - 1, $crawler->filter('.lesson'));
    }
}
