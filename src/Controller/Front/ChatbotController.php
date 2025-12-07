<?php

namespace App\Controller\Front;

use App\Entity\Cours;
use App\Service\CoursChatbot;
use App\Service\GlobalChatbot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chatbot')]
final class ChatbotController extends AbstractController
{
    #[Route('/cours/{id}', name: 'app_chatbot_cours', methods: ['POST'])]
    public function askForCours(Cours $cour, Request $request, CoursChatbot $chatbot): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $question = isset($data['message']) ? (string) $data['message'] : '';

        $answer = $chatbot->answerForCours($cour, $question);

        return new JsonResponse([
            'answer' => $answer,
        ]);
    }

    #[Route('/global', name: 'app_chatbot_global', methods: ['POST'])]
    public function askGlobal(Request $request, GlobalChatbot $chatbot): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $question = isset($data['message']) ? (string) $data['message'] : '';

        $answer = $chatbot->answer($question);

        return new JsonResponse([
            'answer' => $answer,
        ]);
    }
}
