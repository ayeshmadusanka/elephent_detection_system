# 🐘 Elephant Detection System

A modern, single-page web application for detecting elephants in images using Google Vertex AI and machine learning.

## Features

- **AI-Powered Detection**: Uses Google Vertex AI's Gemini model for accurate elephant detection
- **Modern UI**: Clean, responsive interface built with Tailwind CSS
- **User-Friendly**: Drag-and-drop or click to upload images
- **Real-time Feedback**: SweetAlert2 for elegant notifications and loading states
- **Comprehensive Analysis**: Provides detailed information about detected elephants including:
  - Species identification (African/Asian)
  - Physical characteristics
  - Behavioral observations
  - Conservation status
  - Habitat assessment

## Technology Stack

- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Backend**: PHP 7.4+
- **AI/ML**: Google Vertex AI (Gemini 2.0 Flash)
- **Notifications**: SweetAlert2
- **Dataset**: Stored in Google Cloud Storage at `gs://prasa_bucket/Elephant_Dataset_Finalized`

## Project Structure

```
elephant-detection/
├── index.php                    # Main application page
├── detect.php                   # Backend API for elephant detection
├── pelagic-magpie-*.json       # Google Cloud credentials (auto-loaded)
└── README.md                    # This file
```

## Prerequisites

- PHP 7.4 or higher with the following extensions:
  - curl
  - json
  - openssl
- Web server (Apache/Nginx) or PHP built-in server
- Google Cloud Project with Vertex AI API enabled
- Service account credentials with Vertex AI permissions

## Installation

1. **Clone or copy the project files** to your web server directory:
   ```bash
   cd /path/to/your/webserver
   cp -r elephant-detection /var/www/html/
   ```

2. **Verify credentials** are in place:
   - The file `pelagic-magpie-469618-k8-af8a7f45c226.json` should be in the project directory
   - This file contains your Google Cloud service account credentials

3. **Set appropriate permissions**:
   ```bash
   chmod 755 elephant-detection
   chmod 644 elephant-detection/*.php
   chmod 600 elephant-detection/*.json  # Protect credentials
   ```

## Running the Application

### Option 1: Using PHP Built-in Server (Development)

```bash
cd elephant-detection
php -S localhost:8000
```

Then open your browser and navigate to: `http://localhost:8000`

### Option 2: Using Apache/Nginx (Production)

1. Place the files in your web server's document root
2. Navigate to: `http://your-domain.com/elephant-detection/`

## Usage

1. **Open the application** in your web browser
2. **Upload an image** by either:
   - Clicking the "Select Image" button
   - Dragging and dropping an image onto the upload area
3. **Click "Detect Elephants"** to process the image
4. **View results** with detailed information about any detected elephants

## API Endpoint

The backend exposes a single endpoint:

**POST** `/detect.php`
- **Request**: Multipart form data with `image` field
- **Response**: JSON with detection results
- **Example Response**:
  ```json
  {
    "status": "success",
    "data": {
      "response": "Detection results...",
      "timestamp": "2024-01-15 10:30:00",
      "model": "gemini-2.0-flash-exp"
    }
  }
  ```

## Configuration

The system uses the following Google Cloud configuration:
- **Project ID**: pelagic-magpie-469618-k8
- **Region**: us-central1
- **Model**: gemini-2.0-flash-exp
- **Dataset Location**: gs://prasa_bucket/Elephant_Dataset_Finalized

## Security Considerations

1. **Credentials Protection**: Keep the JSON credentials file secure and never commit it to version control
2. **File Upload Validation**: The system validates file types and sizes
3. **HTTPS**: Use HTTPS in production to encrypt data in transit
4. **Rate Limiting**: Consider implementing rate limiting for production use

## Troubleshooting

### Common Issues

1. **"Credentials file not found"**
   - Ensure the JSON credentials file is in the project directory
   - Check file permissions

2. **"Failed to authenticate with Google Cloud"**
   - Verify the service account has Vertex AI permissions
   - Check that the Vertex AI API is enabled in your project

3. **"Network error occurred"**
   - Check your internet connection
   - Verify PHP curl extension is enabled
   - Check firewall settings

4. **Image upload fails**
   - Ensure the image is under 10MB
   - Supported formats: JPG, PNG, WEBP
   - Check PHP upload_max_filesize and post_max_size settings

## Performance Tips

- Images are processed in real-time and not stored on the server
- For best results, use clear, well-lit images of elephants
- The system works best with images between 500KB and 5MB

## License

This project is provided as-is for educational and conservation purposes.

## Support

For issues or questions about the elephant detection system, please check the logs in your PHP error log for detailed error messages.