<?php
/**
 * Vertex AI Custom Model Prediction API
 * Handles predictions from trained elephant detection model
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configuration
require_once __DIR__ . '/vertex_ai_config.php';

/**
 * Load environment variables
 */
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envVars = parse_ini_file($envFile);
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Load Google Cloud credentials
 */
function loadGoogleCredentials($credentialsPath) {
    if (!file_exists($credentialsPath)) {
        throw new Exception('Credentials file not found');
    }
    
    $credentials = json_decode(file_get_contents($credentialsPath), true);
    if (!$credentials) {
        throw new Exception('Invalid credentials format');
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
 * Get Google Cloud access token
 */
function getAccessToken($credentials) {
    $client_email = $credentials['client_email'];
    $private_key = $credentials['private_key'];
    $token_uri = $credentials['token_uri'];
    
    // Create JWT
    $jwt_header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];
    
    $jwt_claim = [
        'iss' => $client_email,
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => $token_uri,
        'exp' => time() + 3600,
        'iat' => time()
    ];
    
    $jwt = base64url_encode(json_encode($jwt_header)) . '.' . base64url_encode(json_encode($jwt_claim));
    
    openssl_sign($jwt, $signature, $private_key, 'sha256WithRSAEncryption');
    $jwt .= '.' . base64url_encode($signature);
    
    // Request token
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
        throw new Exception('Failed to obtain access token');
    }
    
    $token_response = json_decode($response, true);
    return $token_response['access_token'] ?? null;
}

/**
 * Predict using custom trained model
 */
function predictWithCustomModel($imageBase64, $accessToken) {
    global $endpoint_config;
    
    // Prepare prediction request
    $payload = [
        'instances' => [
            [
                'content' => $imageBase64
            ]
        ]
    ];
    
    // Make prediction request
    $curl = curl_init(VERTEX_AI_ENDPOINT);
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
        throw new Exception('Network error: ' . $curlError);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Prediction failed';
        throw new Exception($errorMessage);
    }
    
    $responseData = json_decode($response, true);
    
    if (!isset($responseData['predictions'][0])) {
        throw new Exception('Invalid prediction response');
    }
    
    return $responseData['predictions'][0];
}

/**
 * Format prediction results
 */
function formatPredictionResults($prediction) {
    $displayNames = $prediction['displayNames'] ?? [];
    $confidences = $prediction['confidences'] ?? [];
    
    // Combine labels with confidence scores
    $results = [];
    for ($i = 0; $i < count($displayNames); $i++) {
        $results[] = [
            'label' => $displayNames[$i],
            'confidence' => $confidences[$i] ?? 0,
            'percentage' => round(($confidences[$i] ?? 0) * 100, 2)
        ];
    }
    
    // Sort by confidence
    usort($results, function($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });
    
    // Determine if elephant is detected
    $elephantDetected = false;
    $elephantConfidence = 0;
    
    foreach ($results as $result) {
        if (stripos($result['label'], 'elephant') !== false) {
            $elephantDetected = true;
            $elephantConfidence = $result['confidence'];
            break;
        }
    }
    
    // Create detailed response
    $response = [
        'elephant_detected' => $elephantDetected,
        'confidence' => $elephantConfidence,
        'confidence_percentage' => round($elephantConfidence * 100, 2),
        'top_predictions' => array_slice($results, 0, 5),
        'all_predictions' => $results
    ];
    
    // Add detection message
    if ($elephantDetected) {
        $confidenceLevel = $elephantConfidence > 0.9 ? 'Very High' : 
                          ($elephantConfidence > 0.7 ? 'High' : 
                          ($elephantConfidence > 0.5 ? 'Moderate' : 'Low'));
        
        $response['message'] = "🐘 Elephant Detected!";
        $response['details'] = "An elephant has been detected in the image with {$confidenceLevel} confidence ({$response['confidence_percentage']}%).";
        $response['conservation_note'] = "Elephants are magnificent creatures that play a crucial role in their ecosystems. This detection helps in wildlife monitoring and conservation efforts.";
    } else {
        $topLabel = $results[0]['label'] ?? 'Unknown';
        $response['message'] = "No Elephant Detected";
        $response['details'] = "No elephants were detected in this image. The image appears to contain: {$topLabel}.";
        $response['suggestion'] = "Try uploading a clear image containing elephants for detection.";
    }
    
    return $response;
}

/**
 * Validate uploaded image
 */
function validateImage($file) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No image uploaded or upload error occurred');
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $fileType = $file['type'];
    
    // Also check file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid image type. Please upload JPG, PNG, or WEBP images.');
    }
    
    // Validate file size (10MB max)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Image size must be less than 10MB');
    }
    
    // Verify it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Invalid image file');
    }
    
    return true;
}

/**
 * Main execution
 */
try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    // Check if vertex_ai_config.php exists
    if (!file_exists(__DIR__ . '/vertex_ai_config.php')) {
        throw new Exception('Model not deployed. Please run the deployment scripts first.');
    }
    
    // Validate uploaded image
    validateImage($_FILES['image']);
    
    // Read and encode image
    $imageData = file_get_contents($_FILES['image']['tmp_name']);
    if ($imageData === false) {
        throw new Exception('Failed to read uploaded image');
    }
    
    // Resize image if needed (for faster processing)
    $image = imagecreatefromstring($imageData);
    if ($image !== false) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize if larger than 1024px
        $maxDimension = 1024;
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, 
                              $newWidth, $newHeight, $width, $height);
            
            // Get resized image data
            ob_start();
            imagejpeg($resized, null, 90);
            $imageData = ob_get_clean();
            
            imagedestroy($resized);
        }
        imagedestroy($image);
    }
    
    $imageBase64 = base64_encode($imageData);
    
    // Load environment and credentials
    loadEnv();
    $credentialsFile = $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? 'credentials.json';
    $credentialsPath = __DIR__ . '/' . $credentialsFile;
    $credentials = loadGoogleCredentials($credentialsPath);
    
    // Get access token
    $accessToken = getAccessToken($credentials);
    if (!$accessToken) {
        throw new Exception('Failed to authenticate with Google Cloud');
    }
    
    // Make prediction
    $prediction = predictWithCustomModel($imageBase64, $accessToken);
    
    // Format results
    $results = formatPredictionResults($prediction);
    
    // Add metadata
    $results['model_info'] = [
        'name' => VERTEX_AI_MODEL_NAME,
        'type' => 'Custom Trained Model',
        'endpoint' => $endpoint_config['endpoint_id'] ?? 'Unknown'
    ];
    $results['timestamp'] = date('Y-m-d H:i:s');
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Elephant Detection Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}