<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Listing - AG Grid</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- AG Grid CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/styles/ag-theme-alpine.css">
    
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2em;
        }
        
        .header .stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-box {
            text-align: center;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-box .number {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-box .label {
            font-size: 0.85em;
            color: #666;
        }
        
        .grid-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .ag-theme-alpine {
            height: 600px;
            width: 100%;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .product-image:hover {
            transform: scale(1.5);
        }
        
        .actions {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Modal for image preview */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Product Listing</h1>
            <div class="stats">
                <div class="stat-box">
                    <div class="number" id="totalProducts">0</div>
                    <div class="label">Total Products</div>
                </div>
                <div class="stat-box">
                    <div class="number" id="totalImages">0</div>
                    <div class="label">Total Images</div>
                </div>
            </div>
        </div>
        
        <div class="grid-container">
            <div class="actions">
                <button class="btn btn-primary" onclick="refreshGrid()">🔄 Refresh</button>
                <button class="btn btn-secondary" onclick="exportToCSV()">📥 Export CSV</button>
                <div class="search-box">
                    <input type="text" id="searchBox" placeholder="🔍 Search products...">
                </div>
                <button class="btn btn-secondary" onclick="window.location.href='/test'">🧪 Test Import</button>
                <button class="btn btn-secondary" onclick="window.location.href='/'">🏠 Home</button>
            </div>
            
            <div id="myGrid" class="ag-theme-alpine"></div>
        </div>
    </div>
    
    <!-- Image Modal -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>
    
    <!-- AG Grid JS -->
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.3/dist/ag-grid-community.min.js"></script>
    
    <script>
        let gridApi;
        
        // Image renderer
        class ImageRenderer {
            init(params) {
                this.eGui = document.createElement('div');
                if (params.value) {
                    const img = document.createElement('img');
                    img.src = params.value;
                    img.className = 'product-image';
                    img.onclick = () => showImageModal(params.value);
                    this.eGui.appendChild(img);
                    
                    // Show image count badge
                    if (params.data.image_count > 1) {
                        const badge = document.createElement('span');
                        badge.innerHTML = ` +${params.data.image_count - 1}`;
                        badge.style.fontSize = '11px';
                        badge.style.color = '#667eea';
                        badge.style.fontWeight = 'bold';
                        this.eGui.appendChild(badge);
                    }
                } else {
                    this.eGui.innerHTML = '—';
                }
            }
            
            getGui() {
                return this.eGui;
            }
        }
        
        // Price renderer
        class PriceRenderer {
            init(params) {
                this.eGui = document.createElement('span');
                this.eGui.innerHTML = params.value ? `$${parseFloat(params.value).toFixed(2)}` : '—';
                this.eGui.style.fontWeight = 'bold';
                this.eGui.style.color = '#28a745';
            }
            
            getGui() {
                return this.eGui;
            }
        }
        
        // Stock renderer
        class StockRenderer {
            init(params) {
                this.eGui = document.createElement('span');
                const stock = parseInt(params.value) || 0;
                this.eGui.innerHTML = stock;
                
                if (stock < 50) {
                    this.eGui.style.color = '#dc3545';
                    this.eGui.style.fontWeight = 'bold';
                } else if (stock < 200) {
                    this.eGui.style.color = '#ffc107';
                    this.eGui.style.fontWeight = 'bold';
                } else {
                    this.eGui.style.color = '#28a745';
                }
            }
            
            getGui() {
                return this.eGui;
            }
        }
        
        // Column definitions
        const columnDefs = [
            { 
                field: 'id', 
                headerName: 'ID', 
                width: 80,
                filter: 'agNumberColumnFilter',
                sortable: true
            },
            { 
                field: 'image', 
                headerName: 'Image', 
                width: 100,
                cellRenderer: ImageRenderer,
                sortable: false,
                filter: false
            },
            { 
                field: 'sku', 
                headerName: 'SKU', 
                width: 130,
                filter: 'agTextColumnFilter',
                sortable: true
            },
            { 
                field: 'name', 
                headerName: 'Product Name', 
                flex: 1,
                minWidth: 200,
                filter: 'agTextColumnFilter',
                sortable: true
            },
            { 
                field: 'price', 
                headerName: 'Price', 
                width: 120,
                valueFormatter: params => params.value ? `$${params.value}` : '—',
                cellStyle: { fontWeight: 'bold', color: '#28a745' },
                filter: 'agNumberColumnFilter',
                sortable: true
            },
            { 
                field: 'stock', 
                headerName: 'Stock', 
                width: 100,
                cellRenderer: StockRenderer,
                filter: 'agNumberColumnFilter',
                sortable: true
            },
            { 
                field: 'description', 
                headerName: 'Description', 
                width: 250,
                filter: 'agTextColumnFilter',
                sortable: true,
                cellStyle: { 
                    whiteSpace: 'nowrap', 
                    overflow: 'hidden', 
                    textOverflow: 'ellipsis' 
                }
            },
            { 
                field: 'image_count', 
                headerName: 'Images', 
                width: 90,
                filter: 'agNumberColumnFilter',
                sortable: true
            },
            { 
                field: 'created_at', 
                headerName: 'Created', 
                width: 180,
                filter: 'agDateColumnFilter',
                sortable: true
            }
        ];
        
        // Grid options with server-side pagination
        const gridOptions = {
            columnDefs: columnDefs,
            defaultColDef: {
                resizable: true,
                sortable: true,
                filter: true
            },
            rowSelection: 'multiple',
            animateRows: true,
            
            // Server-side pagination
            pagination: true,
            paginationPageSize: 50,
            paginationPageSizeSelector: [25, 50, 100, 200],
            
            // Infinite scroll model for server-side data
            rowModelType: 'infinite',
            cacheBlockSize: 50,
            maxBlocksInCache: 10,
            
            onGridReady: function(params) {
                gridApi = params.api;
                
                // Create datasource for infinite scroll
                const datasource = {
                    getRows: async (params) => {
                        console.log('Requesting rows from', params.startRow, 'to', params.endRow);
                        
                        try {
                            const response = await fetch('/api/products', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    startRow: params.startRow,
                                    endRow: params.endRow,
                                    sortModel: params.sortModel,
                                    filterModel: params.filterModel
                                })
                            });
                            
                            if (!response.ok) {
                                const errorText = await response.text();
                                console.error('Response error:', errorText);
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            
                            const data = await response.json();
                            console.log('Loaded data:', data.rowData.length, 'rows');
                            
                            if (!data.success) {
                                throw new Error(data.message || 'Failed to load products');
                            }
                            
                            // Update stats
                            document.getElementById('totalProducts').textContent = data.rowCount || 0;
                            
                            // Calculate total images
                            const totalImages = data.rowData.reduce((sum, p) => sum + (p.image_count || 0), 0);
                            document.getElementById('totalImages').textContent = totalImages;
                            
                            // Success callback with row count for infinite scroll
                            params.successCallback(data.rowData, data.rowCount);
                            
                        } catch (error) {
                            console.error('Error loading products:', error);
                            params.failCallback();
                            alert('Failed to load products: ' + error.message);
                        }
                    }
                };
                
                // Set the datasource
                gridApi.setDatasource(datasource);
            }
        };
        
        // Initialize grid
        document.addEventListener('DOMContentLoaded', function() {
            const gridDiv = document.getElementById('myGrid');
            new agGrid.Grid(gridDiv, gridOptions);
            
            // Note: Quick filter doesn't work with infinite row model
            // Disable search box for server-side pagination
            const searchBox = document.getElementById('searchBox');
            if (searchBox) {
                searchBox.style.display = 'none';
            }
        });
        
        // Refresh grid
        function refreshGrid() {
            if (gridApi) {
                // Purge cache and reload
                gridApi.purgeInfiniteCache();
            }
        }
        
        // Export to CSV - fetch all records from server
        async function exportToCSV() {
            try {
                // Show loading indicator on the export button
                const exportBtn = document.querySelector('.btn-secondary[onclick="exportToCSV()"]');
                const originalText = exportBtn ? exportBtn.textContent : '';
                if (exportBtn) {
                    exportBtn.disabled = true;
                    exportBtn.innerHTML = '⏳ Exporting...';
                }
                
                // First, get the total count of products
                const countResponse = await fetch('/api/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        startRow: 0,
                        endRow: 1, // Just get the count
                        sortModel: [],
                        filterModel: {}
                    })
                });
                
                if (!countResponse.ok) {
                    throw new Error('Failed to fetch product count');
                }
                
                const countData = await countResponse.json();
                const totalCount = countData.rowCount || 0;
                
                if (totalCount === 0) {
                    alert('No products to export');
                    if (exportBtn) {
                        exportBtn.disabled = false;
                        exportBtn.innerHTML = originalText;
                    }
                    return;
                }
                
                // Update button to show progress
                if (exportBtn) {
                    exportBtn.innerHTML = `⏳ Exporting ${totalCount} products...`;
                }
                
                // Now fetch ALL products using the actual total count
                const response = await fetch('/api/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        startRow: 0,
                        endRow: totalCount, // Use actual count to get ALL records
                        sortModel: [],
                        filterModel: {}
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch products for export');
                }
                
                const data = await response.json();
                
                if (!data.success || !data.rowData) {
                    throw new Error('Invalid response from server');
                }
                
                // Convert to CSV
                const products = data.rowData;
                
                // CSV Headers
                const headers = ['ID', 'SKU', 'Name', 'Price', 'Stock', 'Description', 'Images', 'Created At'];
                const csvRows = [];
                csvRows.push(headers.join(','));
                
                // CSV Data
                products.forEach(product => {
                    const row = [
                        product.id,
                        `"${product.sku}"`,
                        `"${(product.name || '').replace(/"/g, '""')}"`, // Escape quotes
                        product.price || 0,
                        product.stock || 0,
                        `"${(product.description || '').replace(/"/g, '""')}"`,
                        product.image_count || 0,
                        `"${product.created_at}"`
                    ];
                    csvRows.push(row.join(','));
                });
                
                // Create CSV blob
                const csvContent = csvRows.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                
                // Download file
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'products_export_' + new Date().toISOString().slice(0,10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Show success message
                alert(`Successfully exported ${products.length} products!`);
                
                // Restore button
                if (exportBtn) {
                    exportBtn.disabled = false;
                    exportBtn.innerHTML = originalText;
                }
                
            } catch (error) {
                console.error('Export error:', error);
                alert('Failed to export products: ' + error.message);
                
                // Restore button
                const exportBtn = document.querySelector('.btn-secondary[onclick="exportToCSV()"]');
                if (exportBtn) {
                    exportBtn.disabled = false;
                    exportBtn.innerHTML = '📥 Export CSV';
                }
            }
        }
        
        // Image modal functions
        function showImageModal(url) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'block';
            modalImg.src = url;
        }
        
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>
</html>
