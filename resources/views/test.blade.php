<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Import & Upload System - Test Interface</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input[type="file"],
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 15px;
            display: none;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            background: #667eea;
            color: white;
        }
        
        .nav-btn.home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .nav-btn.home:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Product Import & Upload System</h1>
        
        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <a href="{{ route('home') }}" class="nav-btn home">
                🏠 Home
            </a>
            <a href="{{ route('products.index') }}" class="nav-btn">
                📦 View Products
            </a>
        </div>
        
        <!-- CSV Import Section -->
        <div class="card">
            <h2>📄 CSV Product Import</h2>
            
            <div style="background: #f0f4f8; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong>📥 Sample CSV Files for Testing:</strong>
                <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <a href="{{ route('sample.download', 'medium_products.csv') }}" style="display: inline-block; padding: 8px 16px; background: #4299e1; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 100
                    </a>
                    <a href="{{ route('sample.download', 'test_products_1000.csv') }}" style="display: inline-block; padding: 8px 16px; background: #48bb78; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 1K
                    </a>
                    <a href="{{ route('sample.download', 'test_products_2000.csv') }}" style="display: inline-block; padding: 8px 16px; background: #38a169; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 2K
                    </a>
                    <a href="{{ route('sample.download', 'test_products_3000.csv') }}" style="display: inline-block; padding: 8px 16px; background: #2f855a; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 3K
                    </a>
                    <a href="{{ route('sample.download', 'test_products_4000.csv') }}" style="display: inline-block; padding: 8px 16px; background: #ed8936; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 4K
                    </a>
                    <a href="{{ route('sample.download', 'test_products_5000.csv') }}" style="display: inline-block; padding: 8px 16px; background: #dd6b20; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 5K
                    </a>
                    <a href="{{ route('sample.download', 'large_products.csv') }}" style="display: inline-block; padding: 8px 16px; background: #e53e3e; color: white; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;">
                        📄 10K+
                    </a>
                </div>
                <div style="margin-top: 8px; font-size: 12px; color: #718096;">
                    💡 Tip: Start with 100 for quick testing, then scale up to test performance
                </div>
            </div>
            
            <div class="form-group">
                <label for="csvFile">Upload CSV File (sku, name, price, description, stock, image_path)</label>
                <input type="file" id="csvFile" accept=".csv">
            </div>
            <button onclick="importCSV()">Import Products</button>
            
            <div id="importResult" class="result"></div>
            <div id="importStats" class="stats" style="display: none;"></div>
        </div>
        
        <!-- Chunked Upload Section -->
        <!-- Chunked Image Upload -->
        <div class="card">
            <h2>📤 Upload Your Image (Drag & Drop)</h2>
            
            <!-- Drag and Drop Zone -->
            <div class="drop-zone" id="dropZone" style="border: 3px dashed #cbd5e0; border-radius: 12px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: #f7fafc; margin-bottom: 20px;">
                <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">📁</div>
                <div style="font-size: 18px; color: #4a5568; font-weight: 600; margin-bottom: 8px;">Drag & Drop your image here</div>
                <div style="font-size: 14px; color: #718096;">or click to browse</div>
            </div>
            
            <!-- File Info Display -->
            <div id="fileInfo" style="display: none; margin-top: 15px; padding: 15px; background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 8px; text-align: left;">
                <div id="fileName" style="font-weight: 600; color: #2d3748; margin-bottom: 5px;"></div>
                <div id="fileSize" style="color: #718096; font-size: 13px;"></div>
            </div>
            
            <!-- Hidden File Input -->
            <div class="form-group" style="display: none;">
                <input type="file" id="imageFile" accept="image/*">
            </div>
            
            <button onclick="uploadImage()" id="uploadBtn">Upload Image</button>
            
            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill">0%</div>
            </div>
            
            <div id="uploadResult" class="result"></div>
        </div>
        
        <!-- Attach Image to Product -->
        <div class="card">
            <h2>🔗 Attach Image to Product</h2>
            <div class="two-columns">
                <div class="form-group">
                    <label for="productId">Product ID</label>
                    <input type="number" id="productId" placeholder="Enter product ID">
                </div>
                <div class="form-group">
                    <label for="uploadUuid">Upload UUID</label>
                    <input type="text" id="uploadUuid" placeholder="UUID from upload">
                </div>
            </div>
            <button onclick="attachImage()">Attach Image</button>
            
            <div id="attachResult" class="result"></div>
        </div>
        
        <!-- API Response -->
        <div class="card">
            <h2>📊 API Response</h2>
            <pre id="apiResponse">API responses will appear here...</pre>
        </div>
    </div>

    <script>
        // API Routes
        const API_ROUTES = {
            productsImport: '{{ route('api.products.import') }}',
            uploadsInitialize: '{{ route('api.uploads.initialize') }}',
            uploadsChunk: '{{ url('api/uploads') }}', // Base URL for dynamic UUID
            uploadsStatus: '{{ url('api/uploads') }}', // Base URL for dynamic UUID
            productsAttachImage: '{{ url('api/products') }}' // Base URL for dynamic product ID
        };
        
        function showResult(elementId, message, type) {
            const el = document.getElementById(elementId);
            el.textContent = message;
            el.className = `result ${type}`;
            el.style.display = 'block';
        }
        
        function showApiResponse(data) {
            document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);
        }

        // Drag and Drop Handlers
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('imageFile');
        const fileInfo = document.getElementById('fileInfo');
        
        // Click to browse
        dropZone.addEventListener('click', () => {
            fileInput.click();
        });
        
        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = '#4299e1';
                dropZone.style.background = '#ebf8ff';
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.borderColor = '#cbd5e0';
                dropZone.style.background = '#f7fafc';
            }, false);
        });
        
        // Handle dropped files
        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                // Update the hidden file input
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
                handleFileSelect(file);
            }
        }, false);
        
        function handleFileSelect(file) {
            // Check if it's an image
            if (!file.type.startsWith('image/')) {
                showResult('uploadResult', 'Please select an image file', 'error');
                return;
            }
            
            // Show file info
            document.getElementById('fileName').textContent = `📄 ${file.name}`;
            document.getElementById('fileSize').textContent = `Size: ${formatFileSize(file.size)}`;
            fileInfo.style.display = 'block';
            
            // Clear previous results
            document.getElementById('uploadResult').style.display = 'none';
        }
        
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }
        
        async function importCSV() {
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showResult('importResult', 'Please select a CSV file', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('csv_file', file);
            
            showResult('importResult', 'Importing products... (this may take a few minutes)', 'info');
            
            try {
                const response = await fetch(API_ROUTES.productsImport, {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is JSON or HTML/text
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    // Server returned HTML/text error (timeout, memory error, etc.)
                    const text = await response.text();
                    
                    // Extract error message from HTML if possible
                    let errorMsg = 'Server error occurred';
                    if (text.includes('Maximum execution time')) {
                        errorMsg = 'Import timeout - file too large. Try a smaller file or increase server timeout.';
                    } else if (text.includes('memory') || text.includes('Memory')) {
                        errorMsg = 'Out of memory - file too large. Try a smaller file or increase server memory limit.';
                    } else if (text.includes('500')) {
                        errorMsg = 'Server error (500) - check server logs for details.';
                    }
                    
                    showResult('importResult', errorMsg, 'error');
                    showApiResponse({error: errorMsg, details: text.substring(0, 500)});
                    return;
                }
                
                showApiResponse(data);
                
                if (data.success) {
                    showResult('importResult', 'Import completed successfully!', 'success');
                    
                    // Show stats
                    const statsDiv = document.getElementById('importStats');
                    statsDiv.innerHTML = `
                        <div class="stat-box">
                            <div class="stat-label">Total Rows</div>
                            <div class="stat-value">${data.data.total}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Imported</div>
                            <div class="stat-value">${data.data.imported}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Updated</div>
                            <div class="stat-value">${data.data.updated}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Invalid</div>
                            <div class="stat-value">${data.data.invalid}</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Duplicates</div>
                            <div class="stat-value">${data.data.duplicates}</div>
                        </div>
                    `;
                    statsDiv.style.display = 'grid';
                } else {
                    showResult('importResult', data.message || 'Import failed', 'error');
                }
            } catch (error) {
                showResult('importResult', 'Error: ' + error.message, 'error');
                showApiResponse({error: error.message});
            }
        }
        
        async function uploadImage() {
            const fileInput = document.getElementById('imageFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showResult('uploadResult', 'Please select an image file', 'error');
                return;
            }
            
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            
            showResult('uploadResult', 'Initializing upload...', 'info');
            document.getElementById('progressBar').style.display = 'block';
            
            try {
                // Step 1: Calculate checksum
                const fileBuffer = await file.arrayBuffer();
                const hashBuffer = await crypto.subtle.digest('SHA-256', fileBuffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const checksum = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                
                // Step 2: Initialize upload
                const initResponse = await fetch(API_ROUTES.uploadsInitialize, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        filename: file.name,
                        total_size: file.size,
                        mime_type: file.type,
                        checksum: checksum
                    })
                });
                
                const initData = await initResponse.json();
                showApiResponse(initData);
                
                if (!initData.success) {
                    throw new Error(initData.message || 'Failed to initialize upload');
                }
                
                const uploadUuid = initData.data.uuid;
                const totalChunks = initData.data.total_chunks;
                const chunkSize = 1024 * 1024; // 1MB
                
                // Step 3: Upload chunks
                for (let i = 0; i < totalChunks; i++) {
                    const start = i * chunkSize;
                    const end = Math.min(start + chunkSize, file.size);
                    const chunk = file.slice(start, end);
                    
                    // Calculate chunk checksum
                    const chunkBuffer = await chunk.arrayBuffer();
                    const chunkHashBuffer = await crypto.subtle.digest('SHA-256', chunkBuffer);
                    const chunkHashArray = Array.from(new Uint8Array(chunkHashBuffer));
                    const chunkChecksum = chunkHashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                    
                    // Convert chunk to base64
                    const reader = new FileReader();
                    const base64Chunk = await new Promise((resolve) => {
                        reader.onload = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(chunk);
                    });
                    
                    // Upload chunk
                    const chunkResponse = await fetch(`${API_ROUTES.uploadsChunk}/${uploadUuid}/chunk`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            chunk_number: i,
                            chunk_data: base64Chunk,
                            chunk_checksum: chunkChecksum
                        })
                    });
                    
                    const chunkData = await chunkResponse.json();
                    
                    if (!chunkData.success) {
                        throw new Error(chunkData.message || 'Failed to upload chunk');
                    }
                    
                    // Update progress
                    const progress = ((i + 1) / totalChunks) * 100;
                    const progressFill = document.getElementById('progressFill');
                    progressFill.style.width = progress + '%';
                    progressFill.textContent = Math.round(progress) + '%';
                    
                    showResult('uploadResult', `Uploading... Chunk ${i + 1}/${totalChunks}`, 'info');
                }
                
                // Success!
                showResult('uploadResult', `✅ Upload completed! UUID: ${uploadUuid}`, 'success');
                document.getElementById('uploadUuid').value = uploadUuid;
                
                // Show final status
                const statusResponse = await fetch(`${API_ROUTES.uploadsStatus}/${uploadUuid}/status`);
                const statusData = await statusResponse.json();
                showApiResponse(statusData);
                
            } catch (error) {
                showResult('uploadResult', 'Error: ' + error.message, 'error');
                showApiResponse({error: error.message});
            } finally {
                uploadBtn.disabled = false;
            }
        }
        
        async function attachImage() {
            const productId = document.getElementById('productId').value;
            const uploadUuid = document.getElementById('uploadUuid').value;
            
            if (!productId || !uploadUuid) {
                showResult('attachResult', 'Please enter both Product ID and Upload UUID', 'error');
                return;
            }
            
            showResult('attachResult', 'Attaching image to product...', 'info');
            
            try {
                const response = await fetch(`${API_ROUTES.productsAttachImage}/${productId}/attach-image`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        upload_uuid: uploadUuid
                    })
                });
                
                const data = await response.json();
                showApiResponse(data);
                
                if (data.success) {
                    showResult('attachResult', '✅ Image attached successfully!', 'success');
                } else {
                    showResult('attachResult', data.message || 'Failed to attach image', 'error');
                }
            } catch (error) {
                showResult('attachResult', 'Error: ' + error.message, 'error');
                showApiResponse({error: error.message});
            }
        }
    </script>
</body>
</html>
