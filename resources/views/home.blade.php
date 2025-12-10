<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Bulk Import & Upload System</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        h1 {
            color: #667eea;
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        p {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        a {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1em;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        a:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .features {
            margin-top: 40px;
            text-align: left;
        }
        
        .feature {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .feature h3 {
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .feature p {
            margin: 0;
            font-size: 0.95em;
        }
        
        .badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Laravel Import System</h1>
        <span class="badge">✅ Server Running</span>
        
        <p>
            A comprehensive bulk CSV product import system with chunked/resumable image uploads,
            featuring transaction safety, checksum validation, and automated image processing.
        </p>
        
        <div class="buttons">
            <a href="/test">🧪 Open Test Interface</a>
            <a href="/products">📦 View Products</a>
        </div>
        
        <div class="features">
            <div class="feature">
                <h3>📄 Bulk CSV Import</h3>
                <p>Upsert products by SKU with validation, duplicate tracking, and error handling</p>
            </div>
            
            <div class="feature">
                <h3>🖼️ Chunked Image Upload</h3>
                <p>1MB chunks with SHA-256 validation, resume capability, and progress tracking</p>
            </div>
            
            <div class="feature">
                <h3>🎨 Auto Image Processing</h3>
                <p>Generates 4 variants (original, 256px, 512px, 1024px) maintaining aspect ratio</p>
            </div>
            
            <div class="feature">
                <h3>🔒 Transaction Safety</h3>
                <p>Database transactions, row locking, and idempotent operations</p>
            </div>
        </div>
    </div>
</body>
</html>
