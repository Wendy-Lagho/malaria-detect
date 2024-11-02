<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiService
{
    private $client;
    private $model;
    private $apiKey;

    public function __construct()
    {
        $this->model = 'gemini-pro-vision';
        $this->apiKey = config('services.google.gemini.key');
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout' => 30.0,
        ]);
    }

    /**
     * Analyze blood smear image for malaria parasites
     *
     * @param string $imagePath Path to the stored image
     * @return array Analysis results
     * @throws \Exception
     */
    public function analyzeMalariaImage(string $imagePath): array
    {
        try {
            // Validate image exists
            if (!Storage::exists($imagePath)) {
                throw new \Exception('Image file not found');
            }

            // Read and encode image
            $imageContent = Storage::get($imagePath);
            $mimeType = Storage::mimeType($imagePath);
            $base64Image = base64_encode($imageContent);

            // Validate image size (Gemini has a 4MB limit)
            if (strlen($imageContent) > 4 * 1024 * 1024) {
                throw new \Exception('Image size exceeds 4MB limit');
            }

            // Prepare request data
            $request = $this->prepareRequest($base64Image, $mimeType);

            // Make API call to Gemini
            $response = $this->makeGeminiRequest($request);

            // Process and structure the response
            return $this->processAnalysisResponse($response);

        } catch (\Exception $e) {
            Log::error('Malaria Analysis Error', [
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Image analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Prepare the request data for Gemini API
     *
     * @param string $base64Image
     * @param string $mimeType
     * @return array
     */
    private function prepareRequest(string $base64Image, string $mimeType): array
    {
        return [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->getMalariaAnalysisPrompt()
                        ],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            'safety_settings' => [
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ],
            'generation_config' => [
                'temperature' => 0.4,
                'top_p' => 0.8,
                'top_k' => 40,
                'max_output_tokens' => 1024
            ]
        ];
    }

    /**
     * Get the analysis prompt for Gemini
     *
     * @return string
     */
    private function getMalariaAnalysisPrompt(): string
    {
        return "You are a medical image analysis expert specializing in malaria detection. 
                Analyze this blood smear image for malaria parasites with extreme precision.
                
                Focus your analysis on:
                1. Clear presence or absence of malaria parasites
                2. Precise confidence level based on visual clarity and parasites visibility
                3. Identification of Plasmodium species if detected
                4. Current stage of the parasite lifecycle if visible
                5. Detailed findings about parasite morphology and characteristics
                6. Evidence-based medical recommendations
                
                Provide ONLY a JSON response in this exact format:
                {
                    \"detection\": boolean,
                    \"confidence\": number (0-100),
                    \"parasiteType\": string,
                    \"stage\": string,
                    \"findings\": string,
                    \"recommendations\": string[]
                }
                
                Rules:
                - Be clinically precise
                - Express uncertainty with lower confidence scores
                - Include specific morphological details in findings
                - Give actionable medical recommendations
                - Maintain strict JSON format";
    }

    /**
     * Make the actual API request to Gemini
     *
     * @param array $request
     * @return string JSON response
     * @throws \Exception
     */
    private function makeGeminiRequest(array $request): string
    {
        try {
            $endpoint = "models/{$this->model}:generateContent";
            
            $response = $this->client->post($endpoint, [
                'query' => ['key' => $this->apiKey],
                'json' => $request,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $responseBody = json_decode($response->getBody(), true);

            // Validate response structure
            if (!isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid response structure from Gemini API');
            }

            $content = $responseBody['candidates'][0]['content']['parts'][0]['text'];

            // Extract JSON from response
            $jsonStart = strpos($content, '{');
            $jsonEnd = strrpos($content, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('Could not find valid JSON in response');
            }

            $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
            $analysisData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON in response: ' . json_last_error_msg());
            }

            // Validate required fields
            if (!$this->validateResponse($analysisData)) {
                throw new \Exception('Response missing required fields');
            }

            return json_encode($analysisData);

        } catch (GuzzleException $e) {
            Log::error('Gemini API Request Failed', [
                'error' => $e->getMessage(),
                'request' => $request
            ]);
            throw new \Exception('Failed to communicate with Gemini API: ' . $e->getMessage());
        }
    }

    /**
     * Process and structure the API response
     *
     * @param string $response JSON string
     * @return array
     * @throws \Exception
     */
    private function processAnalysisResponse(string $response): array
    {
        try {
            $analysisData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
            }

            return [
                'detection' => (bool) ($analysisData['detection'] ?? false),
                'confidence' => (float) ($analysisData['confidence'] ?? 0),
                'parasiteType' => (string) ($analysisData['parasiteType'] ?? 'Unknown'),
                'stage' => (string) ($analysisData['stage'] ?? 'Unknown'),
                'findings' => (string) ($analysisData['findings'] ?? 'Analysis incomplete'),
                'recommendations' => (array) ($analysisData['recommendations'] ?? ['Please consult a medical professional']),
                'raw_response' => $response,
                'timestamp' => now(),
                'status' => 'completed'
            ];

        } catch (\Exception $e) {
            Log::error('Response Processing Error', [
                'error' => $e->getMessage(),
                'response' => $response
            ]);
            throw new \Exception('Failed to process analysis response: ' . $e->getMessage());
        }
    }

    /**
     * Validate the response format
     *
     * @param array $analysisData
     * @return bool
     */
    private function validateResponse(array $analysisData): bool
    {
        $requiredFields = [
            'detection' => 'boolean',
            'confidence' => 'numeric',
            'parasiteType' => 'string',
            'stage' => 'string',
            'findings' => 'string',
            'recommendations' => 'array'
        ];

        foreach ($requiredFields as $field => $type) {
            if (!isset($analysisData[$field])) {
                return false;
            }

            switch ($type) {
                case 'boolean':
                    if (!is_bool($analysisData[$field])) return false;
                    break;
                case 'numeric':
                    if (!is_numeric($analysisData[$field])) return false;
                    break;
                case 'string':
                    if (!is_string($analysisData[$field])) return false;
                    break;
                case 'array':
                    if (!is_array($analysisData[$field])) return false;
                    break;
            }
        }

        return true;
    }
}