<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\ArrayService;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/lesson")
 */
class LessonController extends AbstractController
{
    private BillingClient $billingClient;

    private Security $security;

    public function __construct(BillingClient $billingClient, Security $security)
    {
        $this->billingClient = $billingClient;
        $this->security = $security;
    }

    /**
     * @Route("/", name="app_lesson_index", methods={"GET"})
     */
    public function index(LessonRepository $lessonRepository): Response
    {
        return $this->render('lesson/index.html.twig', [
            'lessons' => $lessonRepository->findAll(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_lesson_show", methods={"GET"})
     */
    public function show(Lesson $lesson, CourseRepository $courseRepository): Response
    {
        $user = $this->security->getUser();
        try {
            $billingCourse = $this->billingClient->getCourse($lesson->getCourse()->getCode());
            $transactions = $this->billingClient
                ->getTransactions($user->getApiToken(), 'payment', $lesson->getCourse()->getCode(), true);
        } catch (BillingException $e) {
            $this->addFlash('notice', $e->getMessage());
            return $this->redirectToRoute('app_course_index');
        }

        if (count($transactions) > 0 or $billingCourse['type'] == 'free' || $this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->render('lesson/show.html.twig', [
                'lesson' => $lesson,
            ]);
        } else {
            return $this->redirectToRoute('app_course_show', ['id' => $lesson->getCourse()->getId()]);
        }
    }

    /**
     * @Route("/{id}/edit", name="app_lesson_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        $id_course = $lesson->getCourse()->getId();
        $form = $this->createForm(LessonType::class, $lesson, ['attr' => ['class' => 'row justify-content-center ']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->add($lesson, true);

            return $this->redirectToRoute('app_course_show', ['id' => $id_course], Response::HTTP_SEE_OTHER);
//            return $this->redirectToRoute('app_lesson_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_lesson_delete", methods={"POST"})
     */
    public function delete(Request $request, Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        $id_course = $lesson->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->request->get('_token'))) {
            $lessonRepository->remove($lesson, true);
        }

        return $this->redirectToRoute('app_course_show', ['id' => $id_course], Response::HTTP_SEE_OTHER);
//        return $this->redirectToRoute('app_lesson_index', [], Response::HTTP_SEE_OTHER);
    }
}
