<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

/**
 * Load Google Cloud credentials from JSON file
 */
function loadGoogleCredentials($credentialsPath) {
    if (!file_exists($credentialsPath)) {
        throw new Exception('Credentials file not found: ' . $credentialsPath);
    }
    
    $credentials = json_decode(file_get_contents($credentialsPath), true);
    if (!$credentials) {
        throw new Exception('Invalid credentials file format');
    }
    
    return $credentials;
}

/**
 * Base64 URL encoding for JWT
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Get Google Cloud access token using service account
 */
function getAccessToken($credentials) {
    $client_email = $credentials['client_email'];
    $private_key = $credentials['private_key'];
    $token_uri = $credentials['token_uri'];
    
    // Create JWT header
    $jwt_header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];
    
    // Create JWT claim set
    $jwt_claim = [
        'iss' => $client_email,
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => $token_uri,
        'exp' => time() + 3600,
        'iat' => time()
    ];
    
    // Encode JWT
    $jwt = base64url_encode(json_encode($jwt_header)) . '.' . base64url_encode(json_encode($jwt_claim));
    
    // Sign JWT
    openssl_sign($jwt, $signature, $private_key, 'sha256WithRSAEncryption');
    $jwt .= '.' . base64url_encode($signature);
    
    // Request access token
    $curl = curl_init($token_uri);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 200) {
        error_log("Token request failed with HTTP {$httpCode}: " . $response);
        throw new Exception('Failed to obtain access token');
    }
    
    $token_response = json_decode($response, true);
    return $token_response['access_token'] ?? null;
}

/**
 * Detect elephants using Vertex AI
 */
function detectElephants($imageBase64, $accessToken, $projectId) {
    // Vertex AI endpoint for Gemini Pro Vision
    $location = 'us-central1';
    $endpoint = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/gemini-2.0-flash-exp:generateContent";
    
    // System instruction for elephant detection
    $system_instruction = "You are an expert wildlife biologist specializing in elephant conservation and identification. 
Analyze the provided image and determine if elephants are present. If elephants are detected, provide comprehensive information about them.

Please structure your response as follows:

**Detection Result:** [Elephants Detected / No Elephants Detected]

If elephants are detected, provide:

**Species Identification:**
• Species: [African Bush/Forest Elephant or Asian Elephant]
• Scientific Name: [Provide the scientific name]
• Distinguishing Features: [List key identifying features observed]

**Physical Characteristics:**
• Estimated Age Group: [Calf/Juvenile/Adult/Elder]
• Sex: [Male/Female/Cannot Determine] 
• Notable Features: [Tusks, ear shape, body size, skin condition]
• Estimated Weight Range: [Provide estimate based on visible size]

**Behavioral Observations:**
• Activity: [What the elephant(s) appear to be doing]
• Group Size: [Solitary/Small group/Herd with number]
• Social Structure: [If multiple elephants, describe apparent relationships]

**Conservation Status:**
• IUCN Status: [Current conservation status]
• Primary Threats: [List main conservation threats]
• Population Trend: [Increasing/Stable/Decreasing]

**Habitat Assessment:**
• Environment Type: [Describe visible habitat]
• Geographic Region: [If identifiable from context]
• Habitat Quality: [Assessment based on visible conditions]

**Additional Notes:**
• Health Assessment: [Any visible health indicators]
• Human-Wildlife Interaction: [If any signs are visible]
• Conservation Importance: [Brief note on why this observation matters]

If no elephants are detected:
• Explain what is visible in the image instead
• Suggest why the image might have been submitted (similar animals, unclear subjects, etc.)

Keep the response informative yet concise, using bullet points for clarity.";
    
    // Prepare the request payload
    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $system_instruction . "\n\nPlease analyze this image for elephant detection and provide detailed information if elephants are present."
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => 'image/jpeg',
                            'data' => $imageBase64
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'topK' => 32,
            'topP' => 1,
            'maxOutputTokens' => 2048,
            'candidateCount' => 1
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ],
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_ONLY_HIGH'
            ]
        ]
    ];
    
    // Make the API request
    $curl = curl_init($endpoint);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    
    if ($curlError) {
        error_log("CURL Error: " . $curlError);
        throw new Exception('Network error occurred during API request');
    }
    
    if ($httpCode !== 200) {
        error_log("Vertex AI API request failed with HTTP {$httpCode}: " . $response);
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'API request failed';
        throw new Exception('Vertex AI Error: ' . $errorMessage);
    }
    
    $responseData = json_decode($response, true);
    
    // Extract the text response
    if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        error_log("Unexpected API response structure: " . json_encode($responseData));
        throw new Exception('Invalid response format from Vertex AI');
    }
    
    return $responseData['candidates'][0]['content']['parts'][0]['text'];
}

/**
 * Main execution
 */
try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    // Check for uploaded file
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No image uploaded or upload error occurred');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $fileType = $_FILES['image']['type'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid image type. Please upload JPG, PNG, or WEBP images.');
    }
    
    // Validate file size (10MB max)
    if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
        throw new Exception('Image size must be less than 10MB');
    }
    
    // Read and encode the image
    $imageData = file_get_contents($_FILES['image']['tmp_name']);
    if ($imageData === false) {
        throw new Exception('Failed to read uploaded image');
    }
    $imageBase64 = base64_encode($imageData);
    
    // Load environment variables
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envVars = parse_ini_file($envFile);
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
    
    // Load Google Cloud credentials
    $credentialsFile = $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? 'pelagic-magpie-469618-k8-af8a7f45c226.json';
    $credentialsPath = __DIR__ . '/' . $credentialsFile;
    $credentials = loadGoogleCredentials($credentialsPath);
    
    // Extract project ID from credentials
    $projectId = $credentials['project_id'] ?? 'pelagic-magpie-469618-k8';
    
    // Get access token
    $accessToken = getAccessToken($credentials);
    if (!$accessToken) {
        throw new Exception('Failed to authenticate with Google Cloud');
    }
    
    // Detect elephants
    $detectionResult = detectElephants($imageBase64, $accessToken, $projectId);
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'response' => $detectionResult,
            'markdown_response' => $detectionResult,
            'timestamp' => date('Y-m-d H:i:s'),
            'model' => 'gemini-2.0-flash-exp'
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Elephant Detection Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}