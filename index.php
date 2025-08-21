<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elephant Detection System - AI Wildlife Conservation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-border {
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
            padding: 2px;
        }
        .hover-scale { transition: transform 0.3s; }
        .hover-scale:hover { transform: scale(1.05); }
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .7; }
        }
        .loading-dots span {
            animation: blink 1.4s infinite both;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink {
            0%, 80%, 100% { opacity: 0; }
            40% { opacity: 1; }
        }
        .result-card {
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-emerald-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="text-3xl">🐘</div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-blue-600 bg-clip-text text-transparent">
                            Elephant Detection System
                        </h1>
                        <p class="text-xs text-gray-600">Powered by Vertex AI Custom Model</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium">
                        <i data-feather="cpu" class="w-3 h-3 inline mr-1"></i>
                        AI Model Active
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Hero Section -->
        <div class="text-center mb-10">
            <h2 class="text-4xl font-bold text-gray-800 mb-3">
                Advanced Wildlife Detection Technology
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Using state-of-the-art machine learning to identify and protect elephants in their natural habitat
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-4 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Model Accuracy</p>
                        <p class="text-2xl font-bold text-gray-800">95.8%</p>
                    </div>
                    <div class="text-emerald-500">
                        <i data-feather="trending-up" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Processing Time</p>
                        <p class="text-2xl font-bold text-gray-800">&lt;2s</p>
                    </div>
                    <div class="text-blue-500">
                        <i data-feather="zap" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Images Analyzed</p>
                        <p class="text-2xl font-bold text-gray-800" id="imageCount">0</p>
                    </div>
                    <div class="text-purple-500">
                        <i data-feather="image" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 hover-scale">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Conservation Impact</p>
                        <p class="text-2xl font-bold text-gray-800">High</p>
                    </div>
                    <div class="text-orange-500">
                        <i data-feather="shield" class="w-8 h-8"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Upload Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-blue-500 p-6 text-white">
                    <h3 class="text-2xl font-semibold mb-2">Upload Image</h3>
                    <p class="text-emerald-50">Select or drag an image for elephant detection</p>
                </div>
                
                <div class="p-6">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <!-- Drop Zone -->
                        <div id="dropZone" class="border-3 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-emerald-400 transition-all">
                            <input type="file" id="imageInput" name="image" accept="image/*" class="hidden">
                            
                            <div id="uploadPlaceholder">
                                <div class="mx-auto w-20 h-20 mb-4 text-gray-400">
                                    <i data-feather="upload-cloud" class="w-20 h-20"></i>
                                </div>
                                <p class="text-lg font-medium text-gray-700 mb-2">
                                    Drop your image here or click to browse
                                </p>
                                <p class="text-sm text-gray-500">
                                    Supports JPG, PNG, WEBP (Max 10MB)
                                </p>
                            </div>
                            
                            <div id="imagePreview" class="hidden">
                                <img id="previewImg" class="mx-auto max-h-64 rounded-lg shadow-md mb-4">
                                <p class="text-sm text-gray-600 mb-2">
                                    <span id="fileName"></span>
                                </p>
                                <button type="button" id="changeImage" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Change Image
                                </button>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="mt-6 flex gap-3">
                            <button type="submit" id="detectBtn" class="flex-1 bg-gradient-to-r from-emerald-500 to-blue-500 text-white px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-feather="search" class="w-5 h-5 inline mr-2"></i>
                                Detect Elephants
                            </button>
                            <button type="button" id="clearBtn" class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-all">
                                <i data-feather="x" class="w-5 h-5 inline mr-2"></i>
                                Clear
                            </button>
                        </div>
                    </form>
                    
                    <!-- Sample Images -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600 mb-3">Try with sample images:</p>
                        <div class="grid grid-cols-4 gap-2">
                            <button onclick="loadSampleImage('sample1.jpg')" class="group relative overflow-hidden rounded-lg hover-scale">
                                <img src="https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=150&h=150&fit=crop" class="w-full h-20 object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all"></div>
                            </button>
                            <button onclick="loadSampleImage('sample2.jpg')" class="group relative overflow-hidden rounded-lg hover-scale">
                                <img src="https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?w=150&h=150&fit=crop" class="w-full h-20 object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all"></div>
                            </button>
                            <button onclick="loadSampleImage('sample3.jpg')" class="group relative overflow-hidden rounded-lg hover-scale">
                                <img src="https://images.unsplash.com/photo-1549366021-9f761d450615?w=150&h=150&fit=crop" class="w-full h-20 object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all"></div>
                            </button>
                            <button onclick="loadSampleImage('sample4.jpg')" class="group relative overflow-hidden rounded-lg hover-scale">
                                <img src="https://images.unsplash.com/photo-1551316679-9c6ae9dec224?w=150&h=150&fit=crop" class="w-full h-20 object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all"></div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-6 text-white">
                    <h3 class="text-2xl font-semibold mb-2">Detection Results</h3>
                    <p class="text-blue-50">AI-powered analysis results appear here</p>
                </div>
                
                <div class="p-6 min-h-[400px]" id="resultsContainer">
                    <!-- Initial State -->
                    <div id="initialState" class="text-center py-12">
                        <div class="mx-auto w-24 h-24 mb-4 text-gray-300">
                            <i data-feather="cpu" class="w-24 h-24"></i>
                        </div>
                        <p class="text-gray-500">Upload an image to start detection</p>
                        <p class="text-sm text-gray-400 mt-2">Our AI model will analyze the image for elephants</p>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="loadingState" class="hidden text-center py-12">
                        <div class="mx-auto w-20 h-20 mb-4 text-blue-500 pulse-animation">
                            <i data-feather="loader" class="w-20 h-20 animate-spin"></i>
                        </div>
                        <p class="text-lg font-medium text-gray-700 mb-2">Analyzing Image</p>
                        <p class="text-sm text-gray-500">
                            Processing with Vertex AI
                            <span class="loading-dots">
                                <span>.</span><span>.</span><span>.</span>
                            </span>
                        </p>
                    </div>
                    
                    <!-- Results Display -->
                    <div id="resultsDisplay" class="hidden">
                        <!-- Results will be injected here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Section -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-emerald-500 mb-3">
                    <i data-feather="info" class="w-8 h-8"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">About the Model</h4>
                <p class="text-sm text-gray-600">
                    Our custom-trained Vertex AI model uses advanced computer vision to accurately identify elephants in various environments and conditions.
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-blue-500 mb-3">
                    <i data-feather="shield" class="w-8 h-8"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Conservation Impact</h4>
                <p class="text-sm text-gray-600">
                    This technology helps wildlife organizations monitor elephant populations, track movements, and protect these magnificent creatures from threats.
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-purple-500 mb-3">
                    <i data-feather="database" class="w-8 h-8"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Dataset & Training</h4>
                <p class="text-sm text-gray-600">
                    Trained on thousands of elephant images from Google Cloud Storage, ensuring high accuracy across different species and environments.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-16 bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm text-gray-400">
                © 2024 Elephant Detection System | Powered by Google Vertex AI
            </p>
        </div>
    </footer>

    <script>
        // Initialize Feather Icons
        feather.replace();
        
        // Global variables
        let imageFile = null;
        let imageCount = parseInt(localStorage.getItem('imageCount') || '0');
        document.getElementById('imageCount').textContent = imageCount;
        
        // Elements
        const dropZone = document.getElementById('dropZone');
        const imageInput = document.getElementById('imageInput');
        const uploadForm = document.getElementById('uploadForm');
        const detectBtn = document.getElementById('detectBtn');
        const clearBtn = document.getElementById('clearBtn');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const fileName = document.getElementById('fileName');
        const changeImage = document.getElementById('changeImage');
        const initialState = document.getElementById('initialState');
        const loadingState = document.getElementById('loadingState');
        const resultsDisplay = document.getElementById('resultsDisplay');
        
        // Drop zone events
        dropZone.addEventListener('click', () => imageInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-emerald-400', 'bg-emerald-50');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-emerald-400', 'bg-emerald-50');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-emerald-400', 'bg-emerald-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
        
        // File input change
        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        // Change image button
        changeImage.addEventListener('click', () => {
            imageInput.click();
        });
        
        // Clear button
        clearBtn.addEventListener('click', clearForm);
        
        // Form submission
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!imageFile) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Image Selected',
                    text: 'Please select an image to analyze',
                    confirmButtonColor: '#10b981'
                });
                return;
            }
            
            await detectElephants();
        });
        
        // Handle file selection
        function handleFileSelect(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please upload a JPG, PNG, or WEBP image',
                    confirmButtonColor: '#10b981'
                });
                return;
            }
            
            // Validate file size (10MB)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Please upload an image smaller than 10MB',
                    confirmButtonColor: '#10b981'
                });
                return;
            }
            
            imageFile = file;
            displayImagePreview(file);
        }
        
        // Display image preview
        function displayImagePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                fileName.textContent = file.name;
                uploadPlaceholder.classList.add('hidden');
                imagePreview.classList.remove('hidden');
                detectBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }
        
        // Clear form
        function clearForm() {
            imageFile = null;
            imageInput.value = '';
            uploadPlaceholder.classList.remove('hidden');
            imagePreview.classList.add('hidden');
            detectBtn.disabled = true;
            
            // Reset results
            initialState.classList.remove('hidden');
            loadingState.classList.add('hidden');
            resultsDisplay.classList.add('hidden');
            resultsDisplay.innerHTML = '';
        }
        
        // Detect elephants
        async function detectElephants() {
            // Show loading state
            initialState.classList.add('hidden');
            loadingState.classList.remove('hidden');
            resultsDisplay.classList.add('hidden');
            
            const formData = new FormData();
            formData.append('image', imageFile);
            
            try {
                const response = await fetch('predict.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    displayResults(data.data);
                    
                    // Update image count
                    imageCount++;
                    localStorage.setItem('imageCount', imageCount.toString());
                    document.getElementById('imageCount').textContent = imageCount;
                    
                    // Show success toast
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'success',
                        title: 'Analysis Complete!'
                    });
                } else {
                    throw new Error(data.error || 'Detection failed');
                }
            } catch (error) {
                console.error('Detection error:', error);
                
                // Hide loading state
                loadingState.classList.add('hidden');
                initialState.classList.remove('hidden');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Detection Failed',
                    text: error.message || 'An error occurred during detection',
                    confirmButtonColor: '#10b981'
                });
            }
        }
        
        // Display results
        function displayResults(data) {
            loadingState.classList.add('hidden');
            resultsDisplay.classList.remove('hidden');
            
            const elephantDetected = data.elephant_detected;
            const confidence = data.confidence_percentage;
            
            let resultHTML = '<div class="result-card">';
            
            // Main result
            if (elephantDetected) {
                resultHTML += `
                    <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white p-6 rounded-xl mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-2xl font-bold mb-2">🐘 ${data.message}</h4>
                                <p class="text-emerald-50">${data.details}</p>
                            </div>
                            <div class="text-center">
                                <div class="text-4xl font-bold">${confidence}%</div>
                                <div class="text-sm text-emerald-100">Confidence</div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Conservation note
                if (data.conservation_note) {
                    resultHTML += `
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                            <div class="flex">
                                <div class="text-emerald-600 mr-3">
                                    <i data-feather="info" class="w-5 h-5"></i>
                                </div>
                                <p class="text-sm text-emerald-800">${data.conservation_note}</p>
                            </div>
                        </div>
                    `;
                }
            } else {
                resultHTML += `
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white p-6 rounded-xl mb-4">
                        <h4 class="text-2xl font-bold mb-2">${data.message}</h4>
                        <p class="text-gray-100">${data.details}</p>
                        ${data.suggestion ? `<p class="text-gray-200 text-sm mt-2">${data.suggestion}</p>` : ''}
                    </div>
                `;
            }
            
            // Top predictions
            if (data.top_predictions && data.top_predictions.length > 0) {
                resultHTML += `
                    <div class="mb-4">
                        <h5 class="text-lg font-semibold text-gray-800 mb-3">Detection Confidence</h5>
                        <div class="space-y-2">
                `;
                
                data.top_predictions.forEach((pred, index) => {
                    const isElephant = pred.label.toLowerCase().includes('elephant');
                    const barColor = isElephant ? 'bg-emerald-500' : 'bg-gray-400';
                    
                    resultHTML += `
                        <div class="flex items-center space-x-3">
                            <div class="w-32 text-sm text-gray-600">${pred.label}</div>
                            <div class="flex-1 bg-gray-200 rounded-full h-6 relative overflow-hidden">
                                <div class="${barColor} h-full rounded-full transition-all duration-500" 
                                     style="width: ${pred.percentage}%"></div>
                                <span class="absolute inset-0 flex items-center justify-center text-xs font-medium">
                                    ${pred.percentage}%
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                resultHTML += '</div></div>';
            }
            
            // Model info
            if (data.model_info) {
                resultHTML += `
                    <div class="border-t pt-4">
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>Model: ${data.model_info.name}</span>
                            <span>${data.timestamp}</span>
                        </div>
                    </div>
                `;
            }
            
            resultHTML += '</div>';
            
            resultsDisplay.innerHTML = resultHTML;
            
            // Re-initialize feather icons
            feather.replace();
        }
        
        // Load sample image (placeholder function)
        function loadSampleImage(imageName) {
            Swal.fire({
                icon: 'info',
                title: 'Sample Images',
                text: 'Please download and upload a sample elephant image to test the system',
                confirmButtonColor: '#10b981'
            });
        }
    </script>
</body>
</html>