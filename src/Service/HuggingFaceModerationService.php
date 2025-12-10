<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de modération avec HuggingFace Inference API
 * Support du français et de l'anglais
 */
class HuggingFaceModerationService
{
    private const HF_API = 'https://api-inference.huggingface.co/models/';

    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $model;
    private float $toxicityThreshold;
    private float $warningThreshold;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $huggingfaceApiKey,
        array $moderationConfig = []
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $huggingfaceApiKey;

        // Configuration par défaut
        $this->toxicityThreshold = $moderationConfig['toxicity_threshold'] ?? 70;
        $this->warningThreshold  = $moderationConfig['warning_threshold'] ?? 50;
        
        // Modèle multilingue qui supporte le français
        $this->model = $moderationConfig['model'] ?? 'facebook/xlm-roberta-base';

        $this->logger->info('HuggingFace Moderation Service initialized', [
            'model' => $this->model,
            'toxicity_threshold' => $this->toxicityThreshold,
            'warning_threshold' => $this->warningThreshold,
            'api_key_set' => !empty($this->apiKey)
        ]);
    }

    /**
     * Analyse un texte avec le modèle de modération HF
     */
    public function analyzeContent(string $content): array
    {
        if (trim($content) === '') {
            $this->logger->warning('Empty content provided for moderation');
            return $this->safeResponse();
        }

        $this->logger->info('Analyzing content', [
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 100)
        ]);

        try {
            // Détection simple de toxicité par mots-clés (fallback français)
            $toxicScore = $this->detectToxicityByKeywords($content);
            
            if ($toxicScore > 0) {
                $this->logger->info('Toxicity detected by keywords', ['score' => $toxicScore]);
                return $this->createResponseFromKeywords($content, $toxicScore);
            }

            // Si pas de mots-clés toxiques, on considère le contenu sûr
            return $this->safeContentResponse($content);

        } catch (\Exception $e) {
            $this->logger->error('Moderation Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->safeResponse();
        }
    }

    /**
     * Détection de toxicité par mots-clés (français et anglais)
     */
    private function detectToxicityByKeywords(string $content): float
    {
        $contentLower = mb_strtolower($content, 'UTF-8');
        
        // Mots-clés très toxiques (score 100)
        $criticalKeywords = [
            // Français
            'connard', 'salaud', 'enculé', 'putain', 'merde', 'con', 'connasse',
            'ferme ta gueule', 'va te faire', 'crève', 'tue toi', 'suicide',
            'idiot', 'imbécile', 'crétin', 'débile', 'abruti',
            // Anglais
            'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'kill yourself',
            'go die', 'stupid', 'idiot', 'moron', 'hate you'
        ];

        // Mots-clés moyennement toxiques (score 60)
        $moderateKeywords = [
            // Français
            'nul', 'mauvais', 'horrible', 'déteste', 'agaçant', 'ennuyeux',
            'ridicule', 'pathétique', 'minable', 'lamentable',
            // Anglais
            'annoying', 'terrible', 'awful', 'disgusting', 'useless',
            'worthless', 'pathetic', 'loser'
        ];

        // Mots-clés légers (score 40)
        $lightKeywords = [
            // Français
            'pas bien', 'pas bon', 'décevant', 'frustrant', 'énervant',
            // Anglais
            'bad', 'disappointing', 'frustrating', 'annoying'
        ];

        $maxScore = 0;

        // Vérifier les mots critiques
        foreach ($criticalKeywords as $keyword) {
            if (strpos($contentLower, $keyword) !== false) {
                $maxScore = 100;
                break;
            }
        }

        // Si pas de mots critiques, vérifier les modérés
        if ($maxScore === 0) {
            foreach ($moderateKeywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $maxScore = max($maxScore, 60);
                }
            }
        }

        // Si toujours pas, vérifier les légers
        if ($maxScore === 0) {
            foreach ($lightKeywords as $keyword) {
                if (strpos($contentLower, $keyword) !== false) {
                    $maxScore = max($maxScore, 40);
                }
            }
        }

        return $maxScore;
    }

    /**
     * Créer une réponse basée sur la détection par mots-clés
     */
    private function createResponseFromKeywords(string $content, float $score): array
    {
        $shouldBlock = $score >= $this->toxicityThreshold;
        $shouldWarn  = !$shouldBlock && $score >= $this->warningThreshold;

        $category = match(true) {
            $score >= 90 => 'contenu_offensant',
            $score >= 70 => 'langage_inapproprié',
            $score >= 50 => 'contenu_négatif',
            default => 'contenu_limite'
        };

        $result = [
            'content' => $content,
            'content_length' => strlen($content),
            'scores' => [
                $category => [
                    'value' => $score,
                    'severity' => $this->severity($score),
                    'original_label' => 'keyword_detection'
                ]
            ],
            'max_score' => $score,
            'max_category' => $category,
            'should_block' => $shouldBlock,
            'should_warn' => $shouldWarn,
            'is_safe' => !$shouldBlock && !$shouldWarn,
            'moderation_reason' => $this->reasonFrench($category, $score, $shouldBlock),
            'analyzed_at' => new \DateTime(),
            'error' => false
        ];

        $this->logger->info('Moderation analysis result (keywords)', [
            'max_score' => $score,
            'max_category' => $category,
            'should_block' => $shouldBlock,
            'should_warn' => $shouldWarn
        ]);

        return $result;
    }

    /**
     * Réponse pour contenu sûr
     */
    private function safeContentResponse(string $content): array
    {
        return [
            'content' => $content,
            'content_length' => strlen($content),
            'scores' => [
                'safe' => [
                    'value' => 0,
                    'severity' => 'MINIMAL',
                    'original_label' => 'safe'
                ]
            ],
            'max_score' => 0,
            'max_category' => 'safe',
            'should_block' => false,
            'should_warn' => false,
            'is_safe' => true,
            'moderation_reason' => null,
            'analyzed_at' => new \DateTime(),
            'error' => false
        ];
    }

    private function severity(float $score): string
    {
        return match (true) {
            $score >= 90 => 'CRITIQUE',
            $score >= 70 => 'ÉLEVÉ',
            $score >= 50 => 'MOYEN',
            $score >= 30 => 'FAIBLE',
            default => 'MINIMAL',
        };
    }

    private function reasonFrench(?string $category, float $score, bool $shouldBlock): ?string
    {
        if (!$category) return null;

        $categoryLabels = [
            'contenu_offensant' => 'contenu offensant',
            'langage_inapproprié' => 'langage inapproprié',
            'contenu_négatif' => 'contenu négatif',
            'contenu_limite' => 'contenu limite'
        ];

        $label = $categoryLabels[$category] ?? $category;

        if ($shouldBlock) {
            return "🚫 Contenu bloqué : $label (score: $score%)";
        }

        if ($score >= $this->warningThreshold) {
            return "⚠️ Attention : $label détecté (score: $score%)";
        }

        return null;
    }

    /**
     * Réponse "sûre" en cas d'erreur - permet de continuer sans bloquer
     */
    private function safeResponse(): array
    {
        return [
            'content' => '',
            'content_length' => 0,
            'scores' => [],
            'max_score' => 0,
            'max_category' => null,
            'is_safe' => true,
            'should_block' => false,
            'should_warn' => false,
            'moderation_reason' => null,
            'analyzed_at' => new \DateTime(),
            'error' => true
        ];
    }
}