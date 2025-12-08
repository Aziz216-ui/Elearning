<?php

namespace App\Controller;

use App\Service\HuggingFaceModerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestModerationController extends AbstractController
{
    #[Route('/test/moderation', name: 'test_moderation')]
    public function test(HuggingFaceModerationService $moderationService): JsonResponse
    {
        // Phrases de test EN FRANÇAIS
        $tests = [
            'safe' => 'Bonjour, comment allez-vous ? C\'est une belle journée !',
            'medium' => 'Ce post est vraiment nul et décevant.',
            'toxic' => 'Tu es un idiot, ferme ta gueule connard !',
            'very_toxic' => 'Va crever enculé, je te déteste espèce de merde !'
        ];

        $results = [];

        foreach ($tests as $type => $text) {
            $analysis = $moderationService->analyzeContent($text);
            $results[$type] = [
                'text' => $text,
                'max_score' => $analysis['max_score'] ?? 0,
                'max_category' => $analysis['max_category'] ?? 'N/A',
                'should_block' => $analysis['should_block'] ?? false,
                'should_warn' => $analysis['should_warn'] ?? false,
                'is_safe' => $analysis['is_safe'] ?? true,
                'moderation_reason' => $analysis['moderation_reason'] ?? null,
                'all_scores' => $analysis['scores'] ?? [],
                'has_error' => $analysis['error'] ?? false
            ];
        }

        return $this->json([
            'status' => 'Test terminé',
            'timestamp' => date('Y-m-d H:i:s'),
            'langue' => 'français',
            'results' => $results
        ], 200, [], ['json_encode_options' => JSON_PRETTY_PRINT]);
    }
}