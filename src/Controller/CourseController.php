<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Enum\PaymentStatus;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;

use App\Service\ArrayService;
use App\Service\BillingClient;
use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/course")
 */
class CourseController extends AbstractController
{
    private BillingClient $billingClient;
    private Security $security;

    public function __construct(BillingClient $billingClient, Security $security)
    {
        $this->billingClient = $billingClient;
        $this->security = $security;
    }

    /**
     * @Route("/", name="app_course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository): Response
    {
        $courses = ArrayService::arrayByKey($courseRepository->findAllArray(), 'code');

        $billingCourses = ArrayService::arrayByKey($this->billingClient->getCourses(), 'code');

        foreach ($courses as $code => $course) {
            if (isset($billingCourses[$code])) {
                if ($billingCourses[$code]['type'] === PaymentStatus::RENT_NAME) {
                    $courses[$code]['type'] = PaymentStatus::RENT_NAME;
                    $courses[$code]['price_msg'] = $billingCourses[$code]['price'] . '₽ в неделю';
                } elseif ($billingCourses[$code]['type'] === PaymentStatus::BUY_NAME) {
                    $courses[$code]['type'] = PaymentStatus::BUY_NAME;
                    $courses[$code]['price_msg'] = $billingCourses[$code]['price'] . '₽';
                } elseif ($billingCourses[$code]['type'] === PaymentStatus::FREE_NAME) {
                    $courses[$code]['type'] = PaymentStatus::FREE_NAME;
                    $courses[$code]['price_msg'] = 'Бесплатный';
                }
            }
        }
        if ($this->isGranted('ROLE_USER')) {
            $user = $this->security->getUser();


            $transactions = ArrayService::arrayByKey($this->billingClient
                ->getTransactions($user->getApiToken(), 'payment', null, true), 'code');
            foreach ($courses as $code => $course) {
                if (isset($transactions[$code])) {
                    if ($course['type'] === PaymentStatus::RENT_NAME) {
                        $expiresAt = $transactions[$code]['expires'];
                        $courses[$code]['price_msg'] = 'Арендовано до '
                            . date('d/m/y H:i:s', strtotime($expiresAt['date']));
                    } elseif ($course['type'] === PaymentStatus::BUY_NAME) {
                        $courses[$code]['price_msg'] = 'Куплено';
                    }
                }
            }
        }
        return $this->render('course/index.html.twig', [
            'courses' => $courses,
        ]);
    }

    /**
     * @Route("/{id}/new_lesson", name="app_lesson_new", methods={"GET", "POST"})
     */
    public function newLesson(Request $request, Course $course, ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, ['attr' => ['class' => 'row justify-content-center ']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->getRepository(Lesson::class)->add($lesson, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/new", name="app_course_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course, ['attr' => ['class' => 'row justify-content-center ']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course, true);

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,

        ]);
    }

    /**
     * @Route("/{id}", name="app_course_show", methods={"GET"})
     */
    public function show(Request $request, Course $course): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }
        $billingCourse = $this->billingClient->getCourse($course->getCode());
        $billingUser = $this->billingClient->getCurrentUser($user->getApiToken());
        $transactions = $this->billingClient->getTransactions(
            $user->getApiToken(),
            'payment',
            $course->getCode(),
            true
        );
        $course = [
            'id' => $course->getId(),
            'code' => $course->getCode(),
            'name' => $course->getName(),
            'description' => $course->getDescription(),
            'lessons' => $course->getLessons(),
            'type' => $billingCourse['type'],
            'isPaid' => false,
        ];
        if (isset($billingCourse['price'])) {
            $course['price'] = $billingCourse['price'];
        }
        if ($billingCourse['type'] === 'rent') {
            $course['price_msg'] = $billingCourse['price'] . '₽ в неделю';
        } elseif ($billingCourse['type'] === 'buy') {
            $course['price_msg'] = $billingCourse['price'] . '₽';
        } elseif ($billingCourse['type'] === 'free') {
            $course['price_msg'] = 'Бесплатный';
        }
        if (count($transactions) > 0) {
            $transaction = $transactions[count($transactions) - 1];
            $course['isPaid'] = true;
            if ($billingCourse['type'] === 'rent') {
                $course['price_msg'] = 'Арендовано до ' .
                    date('d/m/y H:i:s', strtotime($transaction['expires']['date']));
            } elseif ($billingCourse['type'] === 'buy') {
                $course['price_msg'] = 'Куплено';
            }
        }
        $status=null;
        if ($request->query->get('status') != null) {
            $status = PaymentStatus::PAY_NAMES[$request->query->get('status')];
        }
        return $this->render('course/show.html.twig', [
            'course' => $course,
            'status' => $status,
            'billingUser' => $billingUser,
        ]);
    }

    /**
     * @Route("/{id}/pay", name="app_course_pay", methods={"POST"})
     */
    public function pay(Request $request, Course $course): Response
    {
        if (!$this->isCsrfTokenValid('pay' . $course->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }
        $user = $this->security->getUser();
        $status = null;
        try {
            $responce = $this->billingClient->payForCourse($user->getApiToken(), $course->getCode());
            if ($responce['success'] == true) {
                $status = PaymentStatus::OK;
            }
        } catch (LogicException $e) {
            $status = PaymentStatus::ALREADY_PAID;
        } catch (MissingResourceException $e) {
            $status = PaymentStatus::NO_MONEY;
        }
        return $this->redirectToRoute(
            'app_course_show',
            ['id' => $course->getId(),
                'status' => $status],
            Response::HTTP_SEE_OTHER
        );
    }

    /**
     * @Route("/{id}/edit", name="app_course_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        $form = $this->createForm(CourseType::class, $course, ['attr' => ['class' => 'row justify-content-center ']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $courseRepository->add($course, true);
            return $this->redirectToRoute('app_course_show', [
                'id' => $course->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_delete", methods={"POST"})
     */
    public function delete(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }


}