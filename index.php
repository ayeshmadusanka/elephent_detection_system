<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elephant Detection System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .drag-active {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
        }
        @keyframes pulse-slow {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-gray-700 to-gray-900 bg-clip-text text-transparent mb-3">
                🐘 Elephant Detection System
            </h1>
            <p class="text-gray-600 text-lg">Advanced AI-powered elephant detection using Google Vertex AI</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-center space-x-4">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h2 class="text-2xl font-semibold">Upload & Detect</h2>
                        <p class="text-indigo-100">Upload an image to detect elephants</p>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-8">
                <!-- Upload Area -->
                <div id="uploadArea" class="border-3 border-dashed border-gray-300 rounded-xl p-12 text-center hover:border-indigo-400 transition-all duration-300 cursor-pointer bg-gray-50 hover:bg-indigo-50">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p class="text-xl font-medium text-gray-700 mb-2">Drop your image here</p>
                    <p class="text-gray-500 mb-4">or click to browse</p>
                    <button type="button" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        Select Image
                    </button>
                    <p class="text-xs text-gray-500 mt-3">Supports: JPG, PNG, WEBP (Max: 10MB)</p>
                    <input type="file" id="imageInput" accept="image/*" class="hidden">
                </div>

                <!-- Image Preview -->
                <div id="previewSection" class="hidden mt-8">
                    <div class="bg-gray-100 rounded-xl p-4">
                        <img id="imagePreview" class="max-h-96 mx-auto rounded-lg shadow-lg" alt="Preview">
                        <div class="mt-4 flex justify-center space-x-4">
                            <button id="removeBtn" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Remove
                            </button>
                            <button id="detectBtn" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 animate-pulse-slow">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Detect Elephants
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="resultsSection" class="hidden mt-8">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Detection Results
                        </h3>
                        <div id="resultsContent" class="prose max-w-none">
                            <!-- Results will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="mt-8 grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center mb-3">
                    <div class="bg-blue-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-800">Fast Detection</h3>
                </div>
                <p class="text-gray-600">Powered by Google Vertex AI for quick and accurate elephant detection</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center mb-3">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-800">Secure Processing</h3>
                </div>
                <p class="text-gray-600">Your images are processed securely and not stored on our servers</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-lg">
                <div class="flex items-center mb-3">
                    <div class="bg-green-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="ml-3 text-lg font-semibold text-gray-800">Free to Use</h3>
                </div>
                <p class="text-gray-600">No registration required - start detecting elephants immediately</p>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const uploadArea = document.getElementById('uploadArea');
        const imageInput = document.getElementById('imageInput');
        const previewSection = document.getElementById('previewSection');
        const imagePreview = document.getElementById('imagePreview');
        const removeBtn = document.getElementById('removeBtn');
        const detectBtn = document.getElementById('detectBtn');
        const resultsSection = document.getElementById('resultsSection');
        const resultsContent = document.getElementById('resultsContent');

        let selectedFile = null;

        // Upload area click handler
        uploadArea.addEventListener('click', () => {
            imageInput.click();
        });

        // Drag and drop handlers
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-active');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-active');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-active');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // File input change handler
        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        // Handle file selection
        function handleFile(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please select a valid image file (JPG, PNG, or WEBP)',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Please select an image smaller than 10MB',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            selectedFile = file;

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                uploadArea.classList.add('hidden');
                previewSection.classList.remove('hidden');
                resultsSection.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        // Remove button handler
        removeBtn.addEventListener('click', () => {
            selectedFile = null;
            imageInput.value = '';
            imagePreview.src = '';
            uploadArea.classList.remove('hidden');
            previewSection.classList.add('hidden');
            resultsSection.classList.add('hidden');
        });

        // Detect button handler
        detectBtn.addEventListener('click', async () => {
            if (!selectedFile) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Image Selected',
                    text: 'Please select an image first',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Detecting Elephants...',
                html: 'Processing your image with AI<br><span class="text-sm text-gray-500">This may take a few seconds</span>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Prepare form data
            const formData = new FormData();
            formData.append('image', selectedFile);

            try {
                const response = await fetch('detect.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                Swal.close();

                if (data.status === 'success') {
                    displayResults(data.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Detection Failed',
                        text: data.error || 'An unexpected error occurred',
                        confirmButtonColor: '#6366f1'
                    });
                }
            } catch (error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to connect to the server. Please try again.',
                    confirmButtonColor: '#6366f1'
                });
                console.error('Error:', error);
            }
        });

        // Display results
        function displayResults(data) {
            resultsSection.classList.remove('hidden');
            
            // Format the response for display
            let formattedContent = data.response || data.markdown_response || 'No detection data available';
            
            // Convert markdown to HTML
            formattedContent = formattedContent
                .replace(/\*\*\*(.*?)\*\*\*/g, '<strong class="text-indigo-700">$1</strong>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^### (.*$)/gim, '<h4 class="text-lg font-semibold mt-4 mb-2 text-gray-800">$1</h4>')
                .replace(/^## (.*$)/gim, '<h3 class="text-xl font-semibold mt-4 mb-2 text-gray-800">$1</h3>')
                .replace(/^# (.*$)/gim, '<h2 class="text-2xl font-bold mt-4 mb-3 text-gray-900">$1</h2>')
                .replace(/^• (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
                .replace(/^- (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
                .replace(/\n\n/g, '</p><p class="mb-3 text-gray-700">')
                .replace(/\n/g, '<br>');

            // Wrap list items
            formattedContent = formattedContent.replace(/(<li.*?<\/li>\s*)+/g, function(match) {
                return '<ul class="list-disc list-inside mb-3">' + match + '</ul>';
            });

            // Add paragraph tags if not already present
            if (!formattedContent.startsWith('<')) {
                formattedContent = '<p class="mb-3 text-gray-700">' + formattedContent + '</p>';
            }

            resultsContent.innerHTML = `
                <div class="space-y-4">
                    ${formattedContent}
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Processed at ${new Date().toLocaleString()}
                        </div>
                        <button onclick="location.reload()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Analyze Another Image →
                        </button>
                    </div>
                </div>
            `;

            // Scroll to results
            resultsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Show success notification
            Swal.fire({
                icon: 'success',
                title: 'Detection Complete!',
                text: 'The analysis has been completed successfully',
                timer: 2000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
        }
    </script>
</body>
</html>